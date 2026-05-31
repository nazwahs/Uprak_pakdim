<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = $request->user()->orders()->with('items.product')->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar pesanan berhasil diambil.',
            'data' => $orders,
        ], 200);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'notes' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|integer|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $e->errors(),
            ], 422);
        }

        try {
            $order = DB::transaction(function () use ($request, $validated) {
                $totalPrice = 0;
                $orderItems = [];

                foreach ($validated['items'] as $item) {
                    $product = Product::lockForUpdate()->find($item['product_id']);

                    if ($product->stock < $item['quantity']) {
                        throw new \Exception("Stok produk {$product->name} tidak mencukupi. Sisa stok: {$product->stock}");
                    }

                    $product->decrement('stock', $item['quantity']);

                    $unitPrice = $product->price;
                    $totalPrice += $unitPrice * $item['quantity'];

                    $orderItems[] = [
                        'product_id' => $product->id,
                        'quantity' => $item['quantity'],
                        'unit_price' => $unitPrice,
                    ];
                }

                $order = Order::create([
                    'user_id' => $request->user()->id,
                    'total_price' => $totalPrice,
                    'status' => 'pending',
                    'notes' => $validated['notes'] ?? null,
                ]);

                foreach ($orderItems as $orderItem) {
                    $order->items()->create($orderItem);
                }

                return $order->load('items.product');
            });

            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil dibuat.',
                'data' => $order,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => [],
            ], 400);
        }
    }

    public function show(Request $request, string $id)
    {
        $order = Order::with('items.product')->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan.',
                'errors' => [],
            ], 404);
        }

        if ($order->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke pesanan ini.',
                'errors' => [],
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail pesanan berhasil diambil.',
            'data' => $order,
        ], 200);
    }

    public function updateStatus(Request $request, string $id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan.',
                'errors' => [],
            ], 404);
        }

        try {
            $validated = $request->validate([
                'status' => 'required|string|in:pending,processing,done,cancelled',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $e->errors(),
            ], 422);
        }

        $order->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => 'Status pesanan berhasil diperbarui.',
            'data' => $order,
        ], 200);
    }
}
