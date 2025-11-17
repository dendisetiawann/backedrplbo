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

        return response()->json($menu->load('category'), 201);
    }

    public function show(Menu $menu)
    {
        return response()->json($menu->load('category'));
    }

    public function update(Request $request, Menu $menu)
    {
        $data = $this->validatedData($request, $menu->id);
        $menu->update($data);

        return response()->json($menu->load('category'));
    }

    public function destroy(Menu $menu)
    {
        $menu->delete();

        return response()->json([
            'message' => 'Menu berhasil dihapus.',
        ]);
    }

    private function validatedData(Request $request, ?int $menuId = null): array
    {
        $rules = [
            'category_id' => ['required'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'photo_path' => ['nullable', 'string', 'max:2048'],
            'is_visible' => ['required'],
        ];

        $data = $request->validate($rules);

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('menu_photos', 'public');
            $data['photo_path'] = $path;
            unset($data['photo']);
        } else {
            $data['photo_path'] = $data['photo_path'] ?? null;
        }

        // Manual validation & casting after FormData string conversion
        $categoryId = (int) $data['category_id'];
        if ($categoryId <= 0) {
            throw \Illuminate\Validation\ValidationException::withMessages(['category_id' => 'The category id field is required.']);
        }
        $price = (int) $data['price'];
        if ($price < 0) {
            throw \Illuminate\Validation\ValidationException::withMessages(['price' => 'The price must be at least 0.']);
        }

        $data['category_id'] = $categoryId;
        $data['price'] = $price;
        $data['is_visible'] = in_array(strtolower($data['is_visible']), ['true','1','on'], true);

        return $data;
    }
}
