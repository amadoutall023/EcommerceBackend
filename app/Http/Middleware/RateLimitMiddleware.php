<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = 'api-limit:' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 60)) {
            return response()->json([
                'error' => 'Too Many Requests',
                'message' => 'Please wait before trying again.'
            ], 429);
        }

        RateLimiter::hit($key, 60);

        return $next($request);
    }
}
