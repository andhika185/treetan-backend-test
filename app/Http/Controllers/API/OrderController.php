<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function checkout(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cart' => 'required|array',
            'cart.*.product_id' => 'required|integer|exists:products,id',
            'cart.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $cartItems = $request->cart;
        $user = $request->user();

        try {
            $order = DB::transaction(function () use ($cartItems, $user) {
                $totalAmount = 0;

                // Langkah 1: Validasi stok dan hitung total harga
                foreach ($cartItems as $item) {
                    $product = Product::find($item['product_id']);
                    if ($product->stock < $item['quantity']) {
                        // Jika stok tidak cukup, lemparkan exception untuk membatalkan transaksi
                        throw ValidationException::withMessages([
                            'cart' => 'Stock for product ' . $product->name . ' is not sufficient.'
                        ]);
                    }
                    $totalAmount += $product->price * $item['quantity'];
                }

                // Langkah 2: Buat Order
                $order = Order::create([
                    'user_id' => $user->id,
                    'total_amount' => $totalAmount,
                    'status' => 'pending',
                ]);

                // Langkah 3: Buat Order Items dan kurangi stok produk
                foreach ($cartItems as $item) {
                    $product = Product::find($item['product_id']);
                    $order->items()->create([
                        'product_id' => $product->id,
                        'quantity' => $item['quantity'],
                        'price' => $product->price, // Simpan harga saat ini
                    ]);

                    // Kurangi stok
                    $product->decrement('stock', $item['quantity']);
                }

                return $order;
            });

            // Muat relasi items untuk ditampilkan di response
            $order->load('items.product');

            return response()->json([
                'message' => 'Checkout successful, order is pending payment.',
                'data' => $order
            ], 201);

        } catch (ValidationException $e) {
            // Tangani error validasi (misal: stok tidak cukup)
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            // Tangani error lainnya
            return response()->json(['message' => 'An unexpected error occurred. ' . $e->getMessage()], 500);
        }
    }
}
