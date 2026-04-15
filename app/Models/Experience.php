<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Experience extends Model
{
    protected $fillable = ['company', 'title', 'description', 'start_date', 'end_date'];

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }
}
