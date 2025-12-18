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

    public $timestamps = false;
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

    public static function tambahMenu(array $data): self
    {
        $menu = self::create($data);
        $menu->kategori()->increment('jumlah_menu');

        return $menu->load('kategori');
    }

    public static function editMenu(self $menu, array $data): self
    {
        $oldKategoriId = $menu->id_kategori;
        $menu->update($data);

        if ($oldKategoriId !== $menu->id_kategori) {
            Kategori::where('id_kategori', $oldKategoriId)->decrement('jumlah_menu');
            Kategori::where('id_kategori', $menu->id_kategori)->increment('jumlah_menu');
        }

        return $menu->load('kategori');
    }

    public static function hapusMenu(self $menu): void
    {
        $kategoriId = $menu->id_kategori;
        $menu->delete();

        Kategori::where('id_kategori', $kategoriId)->decrement('jumlah_menu');
    }

    public static function ubahVisibilitas(self $menu, bool $status): self
    {
        $menu->update(['status_visibilitas' => $status]);

        return $menu->fresh();
    }

    public static function ambilDataMenu(?int $menuId = null)
    {
        $query = self::with('kategori')->orderBy('nama_menu');

        if ($menuId !== null) {
            return $query->where('id_menu', $menuId)->first();
        }

        return $query->get();
    }
}
