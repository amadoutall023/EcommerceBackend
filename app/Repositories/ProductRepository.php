<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ProductRepository
{
    private string $table = 'products';

    public function all(array $filters = []): array
    {
        $query = DB::table($this->table)
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->select('products.*', 'categories.name as category_name');

        if (empty($filters['include_out_of_stock'])) {
            $query->where('products.stock', '>', 0);
        }

        if (!empty($filters['category_id'])) {
            $query->where('products.category_id', $filters['category_id']);
        }

        if (!empty($filters['search'])) {
            $query->where('products.name', 'ILIKE', '%' . $filters['search'] . '%');
        }

        return $query->get()
            ->map(fn($item) => Product::fromArray((array) $item))
            ->toArray();
    }

    public function findById(int $id): ?Product
    {
        $data = DB::table($this->table)
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->select('products.*', 'categories.name as category_name')
            ->where('products.id', $id)
            ->where('products.stock', '>', 0)
            ->first();

        return $data ? Product::fromArray((array) $data) : null;
    }

    public function updateStock(int $productId, int $quantity): bool
    {
        return DB::table($this->table)
            ->where('id', $productId)
            ->decrement('stock', $quantity);
    }

    public function create(array $data): int
    {
        return DB::table($this->table)->insertGetId(array_merge($data, [
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    public function update(int $id, array $data): bool
    {
        return DB::table($this->table)
            ->where('id', $id)
            ->update(array_merge($data, ['updated_at' => now()])) > 0;
    }

    public function delete(int $id): bool
    {
        return DB::table($this->table)->where('id', $id)->delete() > 0;
    }
}
