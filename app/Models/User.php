<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'role',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function student(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function isPembina(): bool
    {
        return $this->role === 'pembina';
    }
    public function isPanitia(): bool
    {
        return $this->role === 'panitia'
            && $this->studentEligibleForCommittee()
            && $this->committeeRoles()->active()->exists();
    }

    public function committeeRoles(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CommitteeRole::class);
    }

    public function studentEligibleForCommittee(): bool
    {
        $student = $this->student;
        return $student && $student->status === 'active'
            && (bool) preg_match('/^(XI|XII)(?:\s|$)/i', trim($student->class_name ?? ''));
    }

    public function committeeClassLocked(): bool
    {
        return $this->committeeRoles()->upcomingOrActive()->exists();
    }

    public function scopeActiveCommittee(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('role', 'panitia')
            ->whereHas('committeeRoles', fn ($roles) => $roles->active())
            ->whereHas('student', fn ($students) => $students->where('status', 'active')
                ->where(fn ($classes) => $classes->where('class_name', 'like', 'XI %')->orWhere('class_name', 'like', 'XII %')));
    }
}
