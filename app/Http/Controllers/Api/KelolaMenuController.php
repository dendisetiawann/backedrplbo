<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KelolaMenuController extends Controller
{
    public function ambilSemuaMenu()
    {
        return response()->json(Menu::ambilDataMenu());
    }

    public function store(Request $request)
    {
        $data = $this->validasiInputanKelolaMenu($request);
        $menu = Menu::tambahMenu($data);

        return response()->json($menu, 201);
    }

    public function cekMenu(Menu $menu)
    {
        return response()->json(Menu::ambilDataMenu($menu->id_menu));
    }

    public function update(Request $request, Menu $menu)
    {
        $data = $this->validasiInputanKelolaMenu($request, $menu->id_menu);
        $updatedMenu = Menu::editMenu($menu, $data);

        return response()->json($updatedMenu);
    }

    public function destroy(Menu $menu)
    {
        Menu::hapusMenu($menu);

        return response()->json([
            'message' => 'Menu berhasil dihapus.',
        ]);
    }

    private function validasiInputanKelolaMenu(Request $request, ?int $menuId = null): array
    {
        $rules = [
            'id_kategori' => ['required', 'exists:kategori,id_kategori'],
            'nama_menu' => [
                'required',
                'string',
                'max:100',
                Rule::unique('menu', 'nama_menu')
                    ->ignore($menuId, 'id_menu')
                    ->whereNull('tanggal_dihapus'),
            ],
            'deskripsi_menu' => ['nullable', 'string'],
            'harga_menu' => ['required', 'numeric', 'min:1'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:10240'], // Max 10MB
            'foto_menu' => ['nullable', 'string'],
            'status_visibilitas' => ['required'],
        ];

        $data = $request->validate($rules, [
            'nama_menu.unique' => 'Nama menu sudah digunakan.',
            'harga_menu.min' => 'Harga harus lebih dari 0.',
            'foto.max' => 'Ukuran foto maksimal 10MB.',
            'foto.mimes' => 'Format foto harus JPG atau PNG.',
        ]);

        if ($request->hasFile('foto')) {
            $path = $request->file('foto')->store('menu_photos', 'public');
            $data['foto_menu'] = $path;
            unset($data['foto']);
        } else {
            // Keep existing foto_menu if not uploading new one
            if ($request->has('foto_menu')) {
                $data['foto_menu'] = $request->input('foto_menu');
            }
        }

        // Cast types
        $data['id_kategori'] = (int) $data['id_kategori'];
        $data['harga_menu'] = (int) $data['harga_menu'];
        $data['status_visibilitas'] = filter_var($data['status_visibilitas'], FILTER_VALIDATE_BOOLEAN);

        return $data;
    }
}
