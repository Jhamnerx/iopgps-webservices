<?php

namespace App\Services\Api;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

/**
 * Punto único para obtener el accessToken de IOPGPS.
 * Ningún servicio debe hacer peticiones a IOPGPS sin pasar por aquí:
 * si no hay token válido devuelve null y la petición no se realiza.
 */
class AuthTokenService
{
    public const TOKEN_KEY = 'iopgps_access_token';

    /**
     * Mientras exista esta clave no se vuelve a pedir token (evita martillar /api/auth
     * cada 30s cuando las credenciales son incorrectas o la API está caída).
     */
    public const AUTH_FAILED_KEY = 'iopgps_auth_failed';
    protected const AUTH_FAILED_COOLDOWN = 60;

    /**
     * Margen de seguridad antes de la expiración real del token.
     */
    protected const EXPIRY_MARGIN = 600;

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
     * Devuelve el token guardado en Redis o solicita uno nuevo.
     * Devuelve null si no se pudo obtener un token válido.
     */
    public function getAccessToken(): ?string
    {
        if ($token = Redis::get(self::TOKEN_KEY)) {
            return $token;
        }

        return $this->refreshToken();
    }

    /**
     * Solicita un token nuevo a IOPGPS y lo guarda en Redis.
     */
    public function refreshToken(): ?string
    {
        if (Redis::exists(self::AUTH_FAILED_KEY)) {
            Log::warning('Autenticación IOPGPS en espera tras un fallo reciente; no se solicita token.');
            return null;
        }

        if (in_array($this->appid, ['', 'tu-appid'], true) || in_array($this->key, ['', 'tu-secret-key'], true)) {
            Log::error('IOPGPS_APPID / IOPGPS_KEY no están configurados en el .env.');
            $this->markAuthFailed();
            return null;
        }

        $data = $this->generateSignature();

        try {
            $response = Http::timeout(15)->connectTimeout(5)->post($this->authUrl, [
                'appid' => $this->appid,
                'time' => $data['timestamp'],
                'signature' => $data['signature'],
            ]);
        } catch (\Throwable $e) {
            Log::error('Error de conexión al obtener el accessToken de IOPGPS', ['message' => $e->getMessage()]);
            $this->markAuthFailed();
            return null;
        }

        $result = $response->json();

        if ($response->successful() && ($result['code'] ?? null) === 0 && !empty($result['accessToken'])) {
            $expiresIn = (int) ($result['expiresIn'] ?? 7200); // 2 horas en segundos
            $ttl = max(60, $expiresIn - self::EXPIRY_MARGIN);

            Redis::setex(self::TOKEN_KEY, $ttl, $result['accessToken']);
            Redis::del(self::AUTH_FAILED_KEY);

            return $result['accessToken'];
        }

        Log::error('Error obteniendo el accessToken de IOPGPS', [
            'status' => $response->status(),
            'response' => $response->body(),
        ]);
        $this->markAuthFailed();

        return null;
    }

    /**
     * Borra el token guardado (p.ej. cuando la API lo rechaza).
     */
    public function invalidate(): void
    {
        Redis::del(self::TOKEN_KEY);
    }

    /**
     * Segundos que le quedan al token guardado (-2 si no existe).
     */
    public function tokenTtl(): int
    {
        return (int) Redis::ttl(self::TOKEN_KEY);
    }

    /**
     * Indica si una respuesta de IOPGPS fue rechazada por el token.
     * La API responde HTTP 200 con code != 0, así que se revisa también el mensaje.
     */
    public static function isTokenError(int $statusCode, ?array $body): bool
    {
        if (in_array($statusCode, [401, 403], true)) {
            return true;
        }

        if (!is_array($body) || ($body['code'] ?? 0) === 0) {
            return false;
        }

        $message = strtolower(($body['result'] ?? '') . ' ' . ($body['message'] ?? '') . ' ' . ($body['msg'] ?? ''));

        return str_contains($message, 'token');
    }

    protected function markAuthFailed(): void
    {
        Redis::setex(self::AUTH_FAILED_KEY, self::AUTH_FAILED_COOLDOWN, time());
    }
}
