<?php

namespace Database\Seeders;

use App\Models\Empresa;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class EmpresaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {


        Empresa::create([
            'tipo_documento' => '6',
            'razon_social' => 'WebServices Zoftwware Solutions',
            'nombre_comercial' => 'WebServices Zoftwware Solutions',
            'ruc' => '20600995801',
            'direccion' => [
                "country_id" => "PE",
                "department_id" => "13",
                "province_id" => "1301",
                "district_id" => "130101",
                "ubigeo" => "130101",
                "direccion" => 'Av. Los Pinos 123',
            ],
            'telefono' => '+51987654321',
            'correo' => '',
            'mail_config' => [
                'correo' => 'ventas@email.com',
                'servidor' => 'mboxhosting.com',
                'password' => '1105gviG',
                'puerto' => '587',
                'seguridad' => 'tls',
                'tipo_envio' => 'smtp',
            ],
            'estilos' => null,
            'extra' => [
                'texto_superior_login' => 'BIENVENIDO ',
                'texto_inferior_login' => 'Panel Disponible solo para administradores y personal',
            ],
        ]);
    }
}
