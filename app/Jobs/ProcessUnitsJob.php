<?php

namespace App\Jobs;


use App\Models\Config;
use App\Models\Devices;
use App\Jobs\SendToSutranJob;
use App\Jobs\SendToOsinergminJob;
use Illuminate\Support\Facades\Log;
use App\Services\Processors\Processor;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Services\Api\DeviceStatusService;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;


class ProcessUnitsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $config;
    protected DeviceStatusService $deviceStatusService;
    public $timeout = 1200; // 5 minutos

    public function __construct()
    {
        $this->onQueue('web-services');
    }

    public function handle()
    {
        $this->deviceStatusService = app(DeviceStatusService::class);

        $devices = Devices::all();

        $sutranDevices = $this->filterSutranDevices($devices);
        $osinergminDevices = $this->filterOsinergminDevices($devices);

        $combinedDevices = array_unique(array_merge($sutranDevices, $osinergminDevices));

        $chunks = array_chunk($combinedDevices, 10);

        // Inicializar el resultado con la estructura correcta
        $result = ['code' => 0, 'data' => []];

        foreach ($chunks as $chunk) {
            $imeis = implode(',', array_map('intval', array_values($chunk)));

            $partialResult = $this->deviceStatusService->fetchDeviceStatus($chunk);
            Log::info('Procesando chunk de ' . count($chunk) . ' dispositivos');

            // Verificar si partialResult tiene datos antes de intentar fusionarlos
            if (!empty($partialResult['data'])) {
                // Combinar solo los datos dentro de la clave 'data'
                $result['data'] = array_merge($result['data'], $partialResult['data']);
                Log::info('Combinando datos parciales. Total acumulado: ' . count($result['data']));
            } else {
                Log::info('No se encontraron datos para este chunk de dispositivos.');
            }
        }

        // Verificar si $result tiene datos
        if (empty($result['data'])) {
            Log::info('No se encontraron datos para ninguno de los dispositivos.');
            return;
        }

        Log::info('Total de dispositivos acumulados para procesar: ' . count($result['data']));

        // Enviar los resultados acumulados al procesador
        $processor = new Processor();
        $processedUnits = $processor->processUnits($result);

        if (!empty($processedUnits['sutran'])) {
            SendToSutranJob::dispatch($processedUnits['sutran']);
            Log::info('Enviados ' . count($processedUnits['sutran']) . ' dispositivos a Sutran');
        }

        if (!empty($processedUnits['osinergmin'])) {
            SendToOsinergminJob::dispatch($processedUnits['osinergmin']);
            Log::info('Enviados ' . count($processedUnits['osinergmin']) . ' dispositivos a Osinergmin');
        }
    }

    private function filterSutranDevices($devices)
    {
        return $devices->filter(function ($unit) {
            return $unit->services['sutran']['active'] ?? false;
        })->pluck('imei')->toArray();
    }

    private function filterOsinergminDevices($devices)
    {
        return $devices->filter(function ($unit) {
            return $unit->services['osinergmin']['active'] ?? false;
        })->pluck('imei')->toArray();
    }
}
