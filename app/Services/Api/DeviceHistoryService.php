<?php

namespace App\Services\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DeviceHistoryService
{
    protected string $baseUrl;
    protected Client $client;

    public function __construct(Client $client)
    {
        $this->baseUrl = 'https://open.iopgps.com/api/device/track/history';
        $this->client = $client;
    }


    private function getAccessToken(): ?string
    {
        return Redis::get('iopgps_access_token');
    }


    private function convertToTimestamp(string $date): int
    {
        return Carbon::parse($date, 'America/Lima')->utc()->timestamp;
    }


    public function fetchDeviceHistory(string $imei, string $startDate, ?string $endDate = null, string $onlyGps = '0', string $coordType = 'wgs84'): array
    {
        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            Log::error("No hay accessToken disponible en Redis.");
            return ['error' => 'Access token no encontrado.'];
        }

        try {
            $startTime = $this->convertToTimestamp($startDate);
            $endTime = $endDate ? $this->convertToTimestamp($endDate) : null;

            $query = [
                'accessToken' => $accessToken,
                'imei' => $imei,
                'startTime' => $startTime,
                'coordType' => $coordType,
                'onlyGps' => $onlyGps
            ];

            if ($endTime) {
                $query['endTime'] = $endTime;
            }

            $url = "{$this->baseUrl}?" . http_build_query($query);

            $response = $this->client->request('GET', $url, [
                'timeout' => 10,
            ]);

            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);

            if ($statusCode !== 200 || empty($body) || !isset($body['code'])) {
                Log::error("Respuesta inesperada de la API", ['statusCode' => $statusCode, 'body' => $body]);
                return ['error' => 'Respuesta inesperada de la API'];
            }

            if ($body['code'] !== 0) {
                Log::error("Error en respuesta API", ['response' => $body]);
                return ['error' => $body['result'] ?? 'Error desconocido'];
            }

            return $this->formatResponse($body['data']);
        } catch (RequestException | GuzzleException $e) {

            Log::error("Error al conectar con la API de historial de dispositivos", ['message' => $e->getMessage()]);
            return ['error' => 'Error al conectar con la API'];
        } catch (\Exception $e) {
            Log::error("Error inesperado", ['message' => $e->getMessage()]);
            return ['error' => 'Error inesperado'];
        }
    }


    private function formatResponse(array $data): array
    {
        $formattedData = [];

        foreach ($data as $record) {
            $formattedData[] = [
                'imei' => $record['imei'],
                'location' => [
                    'lng' => $record['lng'],
                    'lat' => $record['lat'],
                ],
                'speed' => $record['speed'],
                'course' => $record['course'],
                'accStatus' => $record['accStatus'] ?? null,
                'gpsTime' => $record['gpsTime'],
                'positionType' => $record['positionType']
            ];
        }

        return ['code' => 0, 'data' => $formattedData];
    }
}
