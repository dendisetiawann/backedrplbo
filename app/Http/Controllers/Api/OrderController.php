<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Xendit\Configuration;
use Xendit\Invoice\CreateInvoiceRequest;
use Xendit\Invoice\InvoiceApi;

class OrderController extends Controller
{
    public function publicMenus()
    {
        return response()->json(
            Menu::with('category')
                ->where('is_visible', true)
                ->orderBy('name')
                ->get()
        );
    }

    public function publicStore(Request $request)
    {
        if (! $request->filled('customer_name') || ! $request->filled('table_number') || empty($request->input('items'))) {
            return response()->json([
                'message' => 'Nama, nomor meja, dan pesanan tidak boleh kosong.',
            ], 422);
        }

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'table_number' => ['required', 'string', 'max:50'],
            'customer_note' => ['nullable', 'string'],
            'payment_method' => ['required', Rule::in(['cash', 'qris'])],
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_id' => ['required', 'integer', 'exists:menus,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.note' => ['nullable', 'string'],
        ]);

        $checkoutUrl = null;

        $order = DB::transaction(function () use ($validated, &$checkoutUrl) {
            $items = collect($validated['items']);
            $menuMap = Menu::whereIn('id', $items->pluck('menu_id'))
                ->get()
                ->keyBy('id');

            $totalAmount = 0;
            $preparedItems = [];

            foreach ($items as $item) {
                $menu = $menuMap->get($item['menu_id']);

                if (! $menu || ! $menu->is_visible) {
                    throw ValidationException::withMessages([
                        'items' => 'Menu tidak tersedia.',
                    ]);
                }

                $qty = $item['qty'];
                $subtotal = $menu->price * $qty;
                $totalAmount += $subtotal;

                $preparedItems[] = [
                    'menu_id' => $menu->id,
                    'qty' => $qty,
                    'price' => $menu->price,
                    'subtotal' => $subtotal,
                    'note' => $item['note'] ?? null,
                ];
            }

            $order = Order::create([
                'customer_name' => $validated['customer_name'],
                'table_number' => $validated['table_number'],
                'customer_note' => $validated['customer_note'] ?? null,
                'total_amount' => $totalAmount,
                'payment_method' => $validated['payment_method'],
                'payment_status' => $validated['payment_method'] === 'cash' ? 'belum_bayar' : 'pending',
                'order_status' => 'baru',
            ]);

            $order->items()->createMany($preparedItems);

            if ($validated['payment_method'] === 'qris') {
                Configuration::setXenditKey(config('services.xendit.secret_key'));
                $invoiceApi = new InvoiceApi();

                $externalId = 'KEJORA-' . $order->id . '-' . Str::upper(Str::random(6));
                $requestBody = new CreateInvoiceRequest([
                    'external_id' => $externalId,
                    'description' => 'KejoraCash Order #' . $order->id,
                    'amount' => $totalAmount,
                    'currency' => 'IDR',
                    'success_redirect_url' => config('services.xendit.success_url'),
                    'failure_redirect_url' => config('services.xendit.failure_url'),
                ]);

                $invoice = $invoiceApi->createInvoice($requestBody);

                $order->update([
                    'snap_token' => $invoice->getInvoiceUrl(),
                    'midtrans_order_id' => $externalId,
                ]);

                $checkoutUrl = $invoice->getInvoiceUrl();
            }

            return $order->fresh('items.menu');
        });

        return response()->json([
            'message' => $order->payment_method === 'cash'
                ? 'Pesanan diterima, silakan bayar di kasir dengan menunjukkan nomor pesanan anda.'
                : 'Pesanan berhasil dibuat. Lanjutkan pembayaran QRIS.',
            'order' => $order,
            'snap_token' => $checkoutUrl,
        ], 201);
    }

    public function publicShow(Order $order)
    {
        return response()->json($order->load('items.menu'));
    }

    public function index(Request $request)
    {
        $orders = Order::with('items.menu')
            ->when($request->query('order_status'), fn ($query, $status) => $query->where('order_status', $status))
            ->when($request->query('payment_status'), fn ($query, $status) => $query->where('payment_status', $status))
            ->orderByDesc('created_at')
            ->get();

        return response()->json($orders);
    }

    public function show(Order $order)
    {
        return response()->json($order->load('items.menu'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'order_status' => ['required', Rule::in(['baru', 'diproses', 'selesai'])],
        ]);

        $order->update(['order_status' => $validated['order_status']]);

        return response()->json([
            'message' => 'Status pesanan diperbarui.',
            'order' => $order->load('items.menu'),
        ]);
    }

    public function updatePaymentStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'payment_status' => ['required', Rule::in(['belum_bayar', 'pending', 'dibayar', 'gagal'])],
        ]);

        $order->update(['payment_status' => $validated['payment_status']]);

        return response()->json([
            'message' => 'Status pembayaran diperbarui.',
            'order' => $order->load('items.menu'),
        ]);
    }

}

