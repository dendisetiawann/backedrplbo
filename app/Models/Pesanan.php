<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Arr;

class Pesanan extends Model
{
    use HasFactory;

    protected $table = 'pesanan';

    protected $primaryKey = 'id_pesanan';

    public function getRouteKeyName(): string
    {
        return 'id_pesanan';
    }

    public $timestamps = false;

    protected $fillable = [
        'nomor_pesanan',
        'id_pelanggan',
        'total_harga',
        'status_pesanan',
    ];

    protected $casts = [
        'total_harga' => 'integer',
    ];

    protected $with = ['pembayaran', 'pelanggan'];

    protected $appends = [
        'metode_pembayaran',
        'status_pembayaran',
        'token_pembayaran',
        'id_transaksi_qris',
        'nama_pelanggan',
        'nomor_meja',
        'catatan_pelanggan',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($pesanan) {
            if (empty($pesanan->nomor_pesanan)) {
                $pesanan->nomor_pesanan = static::generateNomorPesanan();
            }
        });
    }

    public static function generateNomorPesanan(): string
    {
        $prefix = 'ORD';
        $year = date('Y');
        $monthDay = date('md');
        
        $today = date('Y-m-d');
        $lastPesanan = static::whereDate('tanggal_dibuat', $today)
            ->orderBy('id_pesanan', 'desc')
            ->first();
        
        if ($lastPesanan && $lastPesanan->nomor_pesanan) {
            $parts = explode('-', $lastPesanan->nomor_pesanan);
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
        return $this->hasMany(ItemPesanan::class, 'id_pesanan', 'id_pesanan');
    }

    public function pembayaran(): HasOne
    {
        return $this->hasOne(Pembayaran::class, 'id_pesanan', 'id_pesanan');
    }

    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class, 'id_pelanggan', 'id_pelanggan');
    }

    public function getMetodePembayaranAttribute(): ?string
    {
        return $this->pembayaran?->metode_pembayaran;
    }

    public function getStatusPembayaranAttribute(): ?string
    {
        return $this->pembayaran?->status_pembayaran;
    }

    public function getTokenPembayaranAttribute(): ?string
    {
        return $this->pembayaran?->token_pembayaran;
    }

    public function getIdTransaksiQrisAttribute(): ?string
    {
        return $this->pembayaran?->id_transaksi_qris;
    }

    public function getNamaPelangganAttribute(): ?string
    {
        return $this->pelanggan->nama_pelanggan ?? null;
    }

    public function getNomorMejaAttribute(): ?string
    {
        return $this->pelanggan->nomor_meja ?? null;
    }

    public function getCatatanPelangganAttribute(): ?string
    {
        return $this->pelanggan->catatan_pelanggan ?? null;
    }

    public static function simpanDataPesanan(array $attributes): self
    {
        $data = Arr::only($attributes, [
            'nomor_pesanan',
            'id_pelanggan',
            'total_harga',
            'status_pesanan',
        ]);

        return static::create($data);
    }

    public static function ambilDataPesanan(int|string $id): ?self
    {
        return static::with(['items.menu', 'pembayaran', 'pelanggan'])->find($id);
    }
}
