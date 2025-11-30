<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemPesanan extends Model
{
    use HasFactory;

    protected $table = 'itempesanan';

    protected $primaryKey = 'id_itempesanan';

    const CREATED_AT = 'tanggal_dibuat';
    const UPDATED_AT = 'tanggal_diubah';

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
}
