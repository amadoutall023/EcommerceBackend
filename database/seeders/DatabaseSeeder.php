<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin & User
        DB::table('users')->insert([
            [
                'name' => 'Admin User',
                'email' => 'admin@mail.com',
                'phone' => '770000001',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'created_at' => now(),
            ],
            [
                'name' => 'Test User',
                'email' => 'user@mail.com',
                'phone' => '770000002',
                'password' => Hash::make('user123'),
                'role' => 'user',
                'created_at' => now(),
            ]
        ]);

        // Categories
        $catIds = [];
        $categories = [
            ['name' => 'T-Shirts', 'image' => 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?q=80&w=800'],
            ['name' => 'Jeans', 'image' => 'https://images.unsplash.com/photo-1542272605-24c47bc86e24?q=80&w=800'],
            ['name' => 'Jackets', 'image' => 'https://images.unsplash.com/photo-1551028719-00167b16eac5?q=80&w=800'],
            ['name' => 'Shoes', 'image' => 'https://images.unsplash.com/photo-1552346154-21d32810aba3?q=80&w=800'],
        ];

        foreach ($categories as $cat) {
            $catIds[] = DB::table('categories')->insertGetId([
                'name' => $cat['name'],
                'slug' => Str::slug($cat['name']),
                'image_url' => $cat['image'],
                'created_at' => now(),
            ]);
        }

        // Products
        $products = [
            [
                'cat' => 0, 
                'name' => 'Classic Black T-Shirt', 
                'price' => 19.99, 
                'original_price' => 29.99,
                'stock' => 100, 
                'image' => 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?q=80&w=800',
                'images' => json_encode([
                    'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?q=80&w=800',
                    'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?q=80&w=800'
                ]),
                'sizes' => json_encode(['S', 'M', 'L', 'XL']),
                'colors' => json_encode(['Noir', 'Blanc', 'Bleu'])
            ],
            [
                'cat' => 0, 
                'name' => 'White Essential Tee', 
                'price' => 15.50, 
                'original_price' => 22.00,
                'stock' => 50, 
                'image' => 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?q=80&w=800',
                'images' => json_encode([
                    'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?q=80&w=800',
                    'https://images.unsplash.com/photo-1618354691373-d851c5c3a990?q=80&w=800'
                ]),
                'sizes' => json_encode(['XS', 'S', 'M', 'L']),
                'colors' => json_encode(['Blanc', 'Gris'])
            ],
            [
                'cat' => 1, 
                'name' => 'Slim Fit Blue Jeans', 
                'price' => 49.90, 
                'original_price' => 69.90,
                'stock' => 30, 
                'image' => 'https://images.unsplash.com/photo-1542272605-24c47bc86e24?q=80&w=800',
                'images' => json_encode([
                    'https://images.unsplash.com/photo-1542272605-24c47bc86e24?q=80&w=800',
                    'https://images.unsplash.com/photo-1475178626620-a4d074967452?q=80&w=800'
                ]),
                'sizes' => json_encode(['28', '30', '32', '34', '36']),
                'colors' => json_encode(['Bleu', 'Noir'])
            ],
            [
                'cat' => 2, 
                'name' => 'Leather Biker Jacket', 
                'price' => 129.00, 
                'original_price' => 199.00,
                'stock' => 10, 
                'image' => 'https://images.unsplash.com/photo-1551028719-00167b16eac5?q=80&w=800',
                'images' => json_encode([
                    'https://images.unsplash.com/photo-1551028719-00167b16eac5?q=80&w=800',
                    'https://images.unsplash.com/photo-1551028719-00167b16eac5?q=80&w=800'
                ]),
                'sizes' => json_encode(['S', 'M', 'L', 'XL']),
                'colors' => json_encode(['Noir', 'Marron'])
            ],
            [
                'cat' => 3, 
                'name' => 'Urban Street Sneakers', 
                'price' => 85.00, 
                'original_price' => 120.00,
                'stock' => 25, 
                'image' => 'https://images.unsplash.com/photo-1552346154-21d32810aba3?q=80&w=800',
                'images' => json_encode([
                    'https://images.unsplash.com/photo-1552346154-21d32810aba3?q=80&w=800',
                    'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800'
                ]),
                'sizes' => json_encode(['38', '39', '40', '41', '42', '43', '44']),
                'colors' => json_encode(['Noir', 'Blanc', 'Rouge'])
            ],
        ];

        foreach ($products as $p) {
            DB::table('products')->insert([
                'category_id' => $catIds[$p['cat']],
                'name' => $p['name'],
                'slug' => Str::slug($p['name']),
                'price' => $p['price'],
                'original_price' => $p['original_price'] ?? null,
                'stock' => $p['stock'],
                'image_url' => $p['image'],
                'images' => $p['images'] ?? null,
                'sizes' => $p['sizes'] ?? null,
                'colors' => $p['colors'] ?? null,
                'description' => "Premium quality {$p['name']} from our latest collection.",
                'created_at' => now(),
            ]);
        }
    }
}
