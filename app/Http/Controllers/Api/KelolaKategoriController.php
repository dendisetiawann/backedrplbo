<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use Illuminate\Http\Request;

class KelolaKategoriController extends Controller
{
    public function ambilSemuaDataKategori()
    {
        return response()->json(Kategori::ambilDataKategori());
    }

    public function store(Request $request)
    {
        $data = Kategori::validasiInputanKategori($request->all());

        $kategori = Kategori::tambahKategori($data);

        return response()->json($kategori, 201);
    }

    public function cekKategori(Kategori $kategori)
    {
        return response()->json(Kategori::ambilDataKategori($kategori->id_kategori));
    }

    public function update(Request $request, Kategori $kategori)
    {
        $data = Kategori::validasiInputanKategori($request->all(), $kategori->id_kategori);

        $updatedKategori = Kategori::editKategori($kategori, $data);

        return response()->json($updatedKategori);
    }

    public function destroy(Kategori $kategori)
    {
        if (!Kategori::hapusKategori($kategori)) {
            return response()->json([
                'message' => 'Kategori tidak dapat dihapus karena masih digunakan oleh menu aktif.',
            ], 422);
        }

        return response()->json([
            'message' => 'Kategori berhasil dihapus.',
        ]);
    }
}
