<?php

namespace App\Services\Senders;

use App\Models\Config;
use App\Models\Devices;
use GuzzleHttp\Client;
use App\Services\LogService;
use App\Services\Api\SidersalesTokenService;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SidersalesSender implements UnitSenderInterface
{
    private const MAX_CONNECTION_ERRORS = 3;

    public LogService $logService;
    public Config $config;
    protected SidersalesTokenService $tokenService;

    public function __construct()
    {
        $this->logService   = app(LogService::class);
        $this->config       = Config::first();
        $this->tokenService = app(SidersalesTokenService::class);
    }

    /**
     * Envía cada payload individualmente al endpoint de Sidersales.
     * La API no acepta bulk, cada posición se envía por separado.
     *
     * @param array  $payloads  Array de payload formateados por SidersalesFormatter
     * @param string $url       URL del entorno (dev / qa / prd)
     */
    public function send(array $payloads, $url): void
    {
        $token = $this->getValidToken();

        // Si la API no responde, cortar el lote en vez de esperar el timeout por cada unidad
        $connectionErrors = 0;

        foreach ($payloads as $payload) {
            if ($this->sendSingle($payload, $url, $token)) {
                $connectionErrors = 0;
                continue;
            }

            if (++$connectionErrors >= self::MAX_CONNECTION_ERRORS) {
                Log::channel('sidersales')->error('Sidersales no responde, se cancela el lote', [
                    'pendientes' => count($payloads),
                ]);
                return;
            }
        }
    }

    /**
     * Envía un único payload al endpoint.
     * Si el token expiró (401), lo renueva y reintenta una vez.
     *
     * @return bool false si hubo error de conexión (sin respuesta HTTP)
     */
    private function sendSingle(array $payload, string $url, string $token, bool $isRetry = false): bool
    {
        // Separar campos internos del payload real de la API
        $imei  = $payload['_imei'] ?? null;
        $apiPayload = array_filter($payload, fn($key) => !str_starts_with($key, '_'), ARRAY_FILTER_USE_KEY);

        $client = new Client([
            'verify' => false,
            'connect_timeout' => 5,
            'timeout' => 10,
        ]);

        Log::channel('sidersales')->debug('>>> REQUEST Sidersales', [
            'method'  => 'POST',
            'url'     => $url,
            'imei'    => $imei,
            'payload' => $apiPayload,
            'token'   => substr($token, 0, 20) . '...' . substr($token, -10),
            'retry'   => $isRetry,
        ]);

        try {
            $response = $client->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ],
                'json' => $apiPayload,
            ]);

            $statusCode   = $response->getStatusCode();
            $rawBody      = $response->getBody()->getContents();
            $responseBody = json_decode($rawBody, true) ?? [];

            Log::channel('sidersales')->debug('<<< RESPONSE Sidersales', [
                'status'      => $statusCode,
                'imei'        => $imei,
                'response'    => $responseBody,
                'raw_body'    => $rawBody,
            ]);

            $this->handleSuccess($apiPayload, $responseBody, $statusCode, $imei);
        } catch (ConnectException $e) {
            Log::channel('sidersales')->error('<<< ERROR conexión Sidersales (timeout/sin conexión)', [
                'imei'    => $imei,
                'plate'   => $apiPayload['placa'] ?? '',
                'message' => $e->getMessage(),
            ]);
            $this->logFailure($apiPayload, ['error' => $e->getMessage()], 0, $imei);
            return false;
        } catch (RequestException $e) {
            if (!$e->hasResponse()) {
                Log::channel('sidersales')->error('<<< ERROR conexión Sidersales (sin respuesta HTTP)', [
                    'imei'    => $imei,
                    'plate'   => $apiPayload['placa'] ?? '',
                    'message' => $e->getMessage(),
                ]);
                $this->logFailure($apiPayload, ['error' => $e->getMessage()], 0, $imei);
                return false;
            }

            $statusCode   = $e->getResponse()->getStatusCode();
            $rawBody      = $e->getResponse()->getBody()->getContents();
            $responseBody = json_decode($rawBody, true) ?? [];

            Log::channel('sidersales')->error('<<< RESPONSE ERROR Sidersales', [
                'status'   => $statusCode,
                'imei'     => $imei,
                'plate'    => $apiPayload['placa'] ?? '',
                'response' => $responseBody,
                'raw_body' => $rawBody,
            ]);

            // Si el token expiró, renovar y reintentar una sola vez
            if ($statusCode === 401 && !$isRetry) {
                Log::channel('sidersales')->warning('Token expirado, renovando...', ['imei' => $imei]);
                $newToken = $this->tokenService->refreshToken();
                return $this->sendSingle($payload, $url, $newToken, true);
            }

            $this->handleError($apiPayload, $responseBody, $statusCode, $imei);
        }

        return true;
    }

    /**
     * Procesa una respuesta exitosa: guarda log y actualiza el dispositivo.
     */
    private function handleSuccess(array $payload, array $response, int $statusCode, ?string $imei): void
    {
        if ($this->config->servicios['sidersales']['enabled_logs'] ?? false) {
            $this->logService->logToDatabase(
                proveedor: 'sidersales',
                service: 'sidersales',
                plate: $payload['placa'],
                status: 'success',
                trama: $payload,
                response: $response,
                additionalData: ['http_status' => $statusCode],
                datePosicion: $payload['fechaHoraGPS'],
                imei: $imei
            );
        }

        if ($imei) {
            Devices::where('imei', $imei)->update([
                'last_update' => $payload['fechaHoraGPS'],
            ]);
        }

        $this->incrementCounter(success: true);

        Log::channel('sidersales')->info('Posición enviada correctamente', [
            'imei'   => $imei,
            'plate'  => $payload['placa'],
            'status' => $statusCode,
        ]);
    }

    /**
     * Procesa una respuesta con error HTTP: guarda log y registra el fallo.
     */
    private function handleError(array $payload, array $response, int $statusCode, ?string $imei): void
    {
        $this->logService->logToDatabase(
            proveedor: 'sidersales',
            service: 'sidersales',
            plate: $payload['placa'],
            status: 'error',
            trama: $payload,
            response: $response,
            additionalData: ['http_status' => $statusCode],
            datePosicion: $payload['fechaHoraGPS'],
            imei: $imei
        );

        $this->incrementCounter(success: false);

        Log::channel('sidersales')->error('Error al enviar posición', [
            'imei'     => $imei,
            'plate'    => $payload['placa'],
            'status'   => $statusCode,
            'response' => $response,
        ]);
    }

    /**
     * Registra un error de conexión (sin respuesta HTTP).
     */
    private function logFailure(array $payload, array $errorData, int $statusCode, ?string $imei): void
    {
        $this->logService->logToDatabase(
            proveedor: 'sidersales',
            service: 'sidersales',
            plate: $payload['placa'] ?? '',
            status: 'error',
            trama: $payload,
            response: $errorData,
            additionalData: ['http_status' => $statusCode],
            datePosicion: $payload['fechaHoraGPS'] ?? null,
            imei: $imei
        );

        $this->incrementCounter(success: false);
    }

    /**
     * Actualiza los contadores globales del servicio (enviados, exitosos, fallidos).
     */
    private function incrementCounter(bool $success): void
    {
        DB::transaction(function () use ($success) {
            $counterService = $this->config->counterServices()->firstOrCreate(
                [
                    'serviceable_type' => Config::class,
                    'serviceable_id'   => $this->config->id,
                ],
                ['data' => []]
            );

            $data             = $counterService->data ?? [];
            $data['sent']     = ($data['sent']    ?? 0) + 1;
            $data['success']  = ($data['success'] ?? 0) + ($success ? 1 : 0);
            $data['failed']   = ($data['failed']  ?? 0) + ($success ? 0 : 1);
            $data['last_attempt'] = now()->toDateTimeString();

            if (!$success) {
                $data['last_error'] = 'Error al enviar posición';
            }

            $counterService->update(['data' => $data]);
        });
    }

    /**
     * Obtiene un token válido desde el servicio de tokens.
     */
    private function getValidToken(): string
    {
        return $this->tokenService->getToken();
    }
}
