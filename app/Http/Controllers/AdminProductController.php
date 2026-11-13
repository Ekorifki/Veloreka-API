<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminProductController extends Controller
{
    public function index(): JsonResponse
    {
        $products = Product::orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'fit_title' => ['nullable', 'string', 'max:100'],
            'category' => ['required', 'string', 'max:100'],
            'sub_category' => ['nullable', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'old_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'image' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'sizes' => ['nullable', 'array'],
            'tags' => ['nullable', 'array'],
        ]);

        $product = Product::create([
            'name' => $validated['name'],
            'fit_title' => $validated['fit_title'] ?? 'Regular Fit',
            'category' => $validated['category'],
            'sub_category' => $validated['sub_category'] ?? 'Apparel',
            'price' => $validated['price'],
            'old_price' => $validated['old_price'] ?? null,
            'stock' => $validated['stock'] ?? 50,
            'image' => $validated['image'],
            'gallery' => [$validated['image']],
            'sizes' => $validated['sizes'] ?? ['S', 'M', 'L', 'XL'],
            'description' => $validated['description'] ?? '',
            'tags' => $validated['tags'] ?? [strtolower($validated['category']), 'y2k', 'streetwear'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'data' => $product,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'fit_title' => ['nullable', 'string', 'max:100'],
            'category' => ['required', 'string', 'max:100'],
            'sub_category' => ['nullable', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'old_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'image' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'sizes' => ['nullable', 'array'],
        ]);

        $product->update([
            'name' => $validated['name'],
            'fit_title' => $validated['fit_title'] ?? $product->fit_title,
            'category' => $validated['category'],
            'sub_category' => $validated['sub_category'] ?? $product->sub_category,
            'price' => $validated['price'],
            'old_price' => $validated['old_price'] ?? null,
            'stock' => $validated['stock'] ?? $product->stock,
            'image' => $validated['image'],
            'sizes' => $validated['sizes'] ?? $product->sizes ?? ['S', 'M', 'L', 'XL'],
            'description' => $validated['description'] ?? $product->description,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'data' => $product,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully',
        ]);
    }
}
