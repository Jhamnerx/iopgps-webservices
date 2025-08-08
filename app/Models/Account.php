<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $table = 'accounts';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'additional_data' => 'json',
        'details_last_updated' => 'datetime',
    ];

    public function children()
    {
        return $this->hasMany(Account::class, 'parentAccountId', 'accountId');
    }

    public function devices()
    {
        return $this->hasMany(Devices::class, 'account_id', 'accountId');
    }
}
