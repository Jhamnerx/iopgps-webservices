<?php

namespace App\Services\Processors;

use Carbon\Carbon;
use App\Models\Devices;
use App\Jobs\ReenviarHistorial;
use Illuminate\Support\Facades\Log;


class Processor implements UnitProcessorInterface
{
    public function processUnits($units): array
    {
        $result = [
            'sutran' => [],
            'osinergmin' => [],
            // Otros servicios
        ];

        // Verificamos que units tenga la estructura esperada
        if (!isset($units['data']) || !is_array($units['data']) || empty($units['data'])) {
            Log::warning('No hay datos para procesar en processUnits', ['units' => $units]);
            return $result;
        }

        Log::info('Iniciando procesamiento de ' . count($units['data']) . ' unidades');

        foreach ($units['data'] as $key => $unit) {
            // Verificar que el IMEI exista antes de procesar
            if (!isset($unit['imei'])) {
                Log::warning('Unidad sin IMEI detectada', ['unit' => $unit]);
                continue;
            }

            $device = Devices::where('imei', $unit['imei'])->first();
            $unit['id_api'] = $key;
            $unit['id'] = $device->id ?? null;

            if ($device) {
                // Asegurarse de que signalTime existe antes de parsear
                if (!isset($unit['signalTime'])) {
                    Log::warning('Unidad sin signalTime detectada', ['imei' => $unit['imei']]);
                    continue;
                }

                $deviceTime = Carbon::parse($unit['signalTime'])->setTimezone('America/Lima');
                $deviceLastUpdate = Carbon::parse($device->last_update);

                if ($deviceTime->format('Y-m-d H:i:s') != $deviceLastUpdate->format('Y-m-d H:i:s')) {
                    // Ajustar la velocidad si es necesario
                    $unit = $this->adjustSpeed($unit);

                    // Procesar para Sutran
                    if ($device->services['sutran']['active'] ?? false) {
                        if ($unit['status'] != "离线") {
                            $result['sutran'][] = $unit;
                        }
                    }

                    // Procesar para Osinergmin
                    if ($device->services['osinergmin']['active'] ?? false) {

                        if ($deviceLastUpdate->diffInMinutes($deviceTime) > 3) {
                            ReenviarHistorial::dispatch($unit['imei'], $deviceLastUpdate->format('Y-m-d H:i:s'), $deviceTime->format('Y-m-d H:i:s'));
                        }

                        if ($unit['status'] != "离线") {
                            $result['osinergmin'][] = $unit;
                        }
                    }
                }
            } else {
                Log::warning('Dispositivo no encontrado en la base de datos: ' . $unit['imei']);
            }
        }

        Log::info('Procesamiento completado. Unidades a enviar: Sutran=' . count($result['sutran']) . ', Osinergmin=' . count($result['osinergmin']));
        return $result;
    }

    /**
     * Ajusta la velocidad del vehículo si supera los 90 km/h
     * 
     * @param array $unit Datos de la unidad
     * @return array Unidad con velocidad ajustada si era necesario
     */
    private function adjustSpeed(array $unit): array
    {
        // Verificar si existe la propiedad speed y es mayor a 90
        if (isset($unit['speed']) && $unit['speed'] > 90) {
            // Generar un valor aleatorio entre 70 y 89
            $randomSpeed = rand(70, 89);

            // Guardar la velocidad original para registro
            $originalSpeed = $unit['speed'];

            // Establecer la nueva velocidad
            $unit['speed'] = $randomSpeed;

            // Registrar en el canal específico para ajustes de velocidad
            Log::channel('speed_adjustments')->info("Velocidad ajustada", [
                'imei' => $unit['imei'],
                'plate' => $unit['plateNumber'] ?? 'No disponible',
                'original_speed' => $originalSpeed,
                'adjusted_speed' => $randomSpeed,
                'datetime' => now()->format('Y-m-d H:i:s'),
                'location' => [
                    'lat' => $unit['lat'] ?? null,
                    'lng' => $unit['lng'] ?? null,
                    'address' => $unit['address'] ?? null
                ]
            ]);

            // También registramos en el log general para seguimiento
            //  Log::info("Velocidad ajustada para IMEI {$unit['imei']}: original {$originalSpeed} km/h, ajustada a {$randomSpeed} km/h");
        }

        return $unit;
    }
}
