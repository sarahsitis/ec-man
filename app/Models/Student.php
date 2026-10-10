<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    public const STATUSES = ['active' => 'Aktif', 'inactive' => 'Nonaktif', 'left' => 'Keluar', 'alumni' => 'Alumni'];
    protected $fillable = [
        'user_id',
        'student_number',
        'full_name',
        'joined_year',
        'profile_photo_path', 'phone', 'class_name', 'email', 'address',
        'status',
    ];

    protected $hidden = ['profile_photo_path'];
    protected $appends = ['profile_photo_url'];

    public function getProfilePhotoUrlAttribute(): ?string
    {
        return $this->profile_photo_path ? route('students.photo', $this->id) : null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function interests(): HasMany
    {
        return $this->hasMany(StudentInterest::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(Score::class);
    }

    public function preTestResult(): HasOne
    {
        return $this->hasOne(PreTestResult::class);
    }
}
