<?php

namespace App\Http\Controllers\Auth;

use App\DTOs\Auth\LoginDTO;
use App\DTOs\Auth\RegisterDTO;
use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    public function register(Request $request): JsonResponse
    {
        $dto = new RegisterDTO($request->all());
        $result = $this->authService->register($dto);

        return response()->json([
            'message' => 'Inscription reussie.',
            'data'    => $result
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $dto = new LoginDTO($request->all());
        $result = $this->authService->login($dto);

        return response()->json([
            'message' => 'Connexion reussie.',
            'data'    => $result
        ]);
    }

    public function logout(): JsonResponse
    {
        // JWT is stateless, logout is usually handled client-side by deleting the token.
        // We could blacklist the token here if we had a blacklist repository.
        return response()->json(['message' => 'Deconnexion reussie.']);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');
        return response()->json(['data' => $user->toPublicArray()]);
    }
}
