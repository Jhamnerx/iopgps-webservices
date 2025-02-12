<?php

namespace App\Services\Transformers;

use DateTime;
use DateTimeZone;

class UnitTransformer
{
    public function transform(array $rawUnits): array
    {
        return array_map(function ($rawUnit) {
            return $this->normalizeUnit($rawUnit);
        }, $rawUnits);
    }

    private function normalizeUnit(array $unit): array
    {

        // Detectar y normalizar la estructura del array $unit
        if (isset($unit['imei'])) {
            return $unit;
        }
    }
}
