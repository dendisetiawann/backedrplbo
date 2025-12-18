<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ItemPesanan;
use App\Models\Menu;
use App\Models\Pembayaran;
use App\Models\Pelanggan;
use App\Models\Pesanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Xendit\Configuration;
use Xendit\Invoice\CreateInvoiceRequest;
use Xendit\Invoice\InvoiceApi;

class MembuatPesananController extends Controller
{
    public function handleXenditCallback(Request $request)
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

        if (! $externalId) {
            return response()->json(['message' => 'Missing external_id'], 400);
        }

        $pembayaran = Pembayaran::where('id_transaksi_qris', $externalId)->first();

        if (! $pembayaran) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $pesanan = $pembayaran->pesanan;

        $statusPembayaran = match ($status) {
            'COMPLETED', 'PAID', 'SETTLED' => 'dibayar',
            'ACTIVE', 'PENDING' => 'pending',
            'FAILED', 'INACTIVE', 'EXPIRED', 'CANCELLED' => 'gagal',
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
            'message' => 'Callback processed',
            'order' => $pesanan->fresh(['items.menu', 'pembayaran']),
        ]);
    }
    public function ambilSemuaMenu()
    {
        return response()->json(
            Menu::with('kategori')
                ->where('status_visibilitas', true)
                ->orderBy('nama_menu')
                ->get()
        );
    }

    public function publicMenuShow(Menu $menu)
    {
        abort_if(! $menu->status_visibilitas, 404, 'Menu tidak tersedia.');

        return response()->json($menu->load('kategori'));
    }

    public function membuatPesanan(Request $request)
    {
        if (! $request->filled('customer_name') || ! $request->filled('table_number') || empty($request->input('items'))) {
            return response()->json([
                'message' => 'Nama, nomor meja, dan pesanan tidak boleh kosong.',
            ], 422);
        }

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:100'],
            'table_number' => ['required', 'string', 'max:10'],
            'customer_note' => ['nullable', 'string'],
            'payment_method' => ['required', Rule::in(['cash', 'qris'])],
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_id' => ['required', 'integer', 'exists:menu,id_menu'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
        ]);

        $checkoutUrl = null;

        $pesanan = DB::transaction(function () use ($validated, &$checkoutUrl) {
            $items = collect($validated['items']);
            $menuMap = Menu::whereIn('id_menu', $items->pluck('menu_id'))
                ->get()
                ->keyBy('id_menu');

            $totalHarga = 0;
            $preparedItems = [];

            foreach ($items as $item) {
                $menu = $menuMap->get($item['menu_id']);

                if (! $menu || ! $menu->status_visibilitas) {
                    throw ValidationException::withMessages([
                        'items' => 'Menu tidak tersedia.',
                    ]);
                }

                $qty = $item['qty'];
                $subtotal = $menu->harga_menu * $qty;
                $totalHarga += $subtotal;

                $preparedItems[] = [
                    'id_menu' => $menu->id_menu,
                    'quantity' => $qty,
                    'harga_itempesanan' => $menu->harga_menu,
                    'subtotal' => $subtotal,
                ];
            }

            $pelanggan = Pelanggan::firstOrCreate(
                [
                    'nama_pelanggan' => $validated['customer_name'],
                    'nomor_meja' => $validated['table_number'],
                ],
                [
                    'catatan_pelanggan' => $validated['customer_note'] ?? null,
                ]
            );

            if (! empty($validated['customer_note']) && $pelanggan->catatan_pelanggan !== $validated['customer_note']) {
                $pelanggan->update([
                    'catatan_pelanggan' => $validated['customer_note'],
                ]);
            }

            $pesanan = Pesanan::simpanDataPesanan([
                'id_pelanggan' => $pelanggan->id_pelanggan,
                'total_harga' => $totalHarga,
                'status_pesanan' => 'baru',
            ]);

            foreach ($preparedItems as $item) {
                ItemPesanan::simpanDataItemPesanan([
                    'id_pesanan' => $pesanan->id_pesanan,
                    'id_menu' => $item['id_menu'],
                    'quantity' => $item['quantity'],
                    'harga_itempesanan' => $item['harga_itempesanan'],
                    'subtotal' => $item['subtotal'],
                ]);
            }

            $pembayaran = Pembayaran::membuatPembayaran([
                'id_pesanan' => $pesanan->id_pesanan,
                'metode_pembayaran' => $validated['payment_method'],
                'status_pembayaran' => $validated['payment_method'] === 'cash' ? 'belum_bayar' : 'pending',
                'jumlah_pembayaran' => $totalHarga,
            ]);

            if ($validated['payment_method'] === 'qris') {
                Configuration::setXenditKey(config('services.xendit.secret_key'));
                $invoiceApi = new InvoiceApi();
                $merchantId = config('services.xendit.merchant_id', '9988123');

                $externalId = $pesanan->nomor_pesanan;
                $requestBody = new CreateInvoiceRequest([
                    'external_id' => $externalId,
                    'description' => 'Pembayaran Pesanan ' . $pesanan->nomor_pesanan . ' - ' . ($pesanan->nama_pelanggan ?? $pelanggan->nama_pelanggan),
                    'amount' => $totalHarga,
                    'currency' => 'IDR',
                    'success_redirect_url' => config('services.xendit.success_url'),
                    'failure_redirect_url' => config('services.xendit.failure_url'),
                    'metadata' => [
                        'merchant_id' => $merchantId,
                        'order_code' => $pesanan->nomor_pesanan,
                    ],
                ]);

                $invoice = $invoiceApi->createInvoice($requestBody);

                $pembayaran->update([
                    'token_pembayaran' => $invoice->getInvoiceUrl(),
                    'id_transaksi_qris' => $externalId,
                ]);

                $checkoutUrl = $invoice->getInvoiceUrl();
            }

            return $pesanan->fresh(['items.menu', 'pembayaran', 'pelanggan']);
        });

        return response()->json([
            'message' => $pesanan->metode_pembayaran === 'cash'
                ? 'Pesanan diterima, silakan bayar di kasir dengan menunjukkan nomor pesanan anda.'
                : 'Pesanan berhasil dibuat. Lanjutkan pembayaran QRIS.',
            'order' => $pesanan,
            'snap_token' => $checkoutUrl,
        ], 201);
    }

    public function buatPesanan(Pesanan $pesanan)
    {
        return response()->json($pesanan->load(['items.menu', 'pembayaran', 'pelanggan']));
    }

    public function verifikasiStatusPembayaran(Request $request, Pesanan $pesanan)
    {
        $pembayaran = $pesanan->pembayaran;

        if (! $pembayaran || $pembayaran->metode_pembayaran !== 'qris') {
            return response()->json([
                'message' => 'Metode pembayaran tidak mendukung pembaruan otomatis.',
            ], 422);
        }

        $paymentUpdates = [];
        $orderUpdates = [];
        $message = 'Pembayaran berhasil ditandai lunas.';

        if ($pembayaran->status_pembayaran !== 'dibayar') {
            $paymentUpdates['status_pembayaran'] = 'dibayar';
            $paymentUpdates['waktu_dibayar'] = now();
        } else {
            $message = 'Pembayaran sudah ditandai lunas.';
        }

        if ($pesanan->status_pesanan === 'baru') {
            $orderUpdates['status_pesanan'] = 'diproses';
            $message = 'Pembayaran berhasil diverifikasi dan pesanan mulai diproses.';
        }

        if (! empty($paymentUpdates)) {
            $pembayaran->update($paymentUpdates);
        }

        if (! empty($orderUpdates)) {
            $pesanan->update($orderUpdates);
        }

        return response()->json([
            'message' => $message,
            'order' => $pesanan->load(['items.menu', 'pembayaran', 'pelanggan']),
        ]);
    }

}

