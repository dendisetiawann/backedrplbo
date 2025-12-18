<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;

class ItemPesanan extends Model
{
    use HasFactory;

    protected $table = 'itempesanan';

    protected $primaryKey = 'id_itempesanan';

    public $timestamps = false;

    protected $fillable = [
        'id_pesanan',
        'id_menu',
        'quantity',
        'harga_itempesanan',
        'subtotal',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'harga_itempesanan' => 'integer',
        'subtotal' => 'integer',
    ];

    public function pesanan(): BelongsTo
    {
        return $this->belongsTo(Pesanan::class, 'id_pesanan', 'id_pesanan');
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'id_menu', 'id_menu')->withTrashed();
    }

    public static function simpanDataItemPesanan(array $attributes): self
    {
        $data = Arr::only($attributes, [
            'id_pesanan',
            'id_menu',
            'quantity',
            'harga_itempesanan',
            'subtotal',
        ]);

        return static::create($data);
    }

    public static function ambilDataItemPesanan(int|string $id): ?self
    {
        return static::with('menu')->find($id);
    }
}
