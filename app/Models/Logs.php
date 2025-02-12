<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Logs extends Model
{
    protected $table = 'logs';

    protected $guarded = [
        'id',
        'created_at',
        'updated_at'

    ];

    protected $casts = [
        'fecha_hora_posicion' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'

    ];


    public function getStatusAttribute($value)
    {
        return $value;
    }

    public function getPlateNumberAttribute($value)
    {
        return $value;
    }

    public function getMethodAttribute($value)
    {
        return $value;
    }
}
