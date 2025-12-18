<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class Pembayaran extends Model
{
    use HasFactory;

    protected $table = 'pembayaran';

    protected $primaryKey = 'id_pembayaran';

    public $timestamps = false;

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

    public static function membuatPembayaran(array $attributes): self
    {
        $data = Arr::only($attributes, [
            'id_pesanan',
            'metode_pembayaran',
            'status_pembayaran',
            'jumlah_pembayaran',
            'token_pembayaran',
            'id_transaksi_qris',
            'waktu_dibayar',
        ]);

        return DB::transaction(fn () => static::create($data));
    }

    public static function ambilDataPembayaran(Pesanan $pesanan): ?self
    {
        return $pesanan->pembayaran()->first();
    }
}
