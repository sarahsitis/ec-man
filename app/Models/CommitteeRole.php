<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommitteeRole extends Model
{
    protected $fillable = ['user_id', 'starts_on', 'ends_on', 'appointed_by', 'note', 'revoked_at', 'revoked_by', 'revoke_reason'];

    protected function casts(): array
    {
        return ['starts_on' => 'date:Y-m-d', 'ends_on' => 'date:Y-m-d', 'revoked_at' => 'datetime'];
    }

    public function scopeActive(Builder $query): Builder
    {
        $today = now('Asia/Jakarta')->toDateString();
        return $query->whereNull('revoked_at')->where('starts_on', '<=', $today)->where('ends_on', '>=', $today);
    }

    public function scopeUpcomingOrActive(Builder $query): Builder
    {
        return $query->whereNull('revoked_at')->where('ends_on', '>=', now('Asia/Jakarta')->toDateString());
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function appointedBy(): BelongsTo { return $this->belongsTo(User::class, 'appointed_by'); }
    public function revokedBy(): BelongsTo { return $this->belongsTo(User::class, 'revoked_by'); }
}
