<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class Kategori extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'kategori';

    protected $primaryKey = 'id_kategori';

    public function getRouteKeyName(): string
    {
        return 'id_kategori';
    }

    public $timestamps = false;
    const DELETED_AT = 'tanggal_dihapus';

    protected $fillable = [
        'nama_kategori',
        'jumlah_menu',
    ];

    public function menu(): HasMany
    {
        return $this->hasMany(Menu::class, 'id_kategori', 'id_kategori');
    }

    public static function ambilDataKategori(?int $kategoriId = null)
    {
        $query = self::withCount('menu as jumlah_menu')
            ->orderBy('nama_kategori');

        if ($kategoriId !== null) {
            return $query->where('id_kategori', $kategoriId)->first();
        }

        return $query->get();
    }

    public static function validasiInputanKategori(array $data, ?int $kategoriId = null): array
    {
        $uniqueRule = Rule::unique('kategori', 'nama_kategori')->whereNull('tanggal_dihapus');

        if ($kategoriId !== null) {
            $uniqueRule->ignore($kategoriId, 'id_kategori');
        }

        return Validator::make($data, [
            'nama_kategori' => [
                'required',
                'string',
                'max:100',
                $uniqueRule,
            ],
        ], [
            'nama_kategori.required' => 'Nama kategori tidak boleh kosong.',
            'nama_kategori.unique' => 'Nama kategori sudah digunakan.',
        ])->validate();
    }

    public static function tambahKategori(array $data): self
    {
        return self::create($data);
    }

    public static function editKategori(self $kategori, array $data): self
    {
        $kategori->update($data);

        return $kategori->fresh();
    }

    public static function hapusKategori(self $kategori): bool
    {
        $hasActiveMenu = $kategori->menu()
            ->where('status_visibilitas', true)
            ->exists();

        if ($hasActiveMenu) {
            return false;
        }

        DB::transaction(function () use ($kategori) {
            $kategori->menu()->get()->each->delete();
            $kategori->delete();
        });

        return true;
    }
}
