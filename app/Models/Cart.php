<?php

namespace App\Models;

class Cart
{
    /** @param CartItem[] $items */
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly array $items = [],
    ) {}

    public function total(): float
    {
        return round(array_sum(array_map(fn(CartItem $i) => $i->subtotal(), $this->items)), 2);
    }

    public function toArray(): array
    {
        return [
            'id'    => $this->id,
            'items' => array_map(fn(CartItem $i) => $i->toArray(), $this->items),
            'total' => $this->total(),
        ];
    }
}
