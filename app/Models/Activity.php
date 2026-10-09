<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Activity extends Model
{
    public const STATUSES = ['scheduled' => 'Terjadwal', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'];
    protected $fillable = [
        'academic_year_id',
        'title',
        'category',
        'description',
        'activity_date',
        'start_time',
        'end_time',
        'location',
        'pic',
        'target_audience',
        'status',
        'created_by',
        'activity_scheme_id', 'objectives', 'agenda',
    ];

    protected function casts(): array
    {
        return [
            'activity_date' => 'date:Y-m-d',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function scheme(): BelongsTo
    {
        return $this->belongsTo(ActivityScheme::class, 'activity_scheme_id');
    }
}
