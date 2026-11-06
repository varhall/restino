<?php

namespace Varhall\Restino\Results;

use Nette\Database\Table\Selection;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Varhall\Dbino\Collections\Collection;
use Varhall\Utilino\Collections\ICollection;

/**
 * @phpstan-consistent-constructor
 */
class CollectionResult extends AbstractResult
{
    const DEFAULT_LIMIT     = 100;
    const MAX_LIMIT         = 1000;
    const DEFAULT_OFFSET    = 0;

    protected ICollection|Selection $data;

    /** requested page size, null = default */
    protected ?int $limit       = null;
    protected int $offset       = self::DEFAULT_OFFSET;
    protected array $order      = [];

    /** page size when no limit is requested */
    protected int $defaultLimit = self::DEFAULT_LIMIT;

    /** maximal page size, null = unlimited */
    protected ?int $maxLimit    = self::MAX_LIMIT;

    /** limits were set explicitly by setLimits() */
    protected bool $hasLimits   = false;

    public function __construct(ICollection|Selection $data)
    {
        $this->data = $data;
    }

    public static function fromResult(AbstractResult $result): static
    {
        $self = new static($result->getData());
        $self->mappers = $result->mappers;

        if ($result->serializer !== null) {
            $self->setSerializer($result->serializer);
        }

        if ($result instanceof CollectionResult && $result->hasLimits()) {
            $self->setLimits($result->defaultLimit, $result->maxLimit);
        }

        return $self;
    }

    public function getData(): ICollection|Selection
    {
        return $this->data;
    }

    /**
     * Sets page size used when the client does not request any and the maximal page size (null = unlimited).
     * Limits are applied when the result is executed, so the order of setLimits() and paginate() does not matter.
     * Explicitly set limits take precedence over the limits from the configuration.
     */
    public function setLimits(int $defaultLimit, ?int $maxLimit): static
    {
        if ($defaultLimit < 1 || ($maxLimit !== null && $maxLimit < $defaultLimit)) {
            throw new \Nette\InvalidArgumentException(
                "Invalid collection limits: default {$defaultLimit} must be at least 1 and not greater than maximum " . ($maxLimit ?? 'unlimited') . '.'
            );
        }

        $this->defaultLimit = $defaultLimit;
        $this->maxLimit = $maxLimit;
        $this->hasLimits = true;

        return $this;
    }

    /**
     * Limits were set explicitly
     */
    public function hasLimits(): bool
    {
        return $this->hasLimits;
    }

    public function getDefaultLimit(): int
    {
        return $this->defaultLimit;
    }

    public function getMaxLimit(): ?int
    {
        return $this->maxLimit;
    }

    /**
     * Effective page size - requested limit (or default) capped by the maximum
     */
    public function getLimit(): int
    {
        $limit = $this->limit ?? $this->defaultLimit;

        return $this->maxLimit === null ? $limit : min($limit, $this->maxLimit);
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getOrder(): array
    {
        return $this->order;
    }

    public function paginate(?int $limit, ?int $offset): void
    {
        $this->limit = $limit;
        $this->offset = $offset ?? self::DEFAULT_OFFSET;
    }

    public function order(string $column, bool $desc = false): void
    {
        $this->order[$column] = $desc;
    }

    public function execute(IResponse $response, IRequest $request): mixed
    {
        // paginate first
        $pagination = $this->pagination();

        $response->setHeader('X-Limit', (string) $pagination->getLimit())
            ->setHeader('X-Offset', (string) $pagination->getOffset())
            ->setHeader('X-Total', (string) $pagination->getTotal())
            ->setHeader('X-Next-Offset', (string) $pagination->getNextOffset())
            ->setHeader('X-Previous-Offset', (string) $pagination->getPreviousOffset());
        

        // execute collection
        $this->data = $this->data->limit($pagination->getLimit(), $pagination->getOffset());

        if ($this->data instanceof Selection || $this->data instanceof Collection) {
            foreach ($this->order as $column => $desc) {
                $this->data = $this->data->order($column . ($desc ? ' DESC' : ' ASC'));
            }
        }

        // serialize response
        $results = [];
        $items = $this->data;
        assert(is_iterable($items));    // collection implementations are traversable

        foreach ($items as $item) {
            $results[] = $this->serialize($item);
        }

        return $results;
    }

    public function pagination(): Pagination
    {
        $original = clone $this->getData();
        $total = ($original instanceof Selection) ? $original->count('*') : $original->count();

        return new Pagination($this->getLimit(), $this->offset, $total);
    }
}