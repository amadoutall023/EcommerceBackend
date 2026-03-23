<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserRepository
{
    private string $table = 'users';

    public function findByEmail(string $email): ?User
    {
        $data = DB::table($this->table)->where('email', $email)->first();
        
        return $data ? User::fromArray((array) $data) : null;
    }

    public function findById(int $id): ?User
    {
        $data = DB::table($this->table)->where('id', $id)->first();
        
        return $data ? User::fromArray((array) $data) : null;
    }

    public function findByPhone(string $phone): ?User
    {
        $data = DB::table($this->table)->where('phone', $phone)->first();

        return $data ? User::fromArray((array) $data) : null;
    }

    public function create(array $data): int
    {
        $data['created_at'] = now();
        $data['updated_at'] = now();
        
        return DB::table($this->table)->insertGetId($data);
    }

    public function update(int $id, array $data): void
    {
        $data['updated_at'] = now();

        DB::table($this->table)->where('id', $id)->update($data);
    }
}
