<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Experience extends Model
{
    protected $fillable = ['company', 'title', 'description', 'start_date', 'end_date'];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
