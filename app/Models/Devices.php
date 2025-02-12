<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Devices extends Model
{
    protected $table = 'devices';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'services' => 'json',
        'last_position' => 'json'
    ];


    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
