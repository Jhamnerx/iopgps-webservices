<?php

namespace App\Services\Senders;

use App\Models\Config;
use App\Models\Devices;
use GuzzleHttp\Client;
use App\Services\LogService;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Exception\RequestException;


class SutranSender implements UnitSenderInterface
{
    public $logService;
    public $config;

    public function __construct()
    {
        $this->logService = app(LogService::class);
        $this->config = Config::first();
    }


    public function send(array $tramas, $url): void
    {

        $token = $this->config->servicios['sutran']['token'];

        try {
            $client = new Client(['verify' => false]);
            $response = $client->request('POST', $url, [
                'headers' => [
                    'access-token' => $token,
                    'Content-Type' => 'application/json',
                ],
                'json' => $tramas,
            ]);

            $responseSutran = $response->getBody()->getContents();

            $this->actionAfterSend($tramas, json_decode($responseSutran, true));
        } catch (RequestException $e) {
            if ($e->hasResponse()) {

                $response = $e->getResponse();
                $body = $response->getBody()->getContents();

                // Guardar log de error en la base de datos
                $this->logService->logToDatabase(
                    '',
                    'Sutran',
                    'N/A',
                    'error',
                    $tramas,
                    ['message - token ' . $token . '' => $body],
                    [],
                    null,
                    null
                );
            }
        }
    }

    public function actionAfterSend($tramas, $response)
    {

        $totalSent = count($tramas);
        $successCount = 0;
        $errorCount = 0;

        if ($response['status'] == 200 && empty($response['error_plates'])) {
            $successCount = $totalSent;

            if ($this->config->servicios['sutran']['enabled_logs']) {
                foreach ($tramas as $trama) {

                    $this->logService->logToDatabase(
                        '',
                        'Sutran',
                        $trama['plate'],
                        'success',
                        $trama,
                        ['message' => 'Registrado correctamente'],
                        [],
                        $trama['time_device'],
                        $trama['imei']
                    );
                }
            }

            foreach ($tramas as $trama) {
                Devices::where('imei', $trama['imei'])->first()->update([
                    'last_status' => $trama['event'],
                    'last_position' => $trama['geo'],
                    'last_update' => $trama['time_device'],
                    'latest_position_id' => $trama['idTrama'],
                ]);
            }
        } else {
            // Manejar tramas con errores
            $errored_rows = [];
            foreach ($response['error_plates'] as $error) {
                if (preg_match('/F:(\d+)/', $error['message'], $matches)) {
                    $errored_rows[intval($matches[1])] = $error['message'];
                }
            }

            foreach ($tramas as $index => $trama) {
                if (array_key_exists($index, $errored_rows)) {
                    $errorCount++;

                    if ($this->config->servicios['sutran']['enabled_logs']) {

                        $this->logService->logToDatabase(
                            '',
                            'Sutran',
                            $trama['plate'],
                            'error',
                            $trama,
                            ['message' => $errored_rows[$index]],
                            [],
                            $trama['time_device'],
                            $trama['imei']
                        );
                    }
                } else {
                    $successCount++;


                    if ($this->config->servicios['sutran']['enabled_logs']) {

                        $this->logService->logToDatabase(
                            '',
                            'Sutran',
                            $trama['plate'],
                            'success',
                            $trama,
                            ['message' => 'Registrado correctamente'],
                            [],
                            $trama['time_device'],
                            $trama['imei']
                        );
                    }

                    Devices::where('imei', $trama['id'])->first()->update([
                        'last_status' => $trama['event'],
                        'last_position' => $trama['geo'],
                        'last_update' => $trama['time_device'],
                        'latest_position_id' => $trama['idTrama'],
                    ]);
                }
            }
        }

        DB::transaction(function () use ($totalSent, $successCount, $errorCount) {
            $counterService = $this->config->counterServices()->firstOrCreate(
                [
                    'serviceable_type' => Config::class,
                    'serviceable_id' => $this->config->id,
                ],
                ['data' => []]
            );

            $data = $counterService->data ?? [];

            $data['sent'] = ($data['sent'] ?? 0) + $totalSent;
            $data['success'] = ($data['success'] ?? 0) + $successCount;
            $data['failed'] = ($data['failed'] ?? 0) + $errorCount;
            $data['last_error'] = $errorCount > 0 ? 'Errores en algunas tramas' : null;
            $data['last_attempt'] = now()->toDateTimeString();

            $counterService->update(['data' => $data]);
        });
    }

    private function getConfigModelBySource($source): string
    {
        switch ($source) {
            case 'WoxDevices':
                return 'App\Models\WoxConfig';
            case 'WialonDevices':
                return 'App\Models\WialonConfig';
            case 'NavixyDevices':
                return 'App\Models\NavixyConfig';
            default:
                throw new \Exception("Modelo de configuración desconocido: $source");
        }
    }

    private function getDevicesModelBySource($source): string
    {
        switch ($source) {
            case 'WoxDevices':
                return 'App\Models\WoxDevices';
            case 'WialonDevices':
                return 'App\Models\WialonDevices';
            default:
                throw new \Exception("Modelo de configuración desconocido: $source");
        }
    }
    private function getIdFieldBySource($source): string
    {
        switch ($source) {
            case 'WoxDevices':
                return 'id_wox';
            case 'WialonDevices':
                return 'id_wialon';
            case 'NavixyDevices':
                return 'id_navixy';
            default:
                throw new \Exception("Modelo desconocido: $source");
        }
    }
}
