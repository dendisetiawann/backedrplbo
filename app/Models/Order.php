<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'customer_name',
        'table_number',
        'customer_note',
        'total_amount',
        'payment_method',
        'payment_status',
        'order_status',
        'snap_token',
        'midtrans_order_id',
    ];

    protected $casts = [
        'total_amount' => 'integer',
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
}
