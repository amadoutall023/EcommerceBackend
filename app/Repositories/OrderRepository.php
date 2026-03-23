<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

class OrderRepository
{
    public function create(int $userId, float $totalAmount, array $items): int
    {
        return DB::transaction(function () use ($userId, $totalAmount, $items) {
            $orderId = DB::table('orders')->insertGetId([
                'user_id' => $userId,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($items as $item) {
                DB::table('order_items')->insert([
                    'order_id'       => $orderId,
                    'product_id'     => $item['product_id'],
                    'quantity'       => $item['quantity'],
                    'unit_price'     => $item['unit_price'],
                    'selected_size'  => $item['selected_size'] ?? null,
                    'selected_color' => $item['selected_color'] ?? null,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }

            return $orderId;
        });
    }

    public function findByUserId(int $userId): array
    {
        return DB::table('orders')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->select('orders.*', 'users.name as customer_name', 'users.phone as customer_phone')
            ->where('orders.user_id', $userId)
            ->orderBy('orders.created_at', 'desc')
            ->get()
            ->map(fn($item) => $this->hydrateOrder($item))
            ->toArray();
    }

    public function findById(int $id, int $userId): ?Order
    {
        $orderData = DB::table('orders')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->select('orders.*', 'users.name as customer_name', 'users.phone as customer_phone')
            ->where('orders.id', $id)
            ->where('orders.user_id', $userId)
            ->first();

        if (!$orderData) {
            return null;
        }

        return $this->hydrateOrder($orderData);
    }

    public function findAll(): array
    {
        return DB::table('orders')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->select('orders.*', 'users.name as customer_name', 'users.phone as customer_phone')
            ->orderBy('orders.created_at', 'desc')
            ->get()
            ->map(fn($item) => $this->hydrateOrder($item))
            ->toArray();
    }

    public function updateStatus(int $id, string $status): bool
    {
        return DB::table('orders')
            ->where('id', $id)
            ->update([
                'status' => $status,
                'updated_at' => now(),
            ]) > 0;
    }

    public function getStats(): array
    {
        return [
            'total_sales' => (float) DB::table('orders')->where('status', '!=', 'cancelled')->sum('total_amount'),
            'orders_count' => DB::table('orders')->count(),
            'pending_orders' => DB::table('orders')->where('status', 'pending')->count(),
            'total_users' => DB::table('users')->where('role', 'user')->count(),
        ];
    }

    private function hydrateOrder($orderData): Order
    {
        $itemsData = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->select('order_items.*', 'products.name as product_name')
            ->where('order_id', $orderData->id)
            ->get();

        $items = $itemsData->map(fn($item) => OrderItem::fromArray((array) $item))->toArray();

        return new Order(
            id: $orderData->id,
            userId: $orderData->user_id,
            totalAmount: (float) $orderData->total_amount,
            status: $orderData->status,
            createdAt: $orderData->created_at,
            customerName: $orderData->customer_name ?? null,
            customerPhone: $orderData->customer_phone ?? null,
            items: $items
        );
    }
}
