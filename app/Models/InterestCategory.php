<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InterestCategory extends Model
{
    protected $fillable = ['name'];

    public function studentInterests(): HasMany
    {
        return $this->hasMany(StudentInterest::class);
    }
}
