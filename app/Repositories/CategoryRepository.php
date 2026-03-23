<?php

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Support\Facades\DB;

class CategoryRepository
{
    private string $table = 'categories';

    public function all(): array
    {
        return DB::table($this->table)
            ->get()
            ->map(fn($item) => Category::fromArray((array) $item))
            ->toArray();
    }

    public function findBySlug(string $slug): ?Category
    {
        $data = DB::table($this->table)->where('slug', $slug)->first();
        return $data ? Category::fromArray((array) $data) : null;
    }
    
    public function findById(int $id): ?Category
    {
        $data = DB::table($this->table)->where('id', $id)->first();
        return $data ? Category::fromArray((array) $data) : null;
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
