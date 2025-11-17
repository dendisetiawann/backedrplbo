<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class XenditController extends Controller
{
    public function handleCallback(Request $request)
    {
        $callbackToken = $request->header('X-CALLBACK-TOKEN');
        $xenditToken = config('services.xendit.callback_token');

        if ($xenditToken && $callbackToken !== $xenditToken) {
            Log::warning('Xendit callback token mismatch', ['provided' => $callbackToken]);
            return response()->json(['message' => 'Invalid callback token'], 403);
        }

        $payload = $request->all();
        Log::info('Xendit Callback:', $payload);

        $externalId = $payload['external_id'] ?? null;
        $status = $payload['status'] ?? ($payload['payment_status'] ?? null);

        if (!$externalId) {
            return response()->json(['message' => 'Missing external_id'], 400);
        }

        $order = Order::where('midtrans_order_id', $externalId)->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $paymentStatus = match ($status) {
            'COMPLETED', 'PAID', 'SETTLED' => 'dibayar',
            'ACTIVE', 'PENDING' => 'pending',
            'FAILED', 'INACTIVE', 'EXPIRED', 'CANCELLED' => 'gagal',
            default => $order->payment_status,
        };

        $order->update(['payment_status' => $paymentStatus]);

        return response()->json([
            'message' => 'Callback processed',
            'order' => $order,
        ]);
    }
}
