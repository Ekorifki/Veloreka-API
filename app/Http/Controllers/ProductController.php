<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of products with optional category and search filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::query();

        if ($request->filled('category') && $request->category !== 'All Categories') {
            $category = $request->category;
            $query->where(function ($q) use ($category) {
                $q->where('category', $category)
                  ->orWhere('sub_category', $category);
            });
        }

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(category) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(sub_category) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(description) LIKE ?', ["%{$search}%"]);
            });
        }

        $products = $query->orderBy('id')->get();

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    /**
     * Display a single product by ID.
     */
    public function show(int $id): JsonResponse
    {
        $product = Product::find($id);

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }
}
