<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Profile extends Model
{
    protected $fillable = [
        'name',
        'headline',
        'about',
        'location',
        'raw_text',
        'parsed_json',
    ];

    // Ensure the JSONB column is automatically cast to an array/object
    protected $casts = [
        'parsed_json' => 'array',
    ];

    public function experiences(): HasMany
    {
        return $this->hasMany(Experience::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class);
    }
}
