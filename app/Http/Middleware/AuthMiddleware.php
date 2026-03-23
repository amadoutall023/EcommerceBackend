<?php

namespace App\Http\Middleware;

use App\Services\AuthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthMiddleware
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization');

        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return response()->json(['error' => 'Non autorise', 'message' => 'Jeton manquant ou format invalide.'], 401);
        }

        $token = str_replace('Bearer ', '', $header);
        
        try {
            $user = $this->authService->validateToken($token);
            $request->attributes->set('user', $user);
            return $next($request);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Non autorise', 'message' => $e->getMessage()], 401);
        }
    }
}
