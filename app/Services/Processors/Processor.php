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

        foreach ($units['data']  as $key => $unit) {

            $device = Devices::where('imei', $unit['imei'])->first();
            $unit['id_api'] = $key;
            $unit['id'] = $device->id ?? null;

            if ($device) {

                $deviceTime = Carbon::parse($unit['signalTime'])->setTimezone('America/Lima');

                $deviceLastUpdate = Carbon::parse($device->last_update);

                if ($deviceTime->format('Y-m-d H:i:s') != $deviceLastUpdate->format('Y-m-d H:i:s')) {

                    if ($device->services['sutran']['active'] ?? false) {

                        if ($unit['status'] != "离线") {

                            $result['sutran'][] = $unit;
                        }
                    }


                    if ($device->services['osinergmin']['active'] ?? false) {
                        Log::info('Procesando unidad: ' . $unit['imei'] . "-" . $deviceLastUpdate->diffInMinutes($deviceTime));
                        if ($deviceLastUpdate->diffInMinutes($deviceTime) > 3) {

                            ReenviarHistorial::dispatch($unit['imei'], $deviceLastUpdate->format('Y-m-d H:i:s'), $deviceTime->format('Y-m-d H:i:s'));
                        }

                        if ($unit['status'] != "离线") {

                            $result['osinergmin'][] = $unit;
                        }
                    }
                }
            }
        }

        return $result;
    }
}
