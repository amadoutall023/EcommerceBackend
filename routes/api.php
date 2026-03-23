<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Cart\CartController;
use App\Http\Controllers\Order\OrderController;
use App\Http\Controllers\Product\ProductController;
use App\Http\Controllers\SubscriberController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

Route::middleware(['rate.limit'])->group(function () {

    // Public routes (or semi-public)
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{id}', [ProductController::class, 'show']);
    Route::get('categories', [ProductController::class, 'categories']);

    // Newsletter subscription
    Route::post('subscribe', [SubscriberController::class, 'subscribe']);
    Route::post('checkout/guest', [CartController::class, 'guestCheckout']);

    // Authenticated routes
    Route::middleware(['auth.jwt'])->group(function () {

        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::prefix('cart')->group(function () {
            Route::get('/', [CartController::class, 'show']);
            Route::post('add', [CartController::class, 'add']);
            Route::put('update', [CartController::class, 'update']);
            Route::delete('remove/{item_id}', [CartController::class, 'remove']);
            Route::post('checkout', [CartController::class, 'checkout']);
        });

        Route::prefix('orders')->group(function () {
            Route::get('/', [OrderController::class, 'index']);
            Route::get('{id}', [OrderController::class, 'show']);
        });

        // Admin restricted routes
        Route::middleware(['admin'])->prefix('admin')->group(function () {
            Route::get('stats', [OrderController::class, 'adminStats']);
            Route::get('orders', [OrderController::class, 'adminAll']);
            Route::put('orders/{id}/status', [OrderController::class, 'adminUpdateStatus']);

            Route::apiResource('products', ProductController::class);

            Route::post('categories', [ProductController::class, 'storeCategory']);
            Route::put('categories/{id}', [ProductController::class, 'updateCategory']);
            Route::delete('categories/{id}', [ProductController::class, 'destroyCategory']);

            // Subscribers
            Route::get('subscribers', [SubscriberController::class, 'index']);
        });
    });
});
