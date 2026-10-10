<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AssessmentAssignment extends Model {
    protected $guarded = ['id'];
    protected function casts(): array {
        return ['rubric' => 'array', 'due_date' => 'date:Y-m-d', 'proposed_score' => 'integer', 'final_score' => 'integer', 'submitted_at' => 'datetime', 'reviewed_at' => 'datetime'];
    }
    public function student() { return $this->belongsTo(Student::class); }
    public function assessor() { return $this->belongsTo(User::class, 'assessor_id'); }
    public function reviewer() { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function events() { return $this->hasMany(AssessmentEvent::class); }
    public function editable(): bool { return in_array($this->status, ['assigned', 'draft', 'revision'], true); }
    public function expired(): bool {
        return $this->due_date && $this->due_date->format('Y-m-d') < now('Asia/Jakarta')->format('Y-m-d');
    }
}
