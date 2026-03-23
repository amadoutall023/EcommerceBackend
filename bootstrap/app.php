<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth.jwt'   => \App\Http\Middleware\AuthMiddleware::class,
            'admin'      => \App\Http\Middleware\AdminMiddleware::class,
            'rate.limit' => \App\Http\Middleware\RateLimitMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\App\Exceptions\ValidationException $e) {
            return response()->json([
                'error'   => 'Erreur de validation',
                'message' => $e->getMessage(),
                'details' => $e->getErrors()
            ], 422);
        });

        $exceptions->render(function (\App\Exceptions\NotFoundException $e) {
            return response()->json([
                'error'   => 'Introuvable',
                'message' => $e->getMessage()
            ], 404);
        });

        $exceptions->render(function (\App\Exceptions\AuthException $e) {
            return response()->json([
                'error'   => 'Non autorise',
                'message' => $e->getMessage()
            ], $e->getCode() ?: 401);
        });
    })->create();
