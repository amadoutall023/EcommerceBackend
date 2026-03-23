<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'T-Shirts', 'image_url' => 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?q=80&w=800'],
            ['name' => 'Jeans', 'image_url' => 'https://images.unsplash.com/photo-1542272605-24c47bc86e24?q=80&w=800'],
            ['name' => 'Jackets', 'image_url' => 'https://images.unsplash.com/photo-1551028719-00167b16eac5?q=80&w=800'],
            ['name' => 'Shoes', 'image_url' => 'https://images.unsplash.com/photo-1552346154-21d32810aba3?q=80&w=800'],
        ];

        foreach ($categories as $category) {
            $slug = Str::slug($category['name']);

            DB::table('categories')->updateOrInsert(
                ['slug' => $slug],
                [
                    'name' => $category['name'],
                    'image_url' => $category['image_url'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
