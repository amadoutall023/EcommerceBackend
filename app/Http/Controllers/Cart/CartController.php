<?php

namespace App\Http\Controllers\Cart;

use App\DTOs\Cart\AddToCartDTO;
use App\DTOs\Cart\UpdateCartDTO;
use App\DTOs\Order\GuestCheckoutDTO;
use App\Http\Controllers\Controller;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly OrderService $orderService
    ) {}

    public function show(Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');
        $cart = $this->cartService->getCart($user->id);

        return response()->json(['data' => $cart]);
    }

    public function add(Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');
        $dto = new AddToCartDTO($request->all());
        
        $this->cartService->addItem($user->id, $dto);

        return response()->json(['message' => 'Article ajoute au panier.']);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');
        $dto = new UpdateCartDTO($request->all());
        
        $this->cartService->updateItem($user->id, $dto);

        return response()->json(['message' => 'Quantite du panier mise a jour.']);
    }

    public function remove(Request $request, int $itemId): JsonResponse
    {
        $user = $request->attributes->get('user');
        $this->cartService->removeItem($user->id, $itemId);

        return response()->json(['message' => 'Article retire du panier.']);
    }

    public function checkout(Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');
        $order = $this->orderService->checkout($user->id);

        return response()->json([
            'message' => 'Commande enregistree avec succes.',
            'data'    => $order
        ], 201);
    }

    public function guestCheckout(Request $request): JsonResponse
    {
        $dto = new GuestCheckoutDTO($request->all());
        $order = $this->orderService->guestCheckout($dto);

        return response()->json([
            'message' => 'Commande enregistree avec succes.',
            'data' => $order,
        ], 201);
    }
}
