<?php

namespace App\Services\Processors;

interface UnitProcessorInterface
{
    public function processUnits(array $units): array;
}
