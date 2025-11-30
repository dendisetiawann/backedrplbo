<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pembayaran;
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

        $pembayaran = Pembayaran::where('id_transaksi_qris', $notification->order_id)->first();

        if (! $pembayaran) {
            return response()->json([
                'message' => 'Order tidak ditemukan.',
            ], 404);
        }

        $pesanan = $pembayaran->pesanan;

        $transactionStatus = $notification->transaction_status;
        $fraudStatus = $notification->fraud_status;

        $statusPembayaran = match ($transactionStatus) {
            'capture' => $fraudStatus === 'accept' ? 'dibayar' : 'pending',
            'settlement' => 'dibayar',
            'pending' => 'pending',
            'deny', 'cancel', 'expire' => 'gagal',
            default => $pembayaran->status_pembayaran,
        };

        $pembayaran->update([
            'status_pembayaran' => $statusPembayaran,
            'waktu_dibayar' => $statusPembayaran === 'dibayar' ? now() : null,
        ]);

        if ($statusPembayaran === 'dibayar' && $pesanan && $pesanan->status_pesanan === 'baru') {
            $pesanan->update(['status_pesanan' => 'diproses']);
        }

        return response()->json([
            'message' => 'Notifikasi Midtrans diproses.',
            'order' => $pesanan->fresh(['items.menu', 'pembayaran']),
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
