<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['pembina', 'siswa', 'panitia'])->default('siswa')->change();
        });
    }
    public function down(): void {
        DB::table('users')->where('role', 'panitia')->update(['role' => 'siswa']);
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['pembina', 'siswa'])->default('siswa')->change();
        });
    }
};
