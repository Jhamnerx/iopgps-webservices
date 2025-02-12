<?php

namespace App\Services\Formatters;

interface UnitFormatterInterface
{
    public function format(array $units): array;
}
