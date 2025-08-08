<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Devices extends Model
{
    protected $table = 'devices';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'services' => 'json',
        'last_position' => 'json',
        'last_update' => 'datetime',
        'details_last_updated' => 'datetime',
    ];


    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id', 'accountId');
    }

    /**
     * Verificar si hay al menos un dispositivo con algún servicio activo (sutran u osinergmin)
     * 
     * @return bool
     */
    public static function hasActiveServices()
    {
        return self::where(function ($query) {
            $query->whereJsonContains('services->sutran->active', true)
                ->orWhereJsonContains('services->osinergmin->active', true);
        })->exists();
    }
}
