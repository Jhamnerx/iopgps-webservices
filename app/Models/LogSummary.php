<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogSummary extends Model
{
    protected $table = 'log_summaries';

    protected $guarded = [
        'id',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'date' => 'date',
        'error_samples' => 'array',
        'success_samples' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
