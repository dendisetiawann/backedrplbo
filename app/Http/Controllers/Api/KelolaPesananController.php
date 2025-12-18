<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pesanan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KelolaPesananController extends Controller
{
    public function index(Request $request)
    {
        $pesananList = Pesanan::with(['items.menu', 'pembayaran', 'pelanggan'])
            ->when($request->query('order_status'), fn ($query, $status) => $query->where('status_pesanan', $status))
            ->when($request->query('payment_status'), function ($query, $status) {
                $query->whereHas('pembayaran', fn ($paymentQuery) => $paymentQuery->where('status_pembayaran', $status));
            })
            ->when($request->query('start_date'), fn ($query, $date) => $query->whereDate('tanggal_dibuat', '>=', $date))
            ->when($request->query('end_date'), fn ($query, $date) => $query->whereDate('tanggal_dibuat', '<=', $date))
            ->where(function ($query) {
                $query->whereHas('pembayaran', fn ($paymentQuery) => $paymentQuery->where('metode_pembayaran', '!=', 'qris'))
                    ->orWhereHas('pembayaran', fn ($paymentQuery) => $paymentQuery->where('status_pembayaran', 'dibayar'));
            })
            ->orderByDesc('tanggal_dibuat')
            ->get();

        return response()->json($pesananList);
    }

    public function show(Pesanan $pesanan)
    {
        return response()->json($pesanan->load(['items.menu', 'pembayaran', 'pelanggan']));
    }

    public function updateStatusPesanan(Request $request, Pesanan $pesanan)
    {
        $validated = $request->validate([
            'status_pesanan' => ['required', Rule::in(['baru', 'diproses', 'selesai'])],
        ]);

        $pesanan->update(['status_pesanan' => $validated['status_pesanan']]);

        return response()->json([
            'message' => 'Status pesanan diperbarui.',
            'order' => $pesanan->load(['items.menu', 'pembayaran', 'pelanggan']),
        ]);
    }

    public function updatePaymentStatus(Request $request, Pesanan $pesanan)
    {
        $validated = $request->validate([
            'status_pembayaran' => ['required', Rule::in(['belum_bayar', 'pending', 'dibayar', 'gagal'])],
        ]);

        $pembayaran = $pesanan->pembayaran;

        if (! $pembayaran) {
            return response()->json([
                'message' => 'Data pembayaran tidak ditemukan.',
            ], 422);
        }

        $pembayaran->update([
            'status_pembayaran' => $validated['status_pembayaran'],
            'waktu_dibayar' => $validated['status_pembayaran'] === 'dibayar' ? now() : null,
        ]);

        return response()->json([
            'message' => 'Status pembayaran diperbarui.',
            'order' => $pesanan->load(['items.menu', 'pembayaran', 'pelanggan']),
        ]);
    }
}
