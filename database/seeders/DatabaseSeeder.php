<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Student;
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
            'role' => 'panitia',
            'password' => Hash::make('password'),
        ]);
        foreach (User::whereIn('role', ['siswa', 'panitia'])->get() as $user) {
            Student::firstOrCreate(['user_id' => $user->id], [
                'student_number' => $user->username, 'full_name' => $user->name,
                'joined_year' => date('Y'), 'class_name' => $user->role === 'panitia' ? 'XI PPLG - RPL 1' : null,
            ]);
            if ($user->role === 'panitia') {
                $today = now('Asia/Jakarta');
                $end = $today->month <= 6 ? $today->copy()->startOfYear()->addMonths(6)->subDay() : $today->copy()->endOfYear();
                $user->committeeRoles()->create(['starts_on' => $today->toDateString(), 'ends_on' => $end->toDateString(), 'appointed_by' => User::where('role', 'pembina')->firstOrFail()->id]);
            }
        }
    }
}
