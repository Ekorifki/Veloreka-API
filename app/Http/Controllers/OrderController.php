<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index(): JsonResponse
    {
        $orders = Order::with(['orderItems.product'])
            ->where('user_id', Auth::id())
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    public function checkout(Request $request): JsonResponse
    {
        $userId = Auth::id();

        $dbCartItems = Cart::with('product')
            ->where('user_id', $userId)
            ->get();

        $payloadItems = $request->input('items', []);

        if ($dbCartItems->isEmpty() && empty($payloadItems)) {
            return response()->json([
                'success' => false,
                'message' => 'Keranjang belanja masih kosong.',
            ], 422);
        }

        return DB::transaction(function () use ($request, $userId, $dbCartItems, $payloadItems) {
            $subTotal = 0;
            $preparedItems = [];

            if ($dbCartItems->isNotEmpty()) {
                foreach ($dbCartItems as $cartRow) {
                    $product = $cartRow->product;
                    if (! $product) {
                        continue;
                    }
                    $price = (float) $product->price;
                    $qty = (int) $cartRow->quantity;
                    $lineTotal = round($price * $qty, 2);
                    $subTotal += $lineTotal;

                    $preparedItems[] = [
                        'product_id' => $product->id,
                        'size' => $cartRow->size ?? 'L',
                        'quantity' => $qty,
                        'price' => $price,
                        'sub_total' => $lineTotal,
                    ];
                }
            } else {
                foreach ($payloadItems as $item) {
                    $productId = $item['product_id'] ?? ($item['product']['id'] ?? null);
                    $product = $productId ? Product::find($productId) : null;
                    if (! $product) {
                        continue;
                    }
                    $price = (float) $product->price;
                    $qty = max(1, (int) ($item['quantity'] ?? 1));
                    $lineTotal = round($price * $qty, 2);
                    $subTotal += $lineTotal;

                    $preparedItems[] = [
                        'product_id' => $product->id,
                        'size' => $item['size'] ?? 'L',
                        'quantity' => $qty,
                        'price' => $price,
                        'sub_total' => $lineTotal,
                    ];
                }
            }

            if (empty($preparedItems)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada produk valid untuk diproses.',
                ], 422);
            }

            $shippingFee = (float) $request->input('shipping_fee', 80.00);
            $promoCode = $request->input('promo_code');
            $discountAmount = (float) $request->input(
                'discount_amount',
                $promoCode ? round($subTotal * 0.30, 2) : 0.00
            );
            $totalAmount = max(0, round($subTotal + $shippingFee - $discountAmount, 2));

            $order = Order::create([
                'user_id' => $userId,
                'order_number' => 'VLR-' . strtoupper(Str::random(8)),
                'delivery_nickname' => $request->input('delivery_nickname', 'Home'),
                'delivery_address' => $request->input('delivery_address', 'DI. PandjaitanMok'),
                'payment_method' => $request->input('payment_method', 'card'),
                'sub_total' => $subTotal,
                'shipping_fee' => $shippingFee,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'promo_code' => $promoCode,
                'status' => 'Pending',
            ]);

            foreach ($preparedItems as $line) {
                OrderItem::create([
                    'order_id' => $order->id,
                    ...$line,
                ]);
            }

            Cart::where('user_id', $userId)->delete();

            $order->load(['user', 'orderItems.product']);

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully',
                'data' => $order,
            ], 201);
        });
    }

    public function adminIndex(): JsonResponse
    {
        $orders = Order::with(['user', 'orderItems.product'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'max:50'],
        ]);

        $order = Order::findOrFail($id);
        $order->status = $validated['status'];
        $order->save();
        $order->load(['user', 'orderItems.product']);

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully',
            'data' => $order,
        ]);
    }
}
