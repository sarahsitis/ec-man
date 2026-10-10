<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('students', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->index();
        });
        Schema::table('memberships', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->change();
            $table->string('class_name')->nullable()->change();
        });
        Schema::create('activity_schemes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('category', 100);
            $table->text('objectives');
            $table->text('agenda');
            $table->unsignedSmallInteger('duration_minutes')->default(60);
            $table->timestamps();
        });
        Schema::table('activities', function (Blueprint $table) {
            $table->foreignId('activity_scheme_id')->nullable()->constrained()->nullOnDelete();
            $table->text('objectives')->nullable();
            $table->text('agenda')->nullable();
        });
    }

    public function down(): void {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('activity_scheme_id');
            $table->dropColumn(['objectives', 'agenda']);
        });
        Schema::dropIfExists('activity_schemes');
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });
        // Keep the wider status column so alumni/left records survive rollback.
    }
};
