<?php

namespace Varhall\Restino\Results;

use Nette\Database\Table\Selection;
use Nette\Http\IResponse;
use Varhall\Utilino\Collections\ICollection;

class CollectionResult extends AbstractResult
{
    const DEFAULT_LIMIT     = 100;
    const MAX_LIMIT         = 1000;
    const DEFAULT_OFFSET    = 0;

    protected ICollection|Selection $data;

    protected int $limit        = self::DEFAULT_LIMIT;
    protected int $offset       = self::DEFAULT_OFFSET;
    protected array $order      = [];

    public function __construct(ICollection|Selection $data)
    {
        $this->data = $data;
    }

    public static function fromResult(Result $result): static
    {
        $self = new static($result->getData());
        $self->mappers = $result->mappers;

        return $self;
    }

    public function getData(): ICollection|Selection
    {
        return $this->data;
    }

    public function getLimit(): int
    {
        return $this->limit;
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
        $this->limit = min($limit ?? self::DEFAULT_LIMIT, self::MAX_LIMIT);
        $this->offset = $offset ?? self::DEFAULT_OFFSET;
    }

    public function addOrder(string $column, bool $desc = false): void
    {
        $this->order[$column] = $desc;
    }

    public function execute(IResponse $http): mixed
    {
        // paginate first
        $pagination = $this->pagination();

        // execute collection
        $this->data = $this->data->limit($this->limit, $this->offset);

        if ($this->data instanceof Selection) {
            foreach ($this->order as $column => $desc) {
                $this->data = $this->data->order($column . ($desc ? ' DESC' : ' ASC'));
            }
        }

        // serialize response
        $results = [];

        foreach ($this->data as $item) {
            $results[] = $this->serialize($item);
        }

        return [
            'pagination'    => $pagination,
            'results'       => $results
        ];
    }

    protected function pagination(): array
    {
        $limit = $this->limit;
        $offset = $this->offset;

        $original = clone $this->getData();
        $total = ($original instanceof Selection) ? $original->count('*') : $original->count();

        return [
            'limit'         => $limit,
            'offset'        => [
                'current'       => $offset,
                'next'          => $limit && $offset < $total - $limit
                    ? ($offset + $limit)
                    : null,
                'previous'      => $limit && $offset >= $limit
                    ? $offset - $limit
                    : null,
            ],
            'total'         => $total,
        ];
    }
}