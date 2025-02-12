<?php

namespace App\Jobs;


use DateTime;
use DateTimeZone;
use App\Models\Config;
use \Resources\History;
use App\Models\Devices;
use Gpswox\Resources\Device;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Services\Api\DeviceHistoryService;
use App\Services\Senders\OsinergminSender;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ReenviarHistorial implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $deviceId;
    public $lastUpdate;
    public $now;
    public $timeout = 1200; // 5 minutos
    protected DeviceHistoryService $deviceHistoryService;
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

            $this->reenviarHistorial($posiciones['data']);
        } catch (\Exception $e) {
            Log::error("Error al reenviar historial: " . $e->getMessage());
        }
    }



    public function reenviarHistorial($posiciones)
    {

        $tramas = $this->format($posiciones, 'Devices');

        $url = "https://prod.osinergmin-agent-2021.com/api/v1/trama";

        $sender = new OsinergminSender('Devices');
        $sender->send($tramas, $url);
    }

    public function format(array $posiciones, $source): array
    {

        return array_map(function ($unit) use ($source) {


            $device = Devices::where('imei', $this->deviceId)->first();

            $config = Config::first();

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
