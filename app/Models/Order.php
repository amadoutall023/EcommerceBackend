<?php

namespace App\Models;

class Order
{
    /** @param OrderItem[] $items */
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly float $totalAmount,
        public readonly string $status,
        public readonly string $createdAt,
        public readonly ?string $customerName = null,
        public readonly ?string $customerPhone = null,
        public readonly array $items = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            userId: (int) $data['user_id'],
            totalAmount: (float) $data['total_amount'],
            status: $data['status'],
            createdAt: $data['created_at'] ?? '',
            customerName: $data['customer_name'] ?? null,
            customerPhone: $data['customer_phone'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id'           => $this->id,
            'user_id'      => $this->userId,
            'total_amount' => $this->totalAmount,
            'status'       => $this->status,
            'created_at'   => $this->createdAt,
            'customer_name'=> $this->customerName,
            'customer_phone'=> $this->customerPhone,
            'items'        => array_map(fn(OrderItem $i) => $i->toArray(), $this->items),
        ];
    }
}
