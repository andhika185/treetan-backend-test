<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Mengambil semua data produk dan mengembalikannya sebagai JSON
        $products = Product::all();
        return response()->json(['data' => $products]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Membuat produk baru
        $product = Product::create($request->all());

        // Mengembalikan data produk yang baru dibuat dengan status 201 Created
        return response()->json(['data' => $product], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        // Laravel secara otomatis akan mencari produk berdasarkan ID dari URL
        // Jika tidak ditemukan, akan otomatis mengembalikan response 404 Not Found
        // Ini adalah keajaiban dari Route Model Binding (--model=Product)
        return response()->json(['data' => $product]);
    }
}
