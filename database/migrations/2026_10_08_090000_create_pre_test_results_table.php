<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('pre_test_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->unique()->constrained()->restrictOnDelete();
            $table->string('version', 50);
            $table->json('answers');
            $table->unsignedSmallInteger('score');
            $table->unsignedSmallInteger('max_score');
            $table->string('level', 50);
            $table->json('domain_scores');
            $table->json('self_assessment');
            $table->json('interests');
            $table->text('learning_goal');
            $table->timestamp('submitted_at');
            $table->timestamps();
        });

        foreach (['Percakapan', 'Debat', 'Storytelling', 'Menulis kreatif', 'Mendengarkan', 'Permainan bahasa'] as $name) {
            DB::table('interest_categories')->insertOrIgnore(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void {
        Schema::dropIfExists('pre_test_results');
        // Categories are shared with student interests; preserve them on rollback.
    }
};
