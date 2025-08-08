<?php

namespace App\Services\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class DeviceDetail
{
    protected string $baseUrl;
    protected Client $client;

    public function __construct(Client $client)
    {
        $this->baseUrl = 'https://open.iopgps.com/api/device/detail';
        $this->client = $client;
    }

    /**
     * Obtiene el accessToken desde Redis.
     */
    private function getAccessToken(): ?string
    {
        return Redis::get('iopgps_access_token');
    }

    /**
     * Consulta los detalles de un dispositivo por su IMEI.
     * 
     * @param string $imei El IMEI del dispositivo
     * @return array Respuesta formateada con los detalles del dispositivo
     */
    public function fetchDeviceDetail(string $imei): array
    {
        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            Log::error("No hay accessToken disponible en Redis para DeviceDetail.");
            $authTokenService = app(AuthTokenService::class);
            $accessToken = $authTokenService->getAccessToken();

            if (!$accessToken) {
                return ['error' => 'No se pudo obtener el access token.'];
            }
        }

        if (empty($imei)) {
            return ['error' => 'Debe proporcionar un IMEI válido.'];
        }

        try {
            $url = "{$this->baseUrl}?accessToken={$accessToken}&imei={$imei}";

            Log::info("Solicitando detalles del dispositivo", ['imei' => $imei, 'url' => $url]);

            $response = $this->client->request('GET', $url, [
                'timeout' => 10,
            ]);

            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);

            if ($statusCode !== 200 || empty($body) || !isset($body['code'])) {
                Log::error("Respuesta inesperada de la API de Device Detail", ['statusCode' => $statusCode, 'body' => $body]);
                return ['error' => 'Respuesta inesperada de la API de Device Detail'];
            }

            // Log completo de la respuesta para depuración
            Log::debug('Respuesta de API Device Detail: ', $body);

            if ($body['code'] !== 0) {
                Log::error("Error en respuesta API Device Detail", ['response' => $body]);
                return ['error' => $body['result'] ?? 'Error desconocido'];
            }

            // Verificar si body tiene la clave 'data' y no está vacía
            if (!isset($body['data']) || empty($body['data'])) {
                Log::info("No se encontraron datos para el dispositivo con IMEI: " . $imei);
                return ['code' => 0, 'data' => null];
            }

            // Si tiene datos, formatearlos
            return $this->formatResponse($body['data']);
        } catch (RequestException | GuzzleException $e) {
            Log::error("Error al conectar con la API de detalles de dispositivo", [
                'imei' => $imei,
                'message' => $e->getMessage()
            ]);
            return ['error' => 'Error al conectar con la API: ' . $e->getMessage()];
        } catch (\Exception $e) {
            Log::error("Error inesperado en DeviceDetail", [
                'imei' => $imei,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['error' => 'Error inesperado: ' . $e->getMessage()];
        }
    }

    /**
     * Formatea la respuesta de la API antes de devolverla.
     * 
     * @param array $data Datos recibidos de la API
     * @return array Datos formateados
     */
    private function formatResponse(array $data): array
    {
        // Verificamos si tenemos las estructuras principales
        $deviceStatus = $data['deviceStatus'] ?? [];
        $deviceBrief = $data['deviceBrief'] ?? [];
        $account = $data['account'] ?? [];
        $vehicle = $data['vehicleBean'] ?? [];

        // Creamos una estructura formateada más limpia y organizada
        $formattedData = [
            'code' => 0,
            'data' => [
                'device' => [
                    'imei' => $deviceBrief['imei'] ?? null,
                    'name' => $deviceBrief['name'] ?? null,
                    'type' => $deviceBrief['deviceType'] ?? null,
                    'sim' => $deviceBrief['deviceMobile'] ?? null,
                    'activationDate' => $this->formatTimestamp($deviceBrief['activateTime'] ?? null),
                    'expirationDate' => $this->formatTimestamp($deviceBrief['endTime'] ?? null),
                    'platformExpirationDate' => $this->formatTimestamp($deviceBrief['platformEndtime'] ?? null),
                    'note' => $deviceBrief['note'] ?? null,
                ],
                'status' => [
                    'current' => $this->getStatusText($deviceStatus['status'] ?? null),
                    'since' => $deviceStatus['statusTime'] ?? null,
                    'offlineTime' => $deviceStatus['offlineDuration'] ?? null,
                    'lastPosition' => [
                        'lat' => $deviceStatus['lat'] ?? null,
                        'lng' => $deviceStatus['lng'] ?? null,
                        'address' => $deviceStatus['address'] ?? null,
                        'locationType' => $this->getLocationType($deviceStatus['locationType'] ?? null),
                        'time' => $this->formatTimestamp($deviceStatus['gpsTime'] ?? null),
                    ],
                    'lastSignal' => $this->formatTimestamp($deviceStatus['heartTime'] ?? null),
                    'speed' => $deviceStatus['speed'] ?? 0,
                    'acc' => $deviceStatus['accStatus'] ?? false,
                    'voltage' => $deviceStatus['externalVoltage'] ?? null,
                    'battery' => $deviceStatus['batteryPercentage'] ?? null,
                    'mileage' => [
                        'today' => $deviceStatus['todayMile'] ?? '0 km',
                        'total' => $deviceStatus['totalMile'] ?? '0 km',
                    ],
                    'extras' => [
                        'fuelLevel' => $deviceStatus['fuelVolume'] ?? null,
                        'doorStatus' => $deviceStatus['doorStatus'] ?? null,
                        'acStatus' => $deviceStatus['acStatus'] ?? null,
                    ],
                ],
                'account' => [
                    'id' => $account['accountId'] ?? null,
                    'username' => $account['field_8'] ?? null,
                    'name' => $account['userName'] ?? null,
                    'contact' => $account['contactUser'] ?? null,
                    'phone' => $account['contactTel'] ?? null,
                    'email' => $account['email'] ?? null,
                    'address' => $account['address'] ?? null,
                    'renewalContact' => $account['renewalContact'] ?? null,
                ],
                'vehicle' => [
                    'licensePlate' => $vehicle['licenseNumber'] ?? null,
                    'owner' => $vehicle['carOwner'] ?? null,
                    'contact' => $vehicle['contactUser'] ?? null,
                    'phone' => $vehicle['contactTel'] ?? null,
                    'contract' => $vehicle['contractNumber'] ?? null,
                    'model' => $vehicle['carType'] ?? null,
                    'color' => $vehicle['color'] ?? null,
                    'brand' => $vehicle['carBrand'] ?? null,
                    'series' => $vehicle['carSeries'] ?? null,
                    'productionDate' => $this->formatTimestamp($vehicle['dateOfMake'] ?? null),
                    'vin' => $vehicle['vin'] ?? null,
                    'driverLicense' => $vehicle['driverLicense'] ?? null,
                    'financing' => [
                        'loanStartDate' => $this->formatTimestamp($vehicle['loanStartTime'] ?? null),
                        'loanPeriods' => $vehicle['loanPeriods'] ?? null,
                    ],
                ],
                'rawData' => $data // Incluimos los datos originales para referencia
            ]
        ];

        return $formattedData;
    }

    /**
     * Convierte un timestamp Unix en formato legible o devuelve null
     */
    private function formatTimestamp($timestamp): ?string
    {
        if (empty($timestamp) || !is_numeric($timestamp)) {
            return null;
        }

        // Verificar si el timestamp está en milisegundos (13 dígitos)
        if (strlen((string)$timestamp) === 13) {
            $timestamp = floor($timestamp / 1000);
        }

        return date('Y-m-d H:i:s', $timestamp);
    }

    /**
     * Devuelve el texto descriptivo para el estado del dispositivo
     */
    private function getStatusText($status): string
    {
        if ($status === null) {
            return 'Desconocido';
        }

        switch ($status) {
            case 0:
                return 'Estacionado';
            case 1:
                return 'En movimiento';
            case 2:
                return 'Durmiendo';
            case 10:
                return 'Desconectado';
            default:
                return 'Estado ' . $status;
        }
    }

    /**
     * Devuelve el texto descriptivo para el tipo de localización
     */
    private function getLocationType($type): string
    {
        if ($type === null) {
            return 'Desconocido';
        }

        switch ($type) {
            case 0:
                return 'Estación Base';
            case 1:
                return 'GPS';
            default:
                return 'Tipo ' . $type;
        }
    }
}
