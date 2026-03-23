<?php

namespace App\Services;

use App\DTOs\Cart\AddToCartDTO;
use App\DTOs\Cart\UpdateCartDTO;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\Cart;
use App\Repositories\CartRepository;
use App\Repositories\ProductRepository;

class CartService
{
    public function __construct(
        private readonly CartRepository $cartRepository,
        private readonly ProductRepository $productRepository
    ) {}

    public function getCart(int $userId): array
    {
        $cart = $this->cartRepository->findByUserId($userId);

        if (!$cart) {
            $cartId = $this->cartRepository->createForUser($userId);
            return [
                'id' => $cartId,
                'items' => [],
                'total' => 0
            ];
        }

        return $cart->toArray();
    }

    public function addItem(int $userId, AddToCartDTO $dto): void
    {
        $product = $this->productRepository->findById($dto->productId);

        if (!$product) {
            throw new NotFoundException('Produit');
        }

        if ($product->stock < $dto->quantity) {
            throw new ValidationException(['quantity' => 'Stock insuffisant.']);
        }

        $cart = $this->cartRepository->findByUserId($userId);
        $cartId = $cart ? $cart->id : $this->cartRepository->createForUser($userId);

        $this->cartRepository->addItem(
            $cartId, 
            $product->id, 
            $dto->quantity, 
            $product->price, 
            $dto->selectedSize, 
            $dto->selectedColor
        );
    }

    public function updateItem(int $userId, UpdateCartDTO $dto): void
    {
        $cart = $this->cartRepository->findByUserId($userId);

        if (!$cart) {
            throw new NotFoundException('Panier');
        }

        $itemExist = false;
        foreach ($cart->items as $item) {
            if ($item->id === $dto->itemId) {
                $itemExist = true;
                // Check stock for the new quantity
                $product = $this->productRepository->findById($item->productId);
                if ($product && $product->stock < $dto->quantity) {
                    throw new ValidationException(['quantity' => 'Stock insuffisant pour la quantite ' . $dto->quantity . '.']);
                }
                break;
            }
        }

        if (!$itemExist) {
            throw new NotFoundException('Article du panier');
        }

        $this->cartRepository->updateItemQuantity($dto->itemId, $dto->quantity);
    }

    public function removeItem(int $userId, int $itemId): void
    {
        $cart = $this->cartRepository->findByUserId($userId);
        
        if (!$cart) {
            throw new NotFoundException('Panier');
        }

        $this->cartRepository->removeItem($itemId);
    }
}
