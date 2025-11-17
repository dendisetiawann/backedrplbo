<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            'Minuman Dingin',
            'Minuman Hangat',
            'Makanan Berat',
            'Camilan',
        ];

        foreach ($names as $name) {
            Category::updateOrCreate(['name' => $name]);
        }
    }
}
