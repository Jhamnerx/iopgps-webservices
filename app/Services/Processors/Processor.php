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
}
