<?php

namespace Database\Seeders;

use App\Models\Pengguna;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        Pengguna::updateOrCreate(
            ['username' => 'admin_budi'],
            [
                'nama_pengguna' => 'Budi Santoso',
                'password' => Hash::make('12345'),
            ]
        );
    }
}
