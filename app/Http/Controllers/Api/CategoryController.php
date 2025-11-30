<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        return response()->json(Kategori::orderBy('nama_kategori')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama_kategori' => [
                'required',
                'string',
                'max:100',
                Rule::unique('kategori', 'nama_kategori')->whereNull('tanggal_dihapus'),
            ],
        ], [
            'nama_kategori.required' => 'Nama kategori tidak boleh kosong.',
            'nama_kategori.unique' => 'Nama kategori sudah digunakan.',
        ]);

        $kategori = Kategori::create($data);

        return response()->json($kategori, 201);
    }

    public function show(Kategori $kategori)
    {
        return response()->json($kategori);
    }

    public function update(Request $request, Kategori $kategori)
    {
        $data = $request->validate([
            'nama_kategori' => [
                'required',
                'string',
                'max:100',
                Rule::unique('kategori', 'nama_kategori')
                    ->ignore($kategori->id_kategori, 'id_kategori')
                    ->whereNull('tanggal_dihapus'),
            ],
        ], [
            'nama_kategori.required' => 'Nama kategori tidak boleh kosong.',
            'nama_kategori.unique' => 'Nama kategori sudah digunakan.',
        ]);

        $kategori->update($data);

        return response()->json($kategori);
    }

    public function destroy(Kategori $kategori)
    {
        $hasActiveMenu = $kategori->menu()
            ->where('status_visibilitas', true)
            ->exists();

        if ($hasActiveMenu) {
            return response()->json([
                'message' => 'Kategori tidak dapat dihapus karena masih digunakan oleh menu aktif.',
            ], 422);
        }

        DB::transaction(function () use ($kategori) {
            $kategori->menu()->get()->each->delete();
            $kategori->delete();
        });

        return response()->json([
            'message' => 'Kategori berhasil dihapus.',
        ]);
    }
}
