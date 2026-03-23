<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');
        $orders = $this->orderService->getUserOrders($user->id);

        return response()->json(['data' => $orders]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->attributes->get('user');
        $order = $this->orderService->getOrderDetails($id, $user->id);

        return response()->json(['data' => $order]);
    }

    public function adminAll(): JsonResponse
    {
        $orders = $this->orderService->getAllOrders();
        return response()->json(['data' => $orders]);
    }

    public function adminUpdateStatus(Request $request, int $id): JsonResponse
    {
        $status = $request->input('status');
        if (!$status) {
            return response()->json(['message' => 'Status is required'], 422);
        }
        $result = $this->orderService->updateOrderStatus($id, $status);
        return response()->json($result);
    }

    public function adminStats(): JsonResponse
    {
        $stats = $this->orderService->getAdminStats();
        return response()->json(['data' => $stats]);
    }
}
