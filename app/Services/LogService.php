<?php

namespace App\Services;

use App\Models\Logs;
use Carbon\Carbon;

class LogService
{
    /**
     * Guardar un log en la base de datos.
     *
     * @param string $service El nombre del servicio web.
     * @param string $plate El número de la placa.
     * @param string $message El mensaje del log.
     * @param string $level El nivel del log (por defecto: info).
     * @param array $additionalData Datos adicionales opcionales.
     * @return void
     */
    public function logToDatabase($proveedor, $service, $plate, $status = '', $trama = [], $response = [], $additionalData = [], $datePosicion = null, $imei = null): void
    {

        Logs::create([
            'service_name' => $service,
            'method' => 'POST',
            'date' => Carbon::now()->format('Y-m-d H:i:s'),
            'plate_number' => $plate,
            'request' => json_encode($trama),
            'response' => json_encode($response),
            'status' => $status,
            'additional_data' => $additionalData,
            'fecha_hora_posicion' => $datePosicion,
            'imei' => $imei,
        ]);
    }
}
