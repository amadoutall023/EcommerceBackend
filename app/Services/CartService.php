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

        $cart = $this->cartRepository->findByUserId($userId);
        $cartId = $cart ? $cart->id : $this->cartRepository->createForUser($userId);
        $existingQuantity = 0;

        if ($cart) {
            foreach ($cart->items as $item) {
                if (
                    $item->productId === $product->id
                    && $item->selectedSize === $dto->selectedSize
                    && $item->selectedColor === $dto->selectedColor
                ) {
                    $existingQuantity = $item->quantity;
                    break;
                }
            }
        }

        if ($product->stock < ($existingQuantity + $dto->quantity)) {
            throw new ValidationException(['quantity' => 'Stock insuffisant.']);
        }

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

        $targetItem = null;
        foreach ($cart->items as $item) {
            if ($item->id === $dto->itemId) {
                $targetItem = $item;
                break;
            }
        }

        if (!$targetItem) {
            throw new NotFoundException('Article du panier');
        }

        $product = $this->productRepository->findById($targetItem->productId);
        if ($product && $product->stock < $dto->quantity) {
            throw new ValidationException(['quantity' => 'Stock insuffisant pour la quantite ' . $dto->quantity . '.']);
        }

        $nextSize = $dto->selectedSize ?? $targetItem->selectedSize;
        $nextColor = $dto->selectedColor ?? $targetItem->selectedColor;

        $matchingItem = null;
        foreach ($cart->items as $item) {
            if (
                $item->id !== $targetItem->id
                && $item->productId === $targetItem->productId
                && $item->selectedSize === $nextSize
                && $item->selectedColor === $nextColor
            ) {
                $matchingItem = $item;
                break;
            }
        }

        if ($matchingItem) {
            $mergedQuantity = $matchingItem->quantity + $dto->quantity;
            if ($product && $product->stock < $mergedQuantity) {
                throw new ValidationException(['quantity' => 'Stock insuffisant pour fusionner ces variantes.']);
            }

            $this->cartRepository->incrementItemQuantity($matchingItem->id, $dto->quantity);
            $this->cartRepository->removeItem($cart->id, $targetItem->id);
            return;
        }

        $this->cartRepository->updateItem($dto->itemId, $dto->quantity, $nextSize, $nextColor);
    }

    public function removeItem(int $userId, int $itemId): void
    {
        $cart = $this->cartRepository->findByUserId($userId);
        
        if (!$cart) {
            throw new NotFoundException('Panier');
        }

        $deleted = $this->cartRepository->removeItem($cart->id, $itemId);

        if ($deleted === 0) {
            throw new NotFoundException('Article du panier');
        }
    }
}
