<?php

namespace App\Services;

use App\DTOs\Auth\LoginDTO;
use App\DTOs\Auth\RegisterDTO;
use App\Exceptions\AuthException;
use App\Models\User;
use App\Repositories\UserRepository;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    private string $key;
    private string $algo = 'HS256';

    public function __construct(
        private readonly UserRepository $userRepository
    ) {
        $this->key = config('app.jwt_secret', 'secret123');
    }

    public function register(RegisterDTO $dto): array
    {
        if ($this->userRepository->findByEmail($dto->email)) {
            throw new AuthException("L'e-mail '{$dto->email}' est deja utilise.", 409);
        }

        $userId = $this->userRepository->create([
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => Hash::make($dto->password),
            'role' => 'user',
        ]);

        $user = $this->userRepository->findById($userId);
        $token = $this->generateToken($user);

        return [
            'user' => $user->toPublicArray(),
            'token' => $token
        ];
    }

    public function login(LoginDTO $dto): array
    {
        $user = $this->userRepository->findByEmail($dto->email);

        if (!$user || !Hash::check($dto->password, $user->password)) {
            throw new AuthException('Identifiants invalides.');
        }

        $token = $this->generateToken($user);

        return [
            'user' => $user->toPublicArray(),
            'token' => $token
        ];
    }

    public function validateToken(string $token): User
    {
        try {
            $decoded = JWT::decode($token, new Key($this->key, $this->algo));
            $user = $this->userRepository->findById((int) $decoded->sub);

            if (!$user) {
                throw new AuthException('Utilisateur introuvable.');
            }

            return $user;
        } catch (\Exception $e) {
            throw new AuthException('Jeton invalide ou expire.');
        }
    }

    private function generateToken(User $user): string
    {
        $payload = [
            'iss' => config('app.url'),
            'sub' => $user->id,
            'iat' => time(),
            'exp' => time() + (60 * 60 * 24), // 24 hours
        ];

        return JWT::encode($payload, $this->key, $this->algo);
    }
}
