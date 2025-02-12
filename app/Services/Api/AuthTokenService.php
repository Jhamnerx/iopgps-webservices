<?php

namespace App\Services\Api;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class AuthTokenService
{
    protected string $appid;
    protected string $key;
    protected string $authUrl;

    public function __construct()
    {
        $this->appid = config('app.IOPGPS_APPID', 'tu-appid');
        $this->key = config('app.IOPGPS_KEY', 'tu-secret-key');
        $this->authUrl = 'https://open.iopgps.com/api/auth';
    }

    /**
     * Genera el signature usando MD5 (MD5(key) + time) en minúsculas sin espacios.
     */
    private function generateSignature(): array
    {
        $timestamp = time();
        $hashedKey = md5($this->key);
        $signature = md5($hashedKey . $timestamp);

        return [
            'timestamp' => $timestamp,
            'signature' => $signature,
        ];
    }

    /**
     * Obtiene el accessToken de la API y lo almacena en Redis.
     */
    public function getAccessToken(): ?string
    {
        // Revisar si el token aún está en Redis
        if ($token = Redis::get('iopgps_access_token')) {
            return $token;
        }

        $data = $this->generateSignature();

        // Realizar la solicitud a la API
        $response = Http::post($this->authUrl, [
            'appid' => $this->appid,
            'time' => $data['timestamp'],
            'signature' => $data['signature'],
        ]);

        if ($response->successful()) {
            $result = $response->json();
            if ($result['code'] === 0) {
                $accessToken = $result['accessToken'];
                $expiresIn = $result['expiresIn'] ?? 7200; // 2 horas en segundos

                // Guardar en Redis con TTL de 1h 30min (5400s)
                Redis::setex('iopgps_access_token', 5400, $accessToken);

                return $accessToken;
            }
        }

        Log::error('Error obteniendo el accessToken de IOPGPS', [
            'response' => $response->body(),
        ]);

        return null;
    }
}
