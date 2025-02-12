<?php

namespace App\Services\Formatters;

use App\Models\Config;
use App\Models\Devices;
use App\Services\Transformers\UnitTransformer;


class OsinergminFormatter implements UnitFormatterInterface
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

            $config = Config::first();

            return [
                'id' => $unit['id'],
                'event' => 'none',
                'gpsDate' => gmdate('Y-m-d\TH:i:s.v\Z', $unit['signalTime']),
                'plate' => trim($device->plate),
                'speed' => intval($unit['speed']),
                'position' => [
                    'latitude' => doubleval($unit['location']['lat']),
                    'longitude' => doubleval($unit['location']['lng']),
                    'altitude' => doubleval(0),
                ],
                'tokenTrama' => $config->servicios['osinergmin']['token'],
                'odometer' => round(0, 2),
                'imei' => intval($device->imei),
                'idTrama' => 0,
            ];
        }, $normalizedUnits);
    }
}
