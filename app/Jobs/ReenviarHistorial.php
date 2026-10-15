<?php

namespace App\Jobs;


use DateTime;
use DateTimeZone;
use App\Models\Config;
use \Resources\History;
use App\Models\Devices;
use Gpswox\Resources\Device;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Services\Api\DeviceHistoryService;
use App\Services\Senders\OsinergminSender;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;

/**
 * Único por IMEI: mientras haya un reenvío pendiente o en ejecución para un
 * dispositivo, los nuevos dispatch se descartan (antes se encolaba uno nuevo
 * cada 30s por dispositivo hasta que last_update se actualizaba).
 */
class ReenviarHistorial implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $deviceId;
    public $lastUpdate;
    public $now;
    public $timeout = 240; // Menor que retry_after (300s) para evitar ejecuciones duplicadas
    public $tries = 3; // Número de intentos
    public $backoff = 60; // Esperar 60 segundos entre reintentos
    public $uniqueFor = 900; // El lock expira solo si el job muere sin liberarlo
    protected DeviceHistoryService $deviceHistoryService;

    public function uniqueId(): string
    {
        return (string) $this->deviceId;
    }

    public function uniqueVia()
    {
        return Cache::store('redis');
    }

    /**
     * Create a new job instance.
     */
    public function __construct($deviceId, $lastUpdate, $now)
    {
        $this->lastUpdate = $lastUpdate;
        $this->now = $now;
        $this->deviceId = $deviceId;

        $this->onQueue('reenviar-historial');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->deviceHistoryService = app(DeviceHistoryService::class);

        try {
            $posiciones = $this->deviceHistoryService->fetchDeviceHistory($this->deviceId, $this->lastUpdate, $this->now);

            if (empty($posiciones['data'])) {
                Log::info("No hay posiciones que reenviar para el dispositivo: {$this->deviceId}");
                return;
            }

            $this->reenviarHistorial($posiciones['data']);
        } catch (\Exception $e) {
            Log::error("Error al reenviar historial para dispositivo {$this->deviceId}: " . $e->getMessage());
            Log::error($e->getTraceAsString());
            $this->fail($e); // Marca el job como fallido
        }
    }

    public function reenviarHistorial($posiciones)
    {
        if (empty($posiciones)) {
            return;
        }

        $tramas = $this->format($posiciones, 'Devices');

        $url = "https://pmgo.osinergmin.gob.pe/api-gps-ingesta/api/v1/trama";

        $sender = new OsinergminSender();
        $sender->send($tramas, $url);
    }

    public function format(array $posiciones, $source): array
    {
        // Consultar el dispositivo y la configuración una sola vez
        $device = Devices::where('imei', $this->deviceId)->first();
        $config = Config::first();

        if (!$device) {
            Log::error("Dispositivo no encontrado con IMEI: {$this->deviceId}");
            return [];
        }

        return array_map(function ($unit) use ($device, $config, $source) {
            return [
                'id' => $device->id_,
                'event' => 'none',
                'gpsDate' => gmdate('Y-m-d\TH:i:s.v\Z', $unit['gpsTime']),
                'plate' => trim($device->plate),
                'speed' => intval($unit['speed']),
                'position' => [
                    'latitude' => doubleval($unit['location']['lat']),
                    'longitude' => doubleval($unit['location']['lng']),
                    'altitude' => doubleval(0),
                ],
                'tokenTrama' => $config->servicios['osinergmin']['token'],
                'odometer' => round(1),
                'imei' => $device->imei,
                'idTrama' => $device->imei,
            ];
        }, $posiciones);
    }
}
