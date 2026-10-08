<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('students', function (Blueprint $table) {
            $table->string('profile_photo_path')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('class_name', 100)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
        });
    }
    public function down(): void {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['profile_photo_path', 'phone', 'class_name', 'email', 'address']);
        });
    }
};
