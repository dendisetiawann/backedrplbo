<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Midtrans\Config;
use Midtrans\Notification;

class MidtransController extends Controller
{
    public function handleNotification(Request $request)
    {
        $this->initMidtrans();

        try {
            $notification = new Notification();
        } catch (\Exception $exception) {
            return response()->json([
                'message' => 'Payload Midtrans tidak valid.',
            ], 400);
        }

        $order = Order::where('midtrans_order_id', $notification->order_id)->first();

        if (! $order) {
            return response()->json([
                'message' => 'Order tidak ditemukan.',
            ], 404);
        }

        $transactionStatus = $notification->transaction_status;
        $fraudStatus = $notification->fraud_status;

        $paymentStatus = match ($transactionStatus) {
            'capture' => $fraudStatus === 'accept' ? 'dibayar' : 'pending',
            'settlement' => 'dibayar',
            'pending' => 'pending',
            'deny', 'cancel', 'expire' => 'gagal',
            default => $order->payment_status,
        };

        $order->update(['payment_status' => $paymentStatus]);

        return response()->json([
            'message' => 'Notifikasi Midtrans diproses.',
            'order' => $order,
        ]);
    }

    protected function initMidtrans(): void
    {
        Config::$serverKey = config('services.midtrans.server_key');
        Config::$isProduction = (bool) config('services.midtrans.is_production', false);
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }
}
