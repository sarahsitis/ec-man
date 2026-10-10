<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('title', 150);
            $table->string('aspect', 16);
            $table->json('rubric');
            $table->text('instructions')->nullable();
            $table->timestamps();
        });
        Schema::table('assessment_assignments', function (Blueprint $table) {
            $table->foreignId('assessment_id')->nullable()->constrained()->restrictOnDelete();
            $table->unique(['assessment_id', 'student_id']);
            $table->index(['status', 'submitted_at']);
        });
        Schema::create('scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_assignment_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('assessment_id')->constrained()->restrictOnDelete();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('value');
            $table->text('feedback')->nullable();
            $table->text('review_note')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->unique(['assessment_id', 'student_id']);
            $table->index(['student_id', 'approved_at']);
        });

        // Legacy assignments have no reliable batch identifier; preserve each task independently.
        DB::table('assessment_assignments')->orderBy('id')->chunkById(100, function ($assignments) {
            foreach ($assignments as $assignment) {
                $assessmentId = DB::table('assessments')->insertGetId([
                    'created_by' => $assignment->created_by, 'title' => $assignment->title,
                    'aspect' => $assignment->aspect, 'rubric' => $assignment->rubric,
                    'created_at' => $assignment->created_at, 'updated_at' => $assignment->updated_at,
                ]);
                DB::table('assessment_assignments')->where('id', $assignment->id)->update(['assessment_id' => $assessmentId]);
                if ($assignment->status === 'approved' && $assignment->final_score >= 1 && $assignment->final_score <= 4) {
                    DB::table('scores')->insert([
                        'assessment_assignment_id' => $assignment->id, 'assessment_id' => $assessmentId,
                        'student_id' => $assignment->student_id, 'value' => $assignment->final_score,
                        'feedback' => $assignment->feedback, 'review_note' => $assignment->review_note,
                        'approved_by' => $assignment->reviewed_by, 'approved_at' => $assignment->reviewed_at,
                        'created_at' => $assignment->created_at, 'updated_at' => $assignment->updated_at,
                    ]);
                }
            }
        });
        Schema::table('assessment_assignments', function (Blueprint $table) {
            $table->unsignedBigInteger('assessment_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scores');
        Schema::table('assessment_assignments', function (Blueprint $table) {
            $table->dropUnique(['assessment_id', 'student_id']);
            $table->dropIndex(['status', 'submitted_at']);
            $table->dropConstrainedForeignId('assessment_id');
        });
        Schema::dropIfExists('assessments');
    }
};
