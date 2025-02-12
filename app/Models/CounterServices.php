<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CounterServices extends Model
{
    protected $table = 'counters_services';

    protected $guarded = ['id', 'created_at', 'updated_at'];


    protected $casts = [
        'data' => 'json',
    ];

    public function serviceable()
    {
        return $this->morphTo();
    }
}
