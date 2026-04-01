<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Services\CloudinaryService;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['category_id', 'search', 'include_out_of_stock']);
        $catalog = $this->productService->getCatalog($filters);

        return response()->json(['data' => $catalog]);
    }

    public function show(int $id): JsonResponse
    {
        $product = $this->productService->getProductDetails($id);

        return response()->json(['data' => $product]);
    }

    public function categories(): JsonResponse
    {
        $categories = $this->productService->getAllCategories();

        return response()->json(['data' => $categories]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string',
            'category_id' => 'required|integer|exists:categories,id',
            'price' => 'required|numeric',
            'original_price' => 'nullable|numeric',
            'stock' => 'required|integer',
            'description' => 'nullable|string',
            'sizes' => 'nullable|json',
            'colors' => 'nullable|json',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $data['image_url'] = $this->uploadImage(
                $request->file('image'),
                'site-ta-trend/products'
            );
        }

        unset($data['image']);

        $product = $this->productService->createProduct($data);
        return response()->json(['data' => $product], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
            'category_id' => 'sometimes|integer|exists:categories,id',
            'price' => 'sometimes|numeric',
            'original_price' => 'nullable|numeric',
            'stock' => 'sometimes|integer',
            'description' => 'nullable|string',
            'sizes' => 'nullable|json',
            'colors' => 'nullable|json',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $data['image_url'] = $this->uploadImage(
                $request->file('image'),
                'site-ta-trend/products'
            );
        }

        unset($data['image']);

        $product = $this->productService->updateProduct($id, $data);
        return response()->json(['data' => $product]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->productService->deleteProduct($id);
        return response()->json(['message' => 'Produit supprime']);
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $data['image_url'] = $this->uploadImage(
                $request->file('image'),
                'site-ta-trend/categories'
            );
        }

        unset($data['image']);

        $category = $this->productService->createCategory($data);
        return response()->json(['data' => $category], 201);
    }

    public function updateCategory(Request $request, int $id): JsonResponse
    {
        // For updates with potentially large files or different methods, 
        // using _method=PUT with POST is often safer in Laravel if FormData is involved.
        $data = $request->validate([
            'name' => 'sometimes|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $data['image_url'] = $this->uploadImage(
                $request->file('image'),
                'site-ta-trend/categories'
            );
        }

        unset($data['image']);

        $category = $this->productService->updateCategory($id, $data);
        return response()->json(['data' => $category]);
    }

    public function destroyCategory(int $id): JsonResponse
    {
        $this->productService->deleteCategory($id);
        return response()->json(['message' => 'Category deleted']);
    }

    private function uploadImage(UploadedFile $file, string $folder): string
    {
        return app(CloudinaryService::class)->uploadImage($file, $folder);
    }
}
