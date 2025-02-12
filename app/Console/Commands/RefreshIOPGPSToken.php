<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Api\AuthTokenService;

class RefreshIOPGPSToken extends Command
{
    protected $signature = 'iopgps:refresh-token';
    protected $description = 'Obtiene y almacena el accessToken de IOPGPS en Redis cada 1h 30min';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(AuthTokenService $authTokenService)
    {
        $token = $authTokenService->getAccessToken();

        if ($token) {
            $this->info("Nuevo accessToken almacenado en Redis: $token");
        } else {
            $this->error("Error al obtener el accessToken.");
        }
    }
}
