<?php

namespace App\Services\Senders;

use App\Models\Config;
use App\Models\Devices;
use Carbon\Carbon;
use GuzzleHttp\Client;
use App\Services\LogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\RequestException;


class OsinergminSender implements UnitSenderInterface
{
    public $logService;
    protected $config;

    public function __construct()
    {
        $this->logService = app(LogService::class);
        $this->config = Config::first();
    }

    public function send(array $tramas, $url): void
    {
        $client = new Client(['verify' => false]);

        $successCount = 0;
        $failedCount = 0;

        foreach ($tramas as $trama) {
            try {
                $response = $client->request('POST', $url, [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'body' => json_encode($trama),
                ]);

                $responseBody = json_decode($response->getBody()->getContents(), true);

                if ($responseBody['status'] === 'CREATED') {
                    $successCount++;
                    $this->handleSuccess($responseBody, $trama);
                } else {
                    $failedCount++;
                    $this->handleError($responseBody, $trama);
                }
            } catch (RequestException $e) {

                $failedCount++;

                if ($this->config->servicios['osinergmin']['enabled_logs']) {
                    $this->logError($e, $trama);
                }
            }
        }

        // Actualizar contadores globales después de procesar todas las tramas
        $this->updateCounterService($successCount, $failedCount, count($tramas));
    }

    protected function handleSuccess(array $response, array $trama): void
    {
        if ($this->config->servicios['osinergmin']['enabled_logs']) {

            $this->logService->logToDatabase(
                '',
                'Osinergmin',
                $trama['plate'],
                'success',
                $trama,
                ['message' => $response],
                [],
                Carbon::parse($trama['gpsDate'])->setTimezone('America/Lima')->format('Y-m-d H:i:s'),
                $trama['imei']

            );
        }
        $device = Devices::where('imei', $trama['imei'])->first();

        $device->update([
            'last_status' => $trama['event'],
            'last_position' => $trama['position'],
            'last_update' => Carbon::parse($trama['gpsDate'])->setTimezone('America/Lima')->format('Y-m-d H:i:s'),
            'latest_position_id' => $trama['idTrama'],
        ]);
    }

    protected function handleError(array $response, array $trama): void
    {
        if ($this->config->servicios['osinergmin']['enabled_logs']) {

            $this->logService->logToDatabase(
                '',
                'Osinergmin',
                $trama['plate'],
                'error',
                $trama,
                ['message' => $response['message'] . ' - ' . ($response['suggestion'] ?? 'No suggestion')],
                [],
                Carbon::parse($trama['gpsDate'])->setTimezone('America/Lima')->format('Y-m-d H:i:s'),
                $trama['imei']
            );
        }
    }

    protected function logError(RequestException $e, array $trama): void
    {
        if ($e->hasResponse()) {
            $response = $e->getResponse();
            $body = json_decode($response->getBody()->getContents(), true);

            $this->logService->logToDatabase(
                '',
                'Osinergmin',
                $trama['plate'],
                $body['status'],
                $trama,
                ['message' => $body['message'] . ' - ' . ($body['suggestion'] ?? 'No suggestion')],
                [],
                Carbon::parse($trama['gpsDate'])->setTimezone('America/Lima')->format('Y-m-d H:i:s'),
                $trama['imei']
            );
        }
    }

    protected function updateCounterService(int $successCount, int $failedCount, int $totalSent): void
    {
        DB::transaction(function () use ($successCount, $failedCount, $totalSent) {
            $counterService = $this->config->counterServices()->firstOrCreate(
                [
                    'serviceable_type' => Devices::class,
                    'serviceable_id' => $this->config->id,
                ],
                ['data' => []]
            );

            $data = $counterService->data ?? [];

            $data['sent'] = ($data['sent'] ?? 0) + $totalSent;
            $data['success'] = ($data['success'] ?? 0) + $successCount;
            $data['failed'] = ($data['failed'] ?? 0) + $failedCount;
            $data['last_error'] = $failedCount > 0 ? 'Errores en algunas tramas' : null;
            $data['last_attempt'] = now()->toDateTimeString();

            $counterService->update(['data' => $data]);
        });
    }
}
