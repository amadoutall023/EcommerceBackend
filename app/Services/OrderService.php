<?php

namespace App\Services;

use App\DTOs\Order\GuestCheckoutDTO;
use App\Exceptions\ValidationException;
use App\Models\User;
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
        private readonly UserRepository $userRepository,
        private readonly OrderNotificationService $orderNotificationService
    ) {}

    public function checkout(int $userId): array
    {
        $cart = $this->cartRepository->findByUserId($userId);

        if (!$cart || empty($cart->items)) {
            throw new ValidationException(['cart' => 'Votre panier est vide.']);
        }

        $result = DB::transaction(function () use ($userId, $cart) {
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
            $customer = $this->userRepository->findById($userId);

            return [
                'order' => $order->toArray(),
                'customer' => $customer,
            ];
        });

        if ($result['customer']) {
            $this->dispatchOrderNotification($result['order'], $result['customer']);
        }

        return $result['order'];
    }

    public function guestCheckout(GuestCheckoutDTO $dto): array
    {
        $result = DB::transaction(function () use ($dto) {
            $user = $this->resolveGuestCustomer($dto);
            $orderItems = $this->prepareOrderItems($dto->items);
            $totalAmount = round(array_sum(array_map(
                fn($item) => $item['unit_price'] * $item['quantity'],
                $orderItems
            )), 2);

            $orderId = $this->orderRepository->create($user->id, $totalAmount, $orderItems);
            $order = $this->orderRepository->findById($orderId, $user->id);

            return [
                'order' => $order->toArray(),
                'customer' => $user,
            ];
        });

        $this->dispatchOrderNotification($result['order'], $result['customer']);

        return $result['order'];
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

    public function deleteOrder(int $id): array
    {
        $order = $this->orderRepository->findAnyById($id);

        if (!$order) {
            throw new \App\Exceptions\NotFoundException('Commande');
        }

        DB::transaction(function () use ($order, $id) {
            foreach ($order->items as $item) {
                if ($item->productId !== null) {
                    $this->productRepository->restoreStock($item->productId, $item->quantity);
                }
            }

            $deleted = $this->orderRepository->delete($id);

            if (!$deleted) {
                throw new \RuntimeException('La suppression de la commande a echoue.');
            }
        });

        return ['message' => 'Commande supprimee avec succes'];
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
                'product_name' => $product->name,
                'product_image' => $product->imageUrl,
                'quantity' => $quantity,
                'unit_price' => $product->price,
                'selected_size' => $item['selected_size'] ?? null,
                'selected_color' => $item['selected_color'] ?? null,
            ];
        }

        return $orderItems;
    }

    private function resolveGuestCustomer(GuestCheckoutDTO $dto): User
    {
        $normalizedPhone = $this->normalizePhone($dto->phone);
        $existingUser = $this->userRepository->findByPhone($normalizedPhone);

        if ($dto->isFirstOrder) {
            if ($existingUser) {
                throw new ValidationException([
                    'phone' => 'Ce numero existe deja. Choisissez "Non" si vous avez deja commande avec ce numero.',
                ]);
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
                'phone' => 'Aucun client n\'a ete trouve avec ce numero. Choisissez "Oui" si c\'est votre premiere commande.',
            ]);
        }

        return $existingUser;
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\s+/', '', trim($phone));
    }

    private function dispatchOrderNotification(array $order, User $customer): void
    {
        app()->terminating(function () use ($order, $customer) {
            $this->orderNotificationService->sendNewOrderNotification($order, $customer);
        });
    }
}
