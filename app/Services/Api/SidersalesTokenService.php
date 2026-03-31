<?php

namespace App\Services\Api;

use App\Models\Config;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\RequestException;

class SidersalesTokenService
{
    // Azure AD tenant ID de Gerdau/Sidersales
    const TENANT_ID = '461fd7ef-0eb3-4420-b044-310dc2914d64';
    const TOKEN_URL = 'https://login.microsoftonline.com/' . self::TENANT_ID . '/oauth2/v2.0/token';
    const CACHE_KEY = 'sidersales_access_token';
    const TOKEN_TTL_MINUTES = 55; // Token dura 1 hora, renovamos 5 min antes

    /**
     * Obtiene el token de acceso. Si existe en caché y es válido, lo devuelve.
     * Si no, solicita uno nuevo a Azure AD.
     */
    public function getToken(): string
    {
        if (Cache::has(self::CACHE_KEY)) {
            return Cache::get(self::CACHE_KEY);
        }

        return $this->requestNewToken();
    }

    /**
     * Fuerza la renovación del token aunque esté en caché.
     */
    public function refreshToken(): string
    {
        Cache::forget(self::CACHE_KEY);
        return $this->requestNewToken();
    }

    /**
     * Solicita un token nuevo a Azure AD con client credentials flow.
     */
    private function requestNewToken(): string
    {
        $config = Config::first();
        $clientId = $config->servicios['sidersales']['client_id'] ?? '';
        $clientSecret = $config->servicios['sidersales']['client_secret'] ?? '';

        if (empty($clientId) || empty($clientSecret)) {
            throw new \RuntimeException('Las credenciales de Sidersales (client_id / client_secret) no están configuradas.');
        }

        $client = new Client(['verify' => false]);

        try {
            $response = $client->post(self::TOKEN_URL, [
                'form_params' => [
                    'grant_type'    => 'client_credentials',
                    'client_id'     => $clientId,
                    'client_secret' => $clientSecret,
                    'scope'         => 'https://graph.microsoft.com/.default',
                ],
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            if (empty($body['access_token'])) {
                throw new \RuntimeException('La respuesta de Azure AD no contiene access_token.');
            }

            Cache::put(self::CACHE_KEY, $body['access_token'], now()->addMinutes(self::TOKEN_TTL_MINUTES));

            Log::channel('sidersales')->info('Token Sidersales renovado correctamente', [
                'expires_in'  => $body['expires_in'] ?? 'unknown',
                'token_type'  => $body['token_type'] ?? 'unknown',
            ]);

            return $body['access_token'];
        } catch (RequestException $e) {
            $body = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();
            Log::channel('sidersales')->error('Error al obtener token Sidersales', ['error' => $body]);
            throw new \RuntimeException('No se pudo obtener el token de Sidersales: ' . $body);
        }
    }
}
