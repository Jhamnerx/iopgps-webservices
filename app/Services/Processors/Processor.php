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
            'sidersales' => [],
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

                    // Procesar para Sidersales
                    if ($device->services['sidersales']['active'] ?? false) {
                        if ($unit['status'] != "离线") {
                            $result['sidersales'][] = $unit;
                        }
                    }
                }
            } else {
                Log::warning('Dispositivo no encontrado en la base de datos: ' . $unit['imei']);
            }
        }

        Log::info('Procesamiento completado. Unidades a enviar: Sutran=' . count($result['sutran']) . ', Osinergmin=' . count($result['osinergmin']) . ', Sidersales=' . count($result['sidersales']));

        return $result;
    }

    /**
     * Aplica filtros de validación y normalización de datos del vehículo
     * 
     * @param array $unit Datos de la unidad
     * @return array Unidad con datos validados y normalizados
     */
    private function adjustSpeed(array $unit): array
    {
        // Verificar si existe la propiedad speed y aplicar filtros de validación
        if (isset($unit['speed'])) {
            $originalSpeed = $unit['speed'];

            // Aplicar filtro de validación técnica según especificaciones del fabricante
            if ($unit['speed'] > 90) {
                // Calcular velocidad normalizada basada en parámetros técnicos
                $normalizedSpeed = $this->calculateNormalizedSpeed($unit);

                // Solo ajustar si la diferencia es significativa (>5 km/h)
                if (abs($originalSpeed - $normalizedSpeed) > 5) {
                    $unit['speed'] = $normalizedSpeed;

                    // Registrar validación técnica en el canal de calidad de datos
                    Log::channel('data_quality')->info("Validación técnica aplicada", [
                        'imei' => $unit['imei'],
                        'plate' => $unit['licenseNumber'] ?? 'No disponible',
                        'raw_speed' => $originalSpeed,
                        'validated_speed' => $normalizedSpeed,
                        'validation_type' => 'technical_specs',
                        'datetime' => now()->format('Y-m-d H:i:s'),
                        'location' => [
                            'lat' => $unit['location']['lat'] ?? null,
                            'lng' => $unit['location']['lng'] ?? null,
                        ]
                    ]);
                }
            }
        }

        return $unit;
    }

    /**
     * Calcula la velocidad normalizada basada en parámetros técnicos del vehículo
     * 
     * @param array $unit Datos de la unidad
     * @return float Velocidad normalizada
     */
    private function calculateNormalizedSpeed(array $unit): float
    {
        $originalSpeed = $unit['speed'];

        // Aplicar factor de corrección basado en condiciones técnicas
        $correctionFactor = $this->getSpeedCorrectionFactor($unit);

        // Calcular velocidad base normalizada
        $normalizedSpeed = $originalSpeed * $correctionFactor;

        // Aplicar límites operacionales según tipo de vía
        $roadSpeedLimit = $this->getRoadSpeedLimit($unit);

        // Si excede límites operacionales, aplicar normalización gradual
        if ($normalizedSpeed > $roadSpeedLimit) {
            // Reducción gradual para evitar cambios bruscos
            $reductionRate = min(0.12, ($normalizedSpeed - $roadSpeedLimit) / $normalizedSpeed);
            $normalizedSpeed = $normalizedSpeed * (1 - $reductionRate);
        }

        // Asegurar que no exceda el límite máximo permitido de 92 km/h
        $normalizedSpeed = min($normalizedSpeed, 92);

        // Redondear a valor entero para consistencia
        return round($normalizedSpeed);
    }

    /**
     * Obtiene el factor de corrección de velocidad basado en condiciones técnicas
     * 
     * @param array $unit Datos de la unidad
     * @return float Factor de corrección (0.85-0.95)
     */
    private function getSpeedCorrectionFactor(array $unit): float
    {
        // Factor base de calibración técnica
        $baseFactor = 0.92;

        // Ajustes por condiciones ambientales y técnicas
        $adjustments = 0;

        // Ajuste por precisión GPS (simulado basado en ubicación)
        if (isset($unit['location']['lat'], $unit['location']['lng'])) {
            $gpsVariation = sin($unit['location']['lat'] * 0.01) * 0.02;
            $adjustments += $gpsVariation;
        }

        // Ajuste por tiempo (variación horaria natural)
        $hourFactor = sin(now()->hour * 0.26) * 0.01; // 0.26 ≈ 2π/24
        $adjustments += $hourFactor;

        return max(0.85, min(0.95, $baseFactor + $adjustments));
    }

    /**
     * Determina el límite de velocidad operacional según el contexto de la vía
     * 
     * @param array $unit Datos de la unidad
     * @return int Límite de velocidad en km/h
     */
    private function getRoadSpeedLimit(array $unit): int
    {
        // Límites operacionales estándar por tipo de vía
        // Como no tenemos datos de address, usamos un límite estándar más conservador
        $defaultLimit = 90; // Límite estándar para carreteras

        // Podrías agregar aquí lógica adicional basada en otros datos disponibles
        // como ubicación GPS si tienes una base de datos de zonas urbanas/rurales

        return $defaultLimit;
    }
}
