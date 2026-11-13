<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function index(): JsonResponse
    {
        $items = Cart::with('product')
            ->where('user_id', Auth::id())
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'size' => ['nullable', 'string', 'max:10'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $userId = Auth::id();
        $productId = (int) $validated['product_id'];
        $size = $validated['size'] ?? 'L';
        $quantity = (int) ($validated['quantity'] ?? 1);

        $cartItem = Cart::where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('size', $size)
            ->first();

        if ($cartItem) {
            $cartItem->quantity += $quantity;
            $cartItem->save();
        } else {
            $cartItem = Cart::create([
                'user_id' => $userId,
                'product_id' => $productId,
                'size' => $size,
                'quantity' => $quantity,
            ]);
        }

        $cartItem->load('product');

        return response()->json([
            'success' => true,
            'message' => 'Item added to cart',
            'data' => $cartItem,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $cartItem = Cart::where('user_id', Auth::id())->findOrFail($id);
        $cartItem->quantity = (int) $validated['quantity'];
        $cartItem->save();
        $cartItem->load('product');

        return response()->json([
            'success' => true,
            'message' => 'Cart quantity updated',
            'data' => $cartItem,
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $userId = Auth::id();

        $cartItem = Cart::where('user_id', $userId)->find($id);

        if (! $cartItem) {
            $fallback = Cart::where('user_id', $userId)->where('product_id', $id);
            if ($request->filled('size')) {
                $fallback->where('size', $request->query('size'));
            }
            $cartItem = $fallback->firstOrFail();
        }

        $cartItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item removed from cart',
        ]);
    }

    public function clear(): JsonResponse
    {
        Cart::where('user_id', Auth::id())->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cart cleared',
        ]);
    }
}
