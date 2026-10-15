<?php

namespace App\Console\Commands;

use App\Models\Devices;
use Illuminate\Console\Command;
use App\Services\Api\AuthTokenService;
use App\Services\Api\DeviceStatusService;

class RefreshIOPGPSToken extends Command
{
    protected $signature = 'iopgps:refresh-token
                            {--force : Renovar aunque el token actual siga vigente}
                            {--check : Verificar el token con una consulta real a la API}';

    protected $description = 'Renueva el accessToken de IOPGPS antes de que expire y opcionalmente lo verifica';

    /**
     * Si al token le queda menos que esto, se renueva (el scheduler corre cada hora).
     */
    protected const REFRESH_THRESHOLD = 3600;

    public function handle(AuthTokenService $authTokenService)
    {
        $ttl = $authTokenService->tokenTtl();

        if ($this->option('force') || $ttl < self::REFRESH_THRESHOLD) {
            $authTokenService->invalidate();
            $token = $authTokenService->refreshToken();
        } else {
            $token = $authTokenService->getAccessToken();
        }

        if (!$token) {
            $this->error('No se pudo obtener un accessToken válido (revisa IOPGPS_APPID / IOPGPS_KEY y storage/logs).');
            return self::FAILURE;
        }

        $this->info('accessToken OK: ' . substr($token, 0, 6) . '... (expira en ' . $authTokenService->tokenTtl() . 's)');

        if ($this->option('check')) {
            return $this->checkToken();
        }

        return self::SUCCESS;
    }

    protected function checkToken(): int
    {
        $imei = Devices::value('imei');

        if (!$imei) {
            $this->warn('No hay dispositivos para verificar el token.');
            return self::SUCCESS;
        }

        $result = app(DeviceStatusService::class)->fetchDeviceStatus([$imei]);

        if (!empty($result['token_error'])) {
            $this->error('IOPGPS rechazó el token.');
            return self::FAILURE;
        }

        if (isset($result['error'])) {
            $this->warn('El token no fue rechazado, pero la API devolvió: ' . $result['error']);
            return self::FAILURE;
        }

        $this->info('Token verificado contra la API de IOPGPS.');

        return self::SUCCESS;
    }
}
