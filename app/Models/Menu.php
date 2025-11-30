<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Menu extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'menu';

    protected $primaryKey = 'id_menu';

    public function getRouteKeyName(): string
    {
        return 'id_menu';
    }

    const CREATED_AT = 'tanggal_dibuat';
    const UPDATED_AT = 'tanggal_diubah';
    const DELETED_AT = 'tanggal_dihapus';

    protected $fillable = [
        'id_kategori',
        'nama_menu',
        'deskripsi_menu',
        'harga_menu',
        'foto_menu',
        'status_visibilitas',
    ];

    protected $casts = [
        'harga_menu' => 'integer',
        'status_visibilitas' => 'boolean',
    ];

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class, 'id_kategori', 'id_kategori');
    }

    public function itemPesanan(): HasMany
    {
        return $this->hasMany(ItemPesanan::class, 'id_menu', 'id_menu');
    }
}
