<?php

namespace App\Services\Fetchers;

use Gpswox\Wox;
use Gpswox\Resources\Device;
use Illuminate\Support\Facades\Log;

class WoxFetcher
{
    protected $token;
    protected $host;

    public function __construct($host, $token)
    {
        $this->token = $token;
        $this->host = $host;
    }

    public function fetchUnits(): array
    {
        $client = new Wox($this->host, $this->token);

        try {
            $deviceResource = new Device($client);
            $units = $deviceResource->listDevices();

            return $units;
        } catch (\Exception $e) {
            Log::error('Error al obtener unidades desde Wox: ' . $e->getMessage());
            return [];
        }
    }
}
