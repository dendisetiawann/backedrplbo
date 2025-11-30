<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pembayaran extends Model
{
    use HasFactory;

    protected $table = 'pembayaran';

    protected $primaryKey = 'id_pembayaran';

    const CREATED_AT = 'tanggal_dibuat';
    const UPDATED_AT = 'tanggal_diubah';

    protected $fillable = [
        'id_pesanan',
        'metode_pembayaran',
        'status_pembayaran',
        'jumlah_pembayaran',
        'token_pembayaran',
        'id_transaksi_qris',
        'waktu_dibayar',
    ];

    protected $casts = [
        'jumlah_pembayaran' => 'integer',
        'waktu_dibayar' => 'datetime',
    ];

    protected $touches = ['pesanan'];

    public function pesanan(): BelongsTo
    {
        return $this->belongsTo(Pesanan::class, 'id_pesanan', 'id_pesanan');
    }
}
