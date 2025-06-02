<?php

namespace Varhall\Restino\Results;

use Varhall\Utilino\ISerializable;

class Pagination implements ISerializable
{
    private int $limit;
    private int $offset;
    private int $total;

    public function __construct(int $limit, int $offset, int $total)
    {
        $this->limit = $limit;
        $this->offset = $offset;
        $this->total = $total;
    }


    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getNextOffset(): ?int
    {
        return ($this->offset + $this->limit < $this->total) ? ($this->offset + $this->limit) : null;
    }

    public function getPreviousOffset(): ?int
    {
        return ($this->offset - $this->limit >= 0) ? ($this->offset - $this->limit) : null;
    }


    public function toArray()
    {
        return [
            'limit'         => $this->getLimit(),
            'offset'        => [
                'current'       => $this->getOffset(),
                'next'          => $this->getNextOffset(),
                'previous'      => $this->getPreviousOffset(),
            ],
            'total'         => $this->getTotal()
        ];
    }
}