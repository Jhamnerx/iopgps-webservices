<?php

namespace App\Services\Formatters;

use DateTime;
use DateTimeZone;
use App\Models\Devices;
use App\Models\WialonDevices;
use Illuminate\Support\Facades\Log;
use App\Services\Transformers\UnitTransformer;


class SutranFormatter implements UnitFormatterInterface
{
    protected $transformer;

    public function __construct(UnitTransformer $transformer)
    {
        $this->transformer = $transformer;
    }

    public function format(array $units): array
    {
        $normalizedUnits = $this->transformer->transform($units);

        return array_map(function ($unit) {


            $device = Devices::where('imei', $unit['imei'])->first();

            $date = new DateTime('@' . $unit['signalTime']);

            $date->setTimezone(new DateTimeZone('America/Lima'));
            $formattedDate = $date->format('Y-m-d H:i:s');

            return [
                'id' => $unit['id'],
                'plate' => trim(str_replace('-', '', $device->plate)),
                'geo' => [floatval($unit['location']['lat']), floatval($unit['location']['lng'])],
                'direction' => intval($unit['course'] ?? 0),
                'event' => $unit['speed'] > 5 ? 'ER' : 'PA',
                'speed' => intval($unit['speed']),
                'time_device' => $formattedDate,
                'imei' => intval($device->imei),
                'idTrama' => 0,
            ];
        }, $normalizedUnits);
    }
}
