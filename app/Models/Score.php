<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Score extends Model
{
    protected $fillable = ['assessment_assignment_id', 'assessment_id', 'student_id', 'value', 'feedback', 'review_note', 'approved_by', 'approved_at'];
    protected function casts(): array { return ['value' => 'integer', 'approved_at' => 'datetime']; }
    public function assignment(): BelongsTo { return $this->belongsTo(AssessmentAssignment::class, 'assessment_assignment_id'); }
    public function assessment(): BelongsTo { return $this->belongsTo(Assessment::class); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
}
