<?php

namespace Database\Seeders;

use App\Models\Config;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::create([
            'name' => 'admin',
            'email' => 'admin@admin.com',
            'password' => bcrypt('admin'),
        ]);

        $config = Config::create(
            [
                'hash' => '',
                'servicios' => [
                    'sutran' => [
                        'token' => '',
                        'status' => 0,
                        'enabled_logs' => 0
                    ],
                    'osinergmin' => [
                        'token' => '',
                        'status' => 0,
                        'enabled_logs' => 0
                    ],
                ]
            ]
        );

        $config->counterServices()->create([
            'data' => [
                "sent" => 0,
                "failed" => 0,
                "success" => 0,
                "last_error" => "Errores en algunas tramas",
                "last_attempt" => "2024-11-14 23:47:05"
            ],
        ]);
    }
}
