<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@mail.com',
                'phone' => '770000001',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
            ],
            [
                'name' => 'Test User',
                'email' => 'user@mail.com',
                'phone' => '770000002',
                'password' => Hash::make('user123'),
                'role' => 'user',
            ],
        ];

        foreach ($users as $user) {
            DB::table('users')->updateOrInsert(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'phone' => $user['phone'],
                    'password' => $user['password'],
                    'role' => $user['role'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
