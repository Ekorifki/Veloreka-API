<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    /**
     * GET /api/admin/dashboard
     * Hitung data riil dari database MySQL menggunakan Eloquent.
     */
    public function dashboard(): JsonResponse
    {
        $usersCount = User::count();
        $productsCount = Product::count();
        $ordersCount = Order::count();
        $totalRevenue = (float) Order::sum('total_amount');

        $topProducts = Product::orderBy('id', 'desc')->take(4)->get();
        $recentOrders = Order::with(['user', 'orderItems.product'])
            ->latest()
            ->take(5)
            ->get();

        return response()->json([
            'success' => true,
            'stats' => [
                'users_count' => $usersCount,
                'products_count' => $productsCount,
                'orders_count' => $ordersCount,
                'total_revenue' => $totalRevenue,
            ],
            'top_products' => $topProducts,
            'recent_orders' => $recentOrders,
        ]);
    }

    /**
     * GET /api/admin/users
     * Kembalikan seluruh data pengguna dari tabel users.
     */
    public function users(): JsonResponse
    {
        $users = User::withCount('orders')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'count' => User::count(),
            'data' => $users,
        ]);
    }

    /**
     * PUT /api/admin/users/{id}
     */
    public function updateUser(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'role' => ['required', 'string', 'in:admin,user,customer'],
            'password' => ['nullable', 'string', 'min:3'],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role = $validated['role'];

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();
        $user->loadCount('orders');

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => $user,
        ]);
    }

    /**
     * DELETE /api/admin/users/{id}
     */
    public function deleteUser(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully',
        ]);
    }

    /**
     * GET /api/admin/products
     * Kembalikan seluruh data katalog produk dari tabel products.
     */
    public function products(): JsonResponse
    {
        $products = Product::orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'count' => Product::count(),
            'data' => $products,
        ]);
    }

    /**
     * POST /api/admin/products
     */
    public function storeProduct(Request $request): JsonResponse
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

    /**
     * PUT /api/admin/products/{id}
     */
    public function updateProduct(Request $request, int $id): JsonResponse
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

    /**
     * DELETE /api/admin/products/{id}
     */
    public function deleteProduct(int $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully',
        ]);
    }

    /**
     * GET /api/admin/orders
     * Kembalikan seluruh pesanan beserta relasi user dan orderItems.product.
     */
    public function orders(): JsonResponse
    {
        $orders = Order::with(['user', 'orderItems.product'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'count' => Order::count(),
            'data' => $orders,
        ]);
    }

    /**
     * PUT /api/admin/orders/{id}/status
     */
    public function updateOrderStatus(Request $request, int $id): JsonResponse
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
