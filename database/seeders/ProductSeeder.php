<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categoryIds = DB::table('categories')
            ->pluck('id', 'slug')
            ->all();

        $products = [
            [
                'category_slug' => 't-shirts',
                'name' => 'Classic Black T-Shirt',
                'price' => 19.99,
                'original_price' => 29.99,
                'stock' => 100,
                'image_url' => 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?q=80&w=800',
                'images' => json_encode([
                    'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?q=80&w=800',
                    'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?q=80&w=800',
                ]),
                'sizes' => json_encode(['S', 'M', 'L', 'XL']),
                'colors' => json_encode(['Noir', 'Blanc', 'Bleu']),
            ],
            [
                'category_slug' => 't-shirts',
                'name' => 'White Essential Tee',
                'price' => 15.50,
                'original_price' => 22.00,
                'stock' => 50,
                'image_url' => 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?q=80&w=800',
                'images' => json_encode([
                    'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?q=80&w=800',
                    'https://images.unsplash.com/photo-1618354691373-d851c5c3a990?q=80&w=800',
                ]),
                'sizes' => json_encode(['XS', 'S', 'M', 'L']),
                'colors' => json_encode(['Blanc', 'Gris']),
            ],
            [
                'category_slug' => 'jeans',
                'name' => 'Slim Fit Blue Jeans',
                'price' => 49.90,
                'original_price' => 69.90,
                'stock' => 30,
                'image_url' => 'https://images.unsplash.com/photo-1542272605-24c47bc86e24?q=80&w=800',
                'images' => json_encode([
                    'https://images.unsplash.com/photo-1542272605-24c47bc86e24?q=80&w=800',
                    'https://images.unsplash.com/photo-1475178626620-a4d074967452?q=80&w=800',
                ]),
                'sizes' => json_encode(['28', '30', '32', '34', '36']),
                'colors' => json_encode(['Bleu', 'Noir']),
            ],
            [
                'category_slug' => 'jackets',
                'name' => 'Leather Biker Jacket',
                'price' => 129.00,
                'original_price' => 199.00,
                'stock' => 10,
                'image_url' => 'https://images.unsplash.com/photo-1551028719-00167b16eac5?q=80&w=800',
                'images' => json_encode([
                    'https://images.unsplash.com/photo-1551028719-00167b16eac5?q=80&w=800',
                    'https://images.unsplash.com/photo-1551028719-00167b16eac5?q=80&w=800',
                ]),
                'sizes' => json_encode(['S', 'M', 'L', 'XL']),
                'colors' => json_encode(['Noir', 'Marron']),
            ],
            [
                'category_slug' => 'shoes',
                'name' => 'Urban Street Sneakers',
                'price' => 85.00,
                'original_price' => 120.00,
                'stock' => 25,
                'image_url' => 'https://images.unsplash.com/photo-1552346154-21d32810aba3?q=80&w=800',
                'images' => json_encode([
                    'https://images.unsplash.com/photo-1552346154-21d32810aba3?q=80&w=800',
                    'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800',
                ]),
                'sizes' => json_encode(['38', '39', '40', '41', '42', '43', '44']),
                'colors' => json_encode(['Noir', 'Blanc', 'Rouge']),
            ],
        ];

        foreach ($products as $product) {
            $slug = Str::slug($product['name']);
            $categoryId = $categoryIds[$product['category_slug']] ?? null;

            if (!$categoryId) {
                continue;
            }

            DB::table('products')->updateOrInsert(
                ['slug' => $slug],
                [
                    'category_id' => $categoryId,
                    'name' => $product['name'],
                    'description' => "Premium quality {$product['name']} from our latest collection.",
                    'price' => $product['price'],
                    'original_price' => $product['original_price'],
                    'stock' => $product['stock'],
                    'image_url' => $product['image_url'],
                    'images' => $product['images'],
                    'sizes' => $product['sizes'],
                    'colors' => $product['colors'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
