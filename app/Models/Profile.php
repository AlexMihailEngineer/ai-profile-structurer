<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
    public function experiences()
    {
        return $this->hasMany(Experience::class);
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class);
    }
}
