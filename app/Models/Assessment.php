<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assessment extends Model
{
    protected $fillable = ['created_by', 'title', 'aspect', 'rubric', 'instructions'];
    protected function casts(): array { return ['rubric' => 'array']; }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function assignments(): HasMany { return $this->hasMany(AssessmentAssignment::class); }
    public function scores(): HasMany { return $this->hasMany(Score::class); }
}
