<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Empresa extends Model
{

    protected $table = 'empresa';
    protected $primaryKey = 'id';
    protected $guarded = ['id', 'created_at', 'updated_at'];


    protected $hidden = [
        'mail_config.password'
    ];


    protected function mailConfig(): Attribute
    {
        return Attribute::make(
            get: fn($mail_config) => json_decode($mail_config, true),
            set: fn($mail_config) => json_encode($mail_config),
        );
    }

    protected function direccion(): Attribute
    {
        return Attribute::make(
            get: fn($value) => json_decode($value, true),
            set: fn($value) => json_encode($value),
        );
    }

    protected function estilos(): Attribute
    {
        return Attribute::make(
            get: fn($value) => json_decode($value, true),
            set: fn($value) => json_encode($value),
        );
    }
    protected function extra(): Attribute
    {
        return Attribute::make(
            get: fn($value) => json_decode($value, true),
            set: fn($value) => json_encode($value),
        );
    }
}
