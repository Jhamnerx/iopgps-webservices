<?php

namespace App\Exports;

use App\Models\Logs;
use Maatwebsite\Excel\Concerns\FromCollection;

class LogsExport implements FromCollection
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Logs::all();
    }

    public function headings(): array
    {
        return [
            ['id', 'PLACA', 'SERVICIO', 'IMEI', 'METODO', 'FECHA ENVIO', 'FECHA DISPOSITIVO', 'REQUEST', 'RESPONSE', 'ESTADO', 'CREADO', 'ACTUALIZADO'],
        ];
    }
}
