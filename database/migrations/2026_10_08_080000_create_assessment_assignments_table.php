<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('assessment_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('assessor_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('title', 150);
            $table->string('aspect', 16);
            $table->json('rubric');
            $table->date('due_date')->nullable();
            $table->string('status', 20)->default('assigned');
            $table->unsignedTinyInteger('proposed_score')->nullable();
            $table->text('observations')->nullable();
            $table->text('feedback')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedTinyInteger('final_score')->nullable();
            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'title', 'aspect'], 'assessment_task_student_unique');
            $table->index(['assessor_id', 'status']);
        });
        Schema::create('assessment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_assignment_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('action', 20);
            $table->json('details')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('assessment_events');
        Schema::dropIfExists('assessment_assignments');
    }
};
