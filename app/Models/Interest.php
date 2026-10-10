<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Interest extends Model
{
    protected $table = 'interests';
    protected $fillable = ['name'];

    public function studentInterests(): HasMany
    {
        return $this->hasMany(StudentInterest::class, 'interest_category_id');
    }
}
