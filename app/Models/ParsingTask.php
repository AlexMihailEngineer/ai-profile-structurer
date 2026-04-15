<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParsingTask extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * * @var array<int, string>
     */
    protected $fillable = [
        'status',        // pending, processing, completed, failed
        'raw_text',      // the original copy-paste
        'payload',       // the structured JSON from the AI
        'error_message', // details if NVIDIA NIM fails
    ];

    /**
     * The attributes that should be cast.
     * * Since we used JSONB in the migration, casting to 'array' 
     * allows us to treat the AI result as a native PHP array.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'payload' => 'array',
    ];

    /**
     * Helper to check if the task is finished.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Helper to check if the task failed.
     */
    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }
}
