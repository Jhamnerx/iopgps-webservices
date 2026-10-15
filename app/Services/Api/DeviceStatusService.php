<?php

namespace App\Services\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class DeviceStatusService
{
    protected string $baseUrl;
    protected Client $client;

    public function __construct(Client $client)
    {
        $this->baseUrl = 'https://open.iopgps.com/api/device/status';
        $this->client = $client;
    }

    /**
     * Consulta el estado de múltiples dispositivos por IMEI.
     * Si no hay token válido no se hace la petición y se devuelve 'token_error' => true.
     */
    public function fetchDeviceStatus(array $imeis): array
    {
        if (empty($imeis)) {
            return ['error' => 'Debe proporcionar al menos un IMEI.'];
        }

        $authTokenService = app(AuthTokenService::class);
        $accessToken = $authTokenService->getAccessToken();

        if (!$accessToken) {
            Log::error('No hay accessToken válido de IOPGPS; no se consulta el estado de dispositivos.');
            return ['error' => 'Access token no disponible.', 'token_error' => true];
        }

        $result = $this->requestStatus($imeis, $accessToken);

        // Token rechazado por la API: renovar una sola vez y reintentar
        if (!empty($result['token_error'])) {
            Log::warning('IOPGPS rechazó el accessToken; renovando.');
            $authTokenService->invalidate();
            $accessToken = $authTokenService->refreshToken();

            if (!$accessToken) {
                return ['error' => 'No se pudo renovar el access token.', 'token_error' => true];
            }

            $result = $this->requestStatus($imeis, $accessToken);
        }

        return $result;
    }

    private function requestStatus(array $imeis, string $accessToken): array
    {
        try {
            $imeiString = implode(',', $imeis);
            $url = "{$this->baseUrl}?accessToken={$accessToken}&imei={$imeiString}";

            $response = $this->client->request('GET', $url, [
                'connect_timeout' => 5,
                'timeout' => 10,
                'http_errors' => false,
            ]);

            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);

            if (AuthTokenService::isTokenError($statusCode, $body)) {
                Log::error('Token IOPGPS inválido', ['statusCode' => $statusCode, 'body' => $body]);
                return ['error' => 'Token inválido', 'token_error' => true];
            }

            if ($statusCode !== 200 || empty($body) || !isset($body['code'])) {
                Log::error("Respuesta inesperada de la API", ['statusCode' => $statusCode, 'body' => $body]);
                return ['error' => 'Respuesta inesperada de la API'];
            }

            // Antes se logueaba la respuesta completa cada 30s y llenaba el disco
            Log::debug('Partial result', [
                'code' => $body['code'],
                'devices' => is_array($body['data'] ?? null) ? count($body['data']) : 0,
            ]);

            if ($body['code'] !== 0) {
                Log::error("Error en respuesta API", ['response' => $body]);
                return ['error' => $body['result'] ?? 'Error desconocido'];
            }

            // Verificar si body tiene la clave 'data' y no está vacía
            if (!isset($body['data']) || empty($body['data'])) {
                Log::info("No se encontraron datos para los dispositivos.");
                return ['code' => 0, 'data' => []];
            }

            // Si tiene datos, formatearlos
            return $this->formatResponse($body['data']);
        } catch (RequestException | GuzzleException $e) {
            Log::error("Error al conectar con la API de estado de dispositivos", ['message' => $e->getMessage()]);
            return ['error' => 'Error al conectar con la API'];
        } catch (\Exception $e) {
            Log::error("Error inesperado", ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return ['error' => 'Error inesperado'];
        }
    }

    /**
     * Formatea la respuesta de la API antes de devolverla.
     */
    private function formatResponse(array $data): array
    {
        $formattedData = [];
        foreach ($data as $device) {
            if ($device['status'] !== "离线" && $device['status'] !== "未启用") {
                $formattedData[] = [
                    'imei' => $device['imei'],
                    'status' => $device['status'],
                    'location' => [
                        'lng' => $device['lng'] ?? null,
                        'lat' => $device['lat'] ?? null,
                    ],
                    'speed' => $device['speed'] ?? 0,
                    'course' => $device['course'] ?? 'cero',
                    'accStatus' => $device['accStatus'] ?? false,
                    'licenseNumber' => $device['licenseNumber'] ?? null,
                    'endTime' => $device['endTime'] ?? null,
                    'platformEndTime' => $device['platformEndTime'] ?? 'sin expiracion',
                    'activateTime' => $device['activateTime'] ?? null,
                    'statusTimeDesc' => $device['statusTimeDesc'] ?? null,
                    'signalTime' => $device['signalTime'] ?? null,
                    'gpsTime' => $device['gpsTime'] ?? null,
                    'positionType' => $device['positionType'] ?? null,
                    'isWireless' => $device['isWireless'] ?? false,
                    'account' => [
                        'accountId' => $device['accountId'],
                        'accountName' => $device['accountName'],
                        'userName' => $device['userName'],
                    ],
                ];
            } else {
                //Log::info("Dispositivo sin conexión: " . json_encode($device));
            }
        }

        return ['code' => 0, 'data' => $formattedData];
    }
}
