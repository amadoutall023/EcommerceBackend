<?php

namespace App\DTOs\Order;

use App\Exceptions\ValidationException;

class GuestCheckoutDTO
{
    public readonly bool $isFirstOrder;
    public readonly ?string $name;
    public readonly string $phone;
    public readonly array $items;

    public function __construct(array $data)
    {
        $this->validate($data);

        $this->isFirstOrder = filter_var($data['is_first_order'], FILTER_VALIDATE_BOOLEAN);
        $this->name = isset($data['name']) ? trim($data['name']) : null;
        $this->phone = trim($data['phone']);
        $this->items = $data['items'];
    }

    private function validate(array $data): void
    {
        $errors = [];

        if (!array_key_exists('is_first_order', $data)) {
            $errors['is_first_order'] = 'Le statut du client est requis.';
        }

        if (empty($data['phone'])) {
            $errors['phone'] = 'Le numero de telephone est requis.';
        }

        if (!isset($data['items']) || !is_array($data['items']) || count($data['items']) === 0) {
            $errors['items'] = 'Le panier ne peut pas etre vide.';
        }

        $isFirstOrder = filter_var($data['is_first_order'] ?? false, FILTER_VALIDATE_BOOLEAN);
        if ($isFirstOrder && empty(trim($data['name'] ?? ''))) {
            $errors['name'] = 'Le nom est requis pour une premiere commande.';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }
}
