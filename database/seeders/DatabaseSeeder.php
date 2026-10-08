<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Pembina
        User::create([
            'name' => 'Bapak Pembina',
            'username' => 'admin',
            'role' => 'pembina',
            'password' => Hash::make('admin123'),
        ]);

        // Siswa Biasa
        User::create([
            'name' => 'Siswa Andi',
            'username' => '1001',
            'role' => 'siswa',
            'password' => Hash::make('password'),
        ]);

        // Siswa Panitia
        User::create([
            'name' => 'Panitia Cici',
            'username' => '1002',
            'role' => 'siswa',
            'password' => Hash::make('password'),
        ]);
    }
}
