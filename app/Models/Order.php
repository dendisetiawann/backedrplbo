<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'pelanggan_id',
        'total_amount',
        'order_status',
    ];

    protected $casts = [
        'total_amount' => 'integer',
    ];

    protected $with = ['payment', 'pelanggan'];

    protected $appends = [
        'payment_method',
        'payment_status',
        'snap_token',
        'qris_order_id',
        'customer_name',
        'table_number',
        'customer_note',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = static::generateOrderNumber();
            }
        });
    }

    public static function generateOrderNumber(): string
    {
        // Format: ORD-YYYY-MMDD-XXX
        $prefix = 'ORD';
        $year = date('Y');
        $monthDay = date('md'); // Format: 1122 untuk 22 November
        
        // Get last order number for today
        $today = date('Y-m-d');
        $lastOrder = static::whereDate('created_at', $today)
            ->orderBy('id', 'desc')
            ->first();
        
        if ($lastOrder && $lastOrder->order_number) {
            // Extract last sequence number
            $parts = explode('-', $lastOrder->order_number);
            $lastSequence = isset($parts[3]) ? (int)$parts[3] : 0;
            $newSequence = $lastSequence + 1;
        } else {
            $newSequence = 1;
        }
        
        $sequence = str_pad($newSequence, 3, '0', STR_PAD_LEFT);
        
        return "{$prefix}-{$year}-{$monthDay}-{$sequence}";
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class);
    }

    public function getPaymentMethodAttribute(): ?string
    {
        return $this->payment?->method;
    }

    public function getPaymentStatusAttribute(): ?string
    {
        return $this->payment?->status;
    }

    public function getSnapTokenAttribute(): ?string
    {
        return $this->payment?->snap_token;
    }

    public function getQrisOrderIdAttribute(): ?string
    {
        return $this->payment?->qris_order_id;
    }

    public function getCustomerNameAttribute(): ?string
    {
        return $this->pelanggan->name ?? null;
    }

    public function getTableNumberAttribute(): ?string
    {
        return $this->pelanggan->table_number ?? null;
    }

    public function getCustomerNoteAttribute(): ?string
    {
        return $this->pelanggan->customer_note ?? null;
    }
}
