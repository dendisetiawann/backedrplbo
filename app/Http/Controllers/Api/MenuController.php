<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index()
    {
        $menus = Menu::with('category')
            ->orderBy('name')
            ->get();

        return response()->json($menus);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $menu = Menu::create($data);

        // Increment category count
        $menu->category()->increment('menu_count');

        return response()->json($menu->load('category'), 201);
    }

    public function show(Menu $menu)
    {
        return response()->json($menu->load('category'));
    }

    public function update(Request $request, Menu $menu)
    {
        $oldCategoryId = $menu->category_id;
        $data = $this->validatedData($request, $menu->id);
        $menu->update($data);

        // Handle category change
        if ($oldCategoryId !== $menu->category_id) {
            \App\Models\Category::where('id', $oldCategoryId)->decrement('menu_count');
            \App\Models\Category::where('id', $menu->category_id)->increment('menu_count');
        }

        return response()->json($menu->load('category'));
    }

    public function destroy(Menu $menu)
    {
        $categoryId = $menu->category_id;
        $menu->delete();

        // Decrement category count
        \App\Models\Category::where('id', $categoryId)->decrement('menu_count');

        return response()->json([
            'message' => 'Menu berhasil dihapus.',
        ]);
    }

    private function validatedData(Request $request, ?int $menuId = null): array
    {
        $rules = [
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255', 'unique:menus,name,' . $menuId],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:1'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:10240'], // Max 10MB
            'photo_path' => ['nullable', 'string'],
            'is_visible' => ['required'],
        ];

        $data = $request->validate($rules, [
            'name.unique' => 'Nama menu sudah digunakan.',
            'price.min' => 'Harga harus lebih dari 0.',
            'photo.max' => 'Ukuran foto maksimal 10MB.',
            'photo.mimes' => 'Format foto harus JPG atau PNG.',
        ]);

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('menu_photos', 'public');
            $data['photo_path'] = $path;
            unset($data['photo']);
        } else {
            // Keep existing photo_path if not uploading new one
            if ($request->has('photo_path')) {
                $data['photo_path'] = $request->input('photo_path');
            }
        }

        // Cast types
        $data['category_id'] = (int) $data['category_id'];
        $data['price'] = (int) $data['price'];
        $data['is_visible'] = filter_var($data['is_visible'], FILTER_VALIDATE_BOOLEAN);

        return $data;
    }
}
