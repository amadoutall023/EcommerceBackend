<?php

namespace App\DTOs\Cart;

use App\Exceptions\ValidationException;

class UpdateCartDTO
{
    public readonly int $itemId;
    public readonly int $quantity;
    public readonly ?string $selectedSize;
    public readonly ?string $selectedColor;

    public function __construct(array $data)
    {
        $this->validate($data);
        $this->itemId   = (int) $data['item_id'];
        $this->quantity = (int) $data['quantity'];
        $this->selectedSize = $data['selected_size'] ?? null;
        $this->selectedColor = $data['selected_color'] ?? null;
    }

    private function validate(array $data): void
    {
        $errors = [];

        if (empty($data['item_id']) || ! is_numeric($data['item_id'])) {
            $errors['item_id'] = 'Un identifiant article valide est requis.';
        }

        if (! isset($data['quantity']) || (int) $data['quantity'] < 1) {
            $errors['quantity'] = 'La quantite doit etre au moins egale a 1.';
        }

        if (! empty($errors)) {
            throw new ValidationException($errors);
        }
    }
}
