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
        $xenditCallbackToken = $request->header('x-callback-token');
        if ($xenditCallbackToken !== config('services.xendit.webhook_token')) {
            return response()->json(['message' => 'Invalid webhook token'], 403);
        }

        $payload = $request->all();

        if (isset($payload['status']) && $payload['status'] === 'PAID') {
            $externalIdParts = explode('-', $payload['external_id']);
            $orderId = $externalIdParts[1];

            $order = Order::find($orderId);

            if ($order && $order->status === 'pending') {
                $order->status = 'paid';
                $order->save();
            }
        }

        return response()->json(['message' => 'Webhook received successfully']);
    }
}