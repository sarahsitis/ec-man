<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreTestResult extends Model {
    protected $guarded = ['id'];
    protected $hidden = ['answers'];

    protected function casts(): array {
        return [
            'answers' => 'array', 'domain_scores' => 'array', 'self_assessment' => 'array',
            'interests' => 'array', 'score' => 'integer', 'max_score' => 'integer', 'submitted_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo {
        return $this->belongsTo(Student::class);
    }
}
