<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request; // <-- Pastikan ini ada
use Xendit\Configuration;
use Xendit\Invoice\InvoiceApi;

class PaymentController extends Controller
{
    public function __construct()
    {
        Configuration::setXenditKey(config('services.xendit.api_key'));
    }

    /**
     * @param Request $request
     * @param Order $order
     * @return \Illuminate\Http\JsonResponse
     */
    public function createInvoice(Request $request, Order $order) // <-- PERUBAHAN DI SINI
    {
        // Security check: Pastikan order milik user yang sedang login
        if ($order->user_id !== $request->user()->id) { // <-- PERUBAHAN DI SINI
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Pastikan order belum dibayar
        if ($order->status !== 'pending') {
            return response()->json(['message' => 'This order has already been processed.'], 422);
        }

        $apiInstance = new InvoiceApi();

        $params = [
            'external_id' => 'order-' . $order->id . '-' . time(), // ID unik untuk invoice
            'amount' => $order->total_amount,
            'payer_email' => $request->user()->email, // <-- PERUBAHAN DI SINI
            'description' => 'Payment for Order #' . $order->id,
            'success_redirect_url' => 'https://your-frontend.com/payment-success',
            'failure_redirect_url' => 'https://your-frontend.com/payment-failed',
        ];

        try {
            $result = $apiInstance->createInvoice($params);

            return response()->json($result);
        } catch (\Xendit\XenditSdkException $e) {
            return response()->json([
                'message' => 'Failed to create invoice.',
                'error' => $e->getMessage(),
                'full_error' => $e->getFullError()
            ], 500);
        }
    }

    public function handleWebhook(Request $request)
    {
        // 1. Verifikasi token dari header
        $xenditCallbackToken = $request->header('x-callback-token');
        if ($xenditCallbackToken !== config('services.xendit.webhook_token')) {
            return response()->json(['message' => 'Invalid webhook token'], 403);
        }

        $payload = $request->all();

        // 2. Cek apakah status 'PAID' dan external_id ada
        if (isset($payload['status']) && $payload['status'] === 'PAID' && isset($payload['external_id'])) {
            
            // 3. VALIDASI FORMAT: Pastikan external_id dimulai dengan 'order-'
            // Ini akan mengabaikan payload dari tombol "Test" atau invoice lain yang tidak relevan
            if (str_starts_with($payload['external_id'], 'order-')) {
                
                $externalIdParts = explode('-', $payload['external_id']);
                
                // 4. VALIDASI JUMLAH BAGIAN: Pastikan hasil explode valid
                if (count($externalIdParts) >= 2) {
                    $orderId = $externalIdParts[1];
                    $order = Order::find($orderId);

                    // 5. Update status order jika ditemukan dan masih pending
                    if ($order && $order->status === 'pending') {
                        $order->status = 'paid';
                        $order->save();
                    }
                }
            }
        }

        // 6. Selalu kirim response 200 OK ke Xendit agar tidak dianggap gagal
        return response()->json(['message' => 'Webhook received successfully.']);
    }
}