<?php

namespace App\DTOs\Cart;

use App\Exceptions\ValidationException;

class AddToCartDTO
{
    public readonly int $productId;
    public readonly int $quantity;
    public readonly ?string $selectedSize;
    public readonly ?string $selectedColor;

    public function __construct(array $data)
    {
        $this->validate($data);
        $this->productId     = (int) $data['product_id'];
        $this->quantity      = (int) $data['quantity'];
        $this->selectedSize  = $data['selected_size'] ?? null;
        $this->selectedColor = $data['selected_color'] ?? null;
    }

    private function validate(array $data): void
    {
        $errors = [];

        if (empty($data['product_id']) || ! is_numeric($data['product_id'])) {
            $errors['product_id'] = 'Un identifiant produit valide est requis.';
        }

        if (empty($data['quantity']) || (int) $data['quantity'] < 1) {
            $errors['quantity'] = 'La quantite doit etre au moins egale a 1.';
        }

        if (! empty($errors)) {
            throw new ValidationException($errors);
        }
    }
}
