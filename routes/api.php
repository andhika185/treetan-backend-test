<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\API\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Route ini TIDAK dilindungi oleh secret key, karena dipanggil oleh Xendit.
Route::post('/payment/webhook', [PaymentController::class, 'handleWebhook']);


// Semua route di dalam grup ini WAJIB memiliki header X-Secret-Key.
Route::group(['middleware' => 'secretkey'], function () {

    // Rute publik yang tetap butuh secret key
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // Rute yang butuh secret key DAN token autentikasi user (Sanctum)
    Route::group(['middleware' => 'auth:sanctum'], function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', function (Request $request) {
            return $request->user();
        });

        Route::apiResource('products', ProductController::class)->only(['index', 'show', 'store']);
        Route::post('/checkout', [OrderController::class, 'checkout']);
        Route::post('/orders/{order}/pay', [PaymentController::class, 'createInvoice']);
    });
});
