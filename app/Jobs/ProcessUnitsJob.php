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

        $result = [];
        foreach ($chunks as $chunk) {
            $imeis = implode(',', array_map('intval', array_values($chunk)));

            $partialResult = $this->deviceStatusService->fetchDeviceStatus($chunk);


            $result = array_merge($result, $partialResult);
        }

        Log::info('Procesando unidades:' . json_encode($result));

        $processor = new Processor();
        $processedUnits = $processor->processUnits($result);

        if (!empty($processedUnits['sutran'])) {
            SendToSutranJob::dispatch($processedUnits['sutran']);
        }

        if (!empty($processedUnits['osinergmin'])) {
            SendToOsinergminJob::dispatch($processedUnits['osinergmin']);
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
