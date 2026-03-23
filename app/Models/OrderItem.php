<?php

namespace App\Models;

class OrderItem
{
    public function __construct(
        public readonly int $id,
        public readonly int $orderId,
        public readonly int $productId,
        public readonly int $quantity,
        public readonly float $unitPrice,
        public readonly ?string $selectedSize = null,
        public readonly ?string $selectedColor = null,
        public readonly ?string $productName = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            orderId: (int) $data['order_id'],
            productId: (int) $data['product_id'],
            quantity: (int) $data['quantity'],
            unitPrice: (float) $data['unit_price'],
            selectedSize: $data['selected_size'] ?? null,
            selectedColor: $data['selected_color'] ?? null,
            productName: $data['product_name'] ?? null,
        );
    }

    public function subtotal(): float
    {
        return round($this->unitPrice * $this->quantity, 2);
    }

    public function toArray(): array
    {
        return [
            'id'             => $this->id,
            'product_id'     => $this->productId,
            'product_name'   => $this->productName,
            'quantity'       => $this->quantity,
            'unit_price'     => $this->unitPrice,
            'selected_size'  => $this->selectedSize,
            'selected_color' => $this->selectedColor,
            'subtotal'       => $this->subtotal(),
        ];
    }
}
