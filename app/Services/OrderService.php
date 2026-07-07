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
            $items = array_map(
                fn($item) => [
                    'product_id' => $item->productId,
                    'quantity' => $item->quantity,
                    'selected_size' => $item->selectedSize,
                    'selected_color' => $item->selectedColor,
                    'product_name' => $item->productName,
                ],
                $cart->items
            );

            $result = $this->createOrderForUser($userId, $items, $cart->total());
            $this->cartRepository->clearCart($cart->id);

            return $result;
        });

        if ($result['customer']) {
            $this->dispatchOrderNotification($result['order'], $result['customer']);
        }

        return $result['order'];
    }

    public function checkoutViaWhatsapp(int $userId): array
    {
        $cart = $this->cartRepository->findByUserId($userId);

        if (!$cart || empty($cart->items)) {
            throw new ValidationException(['cart' => 'Votre panier est vide.']);
        }

        $result = DB::transaction(function () use ($userId, $cart) {
            $items = array_map(
                fn($item) => [
                    'product_id' => $item->productId,
                    'quantity' => $item->quantity,
                    'selected_size' => $item->selectedSize,
                    'selected_color' => $item->selectedColor,
                    'product_name' => $item->productName,
                ],
                $cart->items
            );

            $result = $this->createOrderForUser($userId, $items, $cart->total());
            $this->cartRepository->clearCart($cart->id);

            return $result;
        });

        if ($result['customer']) {
            $this->dispatchOrderNotification($result['order'], $result['customer']);
        }

        return [
            'order' => $result['order'],
            'whatsapp_url' => $this->buildWhatsappUrl($result['order'], $result['customer']),
        ];
    }

    public function guestCheckout(GuestCheckoutDTO $dto): array
    {
        $result = DB::transaction(function () use ($dto) {
            $user = $this->resolveGuestCustomer($dto);
            return $this->createOrderForUser($user->id, $dto->items, null, $user);
        });

        $this->dispatchOrderNotification($result['order'], $result['customer']);

        return $result['order'];
    }

    public function guestCheckoutViaWhatsapp(GuestCheckoutDTO $dto): array
    {
        $result = DB::transaction(function () use ($dto) {
            $user = $this->resolveGuestCustomer($dto);
            return $this->createOrderForUser($user->id, $dto->items, null, $user);
        });

        $this->dispatchOrderNotification($result['order'], $result['customer']);

        return [
            'order' => $result['order'],
            'whatsapp_url' => $this->buildWhatsappUrl($result['order'], $result['customer']),
        ];
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

    private function createOrderForUser(
        int $userId,
        array $items,
        ?float $totalAmount = null,
        ?User $customer = null
    ): array {
        $orderItems = $this->prepareOrderItems($items);
        $computedTotal = round(array_sum(array_map(
            fn($item) => $item['unit_price'] * $item['quantity'],
            $orderItems
        )), 2);

        $orderId = $this->orderRepository->create($userId, $totalAmount ?? $computedTotal, $orderItems);
        $order = $this->orderRepository->findById($orderId, $userId);
        $resolvedCustomer = $customer ?? $this->userRepository->findById($userId);

        return [
            'order' => $order->toArray(),
            'customer' => $resolvedCustomer,
        ];
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

    private function buildWhatsappUrl(array $order, ?User $customer): string
    {
        $phone = $this->normalizeWhatsappNumber((string) config('services.whatsapp_orders.phone', '221784541151'));
        $customerName = trim((string) ($customer?->name ?? $order['customer_name'] ?? 'Client'));
        $customerPhone = trim((string) ($customer?->phone ?? $order['customer_phone'] ?? ''));
        $itemsSummary = array_map(function (array $item): string {
            $variant = array_filter([
                $item['selected_size'] ?? null ? 'Taille '.$item['selected_size'] : null,
                $item['selected_color'] ?? null ? 'Couleur '.$item['selected_color'] : null,
            ]);

            $suffix = empty($variant) ? '' : ' ('.implode(', ', $variant).')';

            return sprintf('- %s x%d%s', $item['product_name'] ?? 'Article', (int) $item['quantity'], $suffix);
        }, $order['items'] ?? []);

        $messageLines = array_filter([
            sprintf('Bonjour TaTrend, je confirme ma commande #ORD-%d.', (int) $order['id']),
            sprintf('Client: %s', $customerName),
            $customerPhone !== '' ? sprintf('Telephone: %s', $customerPhone) : null,
            'Articles:',
            ...$itemsSummary,
            sprintf('Total: %.0f XOF', (float) ($order['total_amount'] ?? 0)),
        ]);

        return sprintf(
            'https://wa.me/%s?text=%s',
            $phone,
            rawurlencode(implode("\n", $messageLines))
        );
    }

    private function normalizeWhatsappNumber(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone);
    }
}
