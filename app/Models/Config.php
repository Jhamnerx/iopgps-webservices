<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use App\Models\CounterServices;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Config extends Model
{
    protected $table = 'config';

    protected $guarded = ['id', 'created_at', 'updated_at'];


    protected function servicios(): Attribute
    {
        return Attribute::make(
            get: fn($servicios) => json_decode($servicios, true),
            set: fn($servicios) => json_encode($servicios),
        );
    }


    public function counterServices()
    {
        return $this->morphOne(CounterServices::class, 'serviceable');
    }
}
