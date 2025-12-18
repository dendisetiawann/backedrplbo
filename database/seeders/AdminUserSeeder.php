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
            ['username' => 'kejoracash'],
            [
                'nama_pengguna' => 'kejora_cash',
                'password' => Hash::make('12345'),
            ]
        );
    }
}
