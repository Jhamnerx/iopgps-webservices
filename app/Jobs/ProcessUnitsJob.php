<?php

namespace App\Jobs;


use App\Models\Config;
use App\Models\Devices;
use App\Jobs\SendToSutranJob;
use App\Jobs\SendToOsinergminJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
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

    /**
     * Número máximo de reintentos para este job
     */
    public $tries = 5;

    /**
     * Tiempo en segundos para reintentar tras fallo
     * Usa backoff exponencial: 10, 20, 40, 80, 160 segundos
     */
    public $backoff = [10, 20, 40, 80, 160];

    public function __construct()
    {
        $this->onQueue('web-services');
    }

    /**
     * Determina si el job debería ser reintentado
     *
     * @param \Throwable $exception
     * @return bool
     */
    public function retryUntil()
    {
        // Reintentar hasta 5 minutos
        return now()->addMinutes(5);
    }

    public function handle()
    {
        try {
            // Verificar si hay dispositivos con servicios activos
            if (!Devices::hasActiveServices()) {
                Log::info("No hay dispositivos con servicios activos. Cancelando ProcessUnitsJob.");
                return;
            }

            // Verificar si AccountTreeService está en uso
            if (Redis::exists('iopgps_account_tree_in_progress')) {
                // Obtener el timestamp del inicio de operación 
                $startTime = Redis::get('iopgps_account_tree_in_progress');
                $elapsedTime = time() - $startTime;

                Log::info("AccountTreeService en uso (operación iniciada hace {$elapsedTime} segundos). Posponiendo ProcessUnitsJob.");

                // Posponer este job para que se ejecute en 60 segundos
                $this->release(60);
                return;
            }

            // Verificar si DeviceService está en uso
            if (Redis::exists('iopgps_device_service_in_progress')) {
                // Obtener el timestamp del inicio de operación 
                $startTime = Redis::get('iopgps_device_service_in_progress');
                $elapsedTime = time() - $startTime;

                Log::info("DeviceService en uso (operación iniciada hace {$elapsedTime} segundos). Posponiendo ProcessUnitsJob.");

                // Posponer este job para que se ejecute en 60 segundos
                $this->release(60);
                return;
            }

            // Establecer nuestro propio lock
            Redis::set('iopgps_process_units_in_progress', time(), 'EX', 300); // Expira en 5 minutos por seguridad

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
        } catch (\Illuminate\Database\QueryException $e) {
            // Capturar específicamente errores de deadlock
            if ($e->getCode() == 40001 || stripos($e->getMessage(), 'deadlock') !== false) {
                Log::warning('Deadlock detectado en ProcessUnitsJob. Reintentando en 10 segundos...', [
                    'exception' => $e->getMessage(),
                ]);

                // Liberamos el lock antes de reintentar
                Redis::del('iopgps_process_units_in_progress');

                // Reintentamos el job con un delay
                $this->release(10); // 10 segundos de delay
                return;
            }

            // Para otros errores de base de datos, los registramos y dejamos que falle el job
            Log::error('Error de base de datos en ProcessUnitsJob: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            // Liberar el lock incluso en caso de error
            Redis::del('iopgps_process_units_in_progress');
            throw $e;
        } catch (\Exception $e) {
            // Capturar cualquier otra excepción
            Log::error('Error en ProcessUnitsJob: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            // Liberar el lock incluso en caso de error
            Redis::del('iopgps_process_units_in_progress');
            throw $e;
        } finally {
            // Eliminar el lock cuando terminemos (esto se ejecutará incluso si hay un return en los bloques catch)
            if (Redis::exists('iopgps_process_units_in_progress')) {
                Redis::del('iopgps_process_units_in_progress');
                Log::info('ProcessUnitsJob completado y lock liberado');
            }
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
