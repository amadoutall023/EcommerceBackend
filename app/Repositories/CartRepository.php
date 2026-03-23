<?php

namespace App\Repositories;

use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Support\Facades\DB;

class CartRepository
{
    public function findByUserId(int $userId): ?Cart
    {
        $cartData = DB::table('carts')->where('user_id', $userId)->first();
        
        if (!$cartData) {
            return null;
        }

        $itemsData = DB::table('cart_items')
            ->join('products', 'cart_items.product_id', '=', 'products.id')
            ->select('cart_items.*', 'products.name as product_name', 'products.image_url as product_image')
            ->where('cart_id', $cartData->id)
            ->get();

        $items = $itemsData->map(fn($item) => CartItem::fromArray((array) $item))->toArray();

        return new Cart(
            id: $cartData->id,
            userId: $cartData->user_id,
            items: $items
        );
    }

    public function createForUser(int $userId): int
    {
        return DB::table('carts')->insertGetId([
            'user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function addItem(int $cartId, int $productId, int $quantity, float $unitPrice, ?string $size = null, ?string $color = null): void
    {
        $existing = DB::table('cart_items')
            ->where('cart_id', $cartId)
            ->where('product_id', $productId)
            ->where('selected_size', $size)
            ->where('selected_color', $color)
            ->first();

        if ($existing) {
            DB::table('cart_items')
                ->where('id', $existing->id)
                ->increment('quantity', $quantity, ['updated_at' => now()]);
        } else {
            DB::table('cart_items')->insert([
                'cart_id'        => $cartId,
                'product_id'     => $productId,
                'quantity'       => $quantity,
                'unit_price'     => $unitPrice,
                'selected_size'  => $size,
                'selected_color' => $color,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }
    }

    public function updateItemQuantity(int $itemId, int $quantity): void
    {
        DB::table('cart_items')
            ->where('id', $itemId)
            ->update(['quantity' => $quantity, 'updated_at' => now()]);
    }

    public function removeItem(int $itemId): void
    {
        DB::table('cart_items')->where('id', $itemId)->delete();
    }

    public function clearCart(int $cartId): void
    {
        DB::table('cart_items')->where('cart_id', $cartId)->delete();
    }
}
