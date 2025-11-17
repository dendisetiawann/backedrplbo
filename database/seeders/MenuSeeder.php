<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Menu;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::all()->keyBy('name');

        $menus = [
            [
                'category' => 'Minuman Dingin',
                'name' => 'Es Kopi Susu Kejora',
                'description' => 'Kopi susu gula aren signature Kejora.',
                'price' => 25000,
                'photo_path' => 'images/menus/es-kopi-susu.jpg',
            ],
            [
                'category' => 'Minuman Hangat',
                'name' => 'Latte Hangat',
                'description' => 'Latte creamy hangat dengan latte art.',
                'price' => 28000,
                'photo_path' => 'images/menus/latte-hangat.jpg',
            ],
            [
                'category' => 'Makanan Berat',
                'name' => 'Nasi Goreng Kejora',
                'description' => 'Nasi goreng spesial dengan telur mata sapi.',
                'price' => 35000,
                'photo_path' => 'images/menus/nasi-goreng.jpg',
            ],
            [
                'category' => 'Camilan',
                'name' => 'Roti Bakar Cokelat',
                'description' => 'Roti bakar tebal dengan selai cokelat premium.',
                'price' => 20000,
                'photo_path' => 'images/menus/roti-bakar.jpg',
            ],
            [
                'category' => 'Camilan',
                'name' => 'Kentang Goreng Truffle',
                'description' => 'Kentang goreng renyah dengan minyak truffle.',
                'price' => 27000,
                'photo_path' => 'images/menus/kentang-goreng.jpg',
            ],
            [
                'category' => 'Minuman Dingin',
                'name' => 'Matcha Latte',
                'description' => 'Matcha premium dengan susu segar.',
                'price' => 30000,
                'photo_path' => 'images/menus/matcha-latte.jpg',
            ],
        ];

        foreach ($menus as $menu) {
            if (! $categories->has($menu['category'])) {
                continue;
            }

            Menu::updateOrCreate(
                ['name' => $menu['name']],
                [
                    'category_id' => $categories[$menu['category']]->id,
                    'description' => $menu['description'],
                    'price' => $menu['price'],
                    'photo_path' => $menu['photo_path'],
                    'is_visible' => true,
                ]
            );
        }
    }
}
