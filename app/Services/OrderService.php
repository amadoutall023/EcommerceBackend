<?php

namespace App\Services;

use App\DTOs\Order\GuestCheckoutDTO;
use App\Exceptions\ValidationException;
use App\Repositories\CartRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OrderService
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly CartRepository $cartRepository,
        private readonly ProductRepository $productRepository,
        private readonly UserRepository $userRepository
    ) {}

    public function checkout(int $userId): array
    {
        $cart = $this->cartRepository->findByUserId($userId);

        if (!$cart || empty($cart->items)) {
            throw new ValidationException(['cart' => 'Votre panier est vide.']);
        }

        return DB::transaction(function () use ($userId, $cart) {
            $orderItems = $this->prepareOrderItems(array_map(
                fn($item) => [
                    'product_id' => $item->productId,
                    'quantity' => $item->quantity,
                    'selected_size' => $item->selectedSize,
                    'selected_color' => $item->selectedColor,
                    'product_name' => $item->productName,
                ],
                $cart->items
            ));

            $orderId = $this->orderRepository->create($userId, $cart->total(), $orderItems);
            $this->cartRepository->clearCart($cart->id);

            $order = $this->orderRepository->findById($orderId, $userId);
            return $order->toArray();
        });
    }

    public function guestCheckout(GuestCheckoutDTO $dto): array
    {
        return DB::transaction(function () use ($dto) {
            $user = $this->resolveGuestCustomer($dto);
            $orderItems = $this->prepareOrderItems($dto->items);
            $totalAmount = round(array_sum(array_map(
                fn($item) => $item['unit_price'] * $item['quantity'],
                $orderItems
            )), 2);

            $orderId = $this->orderRepository->create($user->id, $totalAmount, $orderItems);
            $order = $this->orderRepository->findById($orderId, $user->id);

            return $order->toArray();
        });
    }

    public function getUserOrders(int $userId): array
    {
        return array_map(fn($o) => $o->toArray(), $this->orderRepository->findByUserId($userId));
    }

    public function getOrderDetails(int $id, int $userId): array
    {
        $order = $this->orderRepository->findById($id, $userId);

        if (!$order) {
            throw new \App\Exceptions\NotFoundException('Commande');
        }

        return $order->toArray();
    }

    public function getAllOrders(): array
    {
        return array_map(fn($o) => $o->toArray(), $this->orderRepository->findAll());
    }

    public function updateOrderStatus(int $id, string $status): array
    {
        $success = $this->orderRepository->updateStatus($id, $status);
        if (!$success) {
            throw new \App\Exceptions\NotFoundException('Commande');
        }
        return ['message' => 'Statut de la commande mis a jour avec succes'];
    }

    public function getAdminStats(): array
    {
        return $this->orderRepository->getStats();
    }

    private function prepareOrderItems(array $items): array
    {
        $orderItems = [];

        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);
            $product = $this->productRepository->findById($productId);

            if (!$product) {
                throw new ValidationException(['product' => 'Un article du panier est introuvable.']);
            }

            if ($quantity <= 0) {
                throw new ValidationException(['quantity' => "La quantite de '{$product->name}' est invalide."]);
            }

            if ($product->stock < $quantity) {
                throw new ValidationException(['product' => "Le produit '{$product->name}' est en rupture de stock."]);
            }

            $this->productRepository->updateStock($product->id, $quantity);

            $orderItems[] = [
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => $product->price,
                'selected_size' => $item['selected_size'] ?? null,
                'selected_color' => $item['selected_color'] ?? null,
            ];
        }

        return $orderItems;
    }

    private function resolveGuestCustomer(GuestCheckoutDTO $dto): object
    {
        $normalizedPhone = $this->normalizePhone($dto->phone);
        $existingUser = $this->userRepository->findByPhone($normalizedPhone);

        if ($dto->isFirstOrder) {
            if ($existingUser) {
                $this->userRepository->update($existingUser->id, ['name' => $dto->name]);
                return $this->userRepository->findById($existingUser->id);
            }

            $userId = $this->userRepository->create([
                'name' => $dto->name,
                'email' => sprintf('guest_%s@tatrend.local', Str::uuid()->toString()),
                'phone' => $normalizedPhone,
                'password' => Hash::make(Str::random(32)),
                'role' => 'user',
            ]);

            return $this->userRepository->findById($userId);
        }

        if (!$existingUser) {
            throw new ValidationException([
                'phone' => 'Aucun client n\'a ete trouve avec ce numero. Choisissez "Premiere commande" pour creer votre fiche.',
            ]);
        }

        return $existingUser;
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\s+/', '', trim($phone));
    }
}
