<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivityScheme extends Model {
    protected $fillable = ['name', 'category', 'objectives', 'agenda', 'duration_minutes'];

    protected function casts(): array {
        return ['duration_minutes' => 'integer'];
    }

    public function activities(): HasMany {
        return $this->hasMany(Activity::class);
    }
}
