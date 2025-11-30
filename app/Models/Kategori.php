<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kategori extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'kategori';

    protected $primaryKey = 'id_kategori';

    public function getRouteKeyName(): string
    {
        return 'id_kategori';
    }

    const CREATED_AT = 'tanggal_dibuat';
    const UPDATED_AT = 'tanggal_diubah';
    const DELETED_AT = 'tanggal_dihapus';

    protected $fillable = [
        'nama_kategori',
        'jumlah_menu',
    ];

    public function menu(): HasMany
    {
        return $this->hasMany(Menu::class, 'id_kategori', 'id_kategori');
    }
}
