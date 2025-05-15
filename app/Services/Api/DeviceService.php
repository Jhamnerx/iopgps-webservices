<?php

namespace App\Services\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use App\Models\Account;
use App\Models\Config;
use App\Models\Device;
use App\Models\Devices;

class DeviceService
{
    protected string $baseUrl;
    protected Client $client;

    public function __construct(Client $client)
    {
        $this->baseUrl = 'https://open.iopgps.com/api/device';
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
     * Obtiene dispositivos para todas las cuentas almacenadas.
     */
    public function fetchAndStoreDevices()
    {
        $accounts = Account::all();

        foreach ($accounts as $account) {
            $this->fetchDevicesForAccount($account->accountId);
        }
    }

    /**
     * Obtiene y almacena dispositivos para una cuenta específica con paginación automática.
     */
    public function fetchDevicesForAccount($accountId, $currentPage = 1, $pageSize = 100)
    {
        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            Log::error("No hay accessToken disponible en Redis.");
            $authTokenService = app(AuthTokenService::class);
            $token = $authTokenService->getAccessToken();

            return ['error' => 'Access token no encontrado.'];
        }


        try {
            $url = "{$this->baseUrl}?accessToken={$accessToken}&id={$accountId}&currentPage={$currentPage}&pageSize={$pageSize}";

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

            // Guardar dispositivos
            $this->storeDevices($body['data'], $accountId);

            // Manejar paginación
            if (isset($body['page'])) {
                $totalItems = $body['page']['count'];
                $currentPage = $body['page']['currentPage'];
                $pageSize = $body['page']['pageSize'];

                $totalPages = ceil($totalItems / $pageSize);

                if ($currentPage < $totalPages) {
                    $this->fetchDevicesForAccount($accountId, $currentPage + 1, $pageSize);
                }
            }

            return ['success' => 'Dispositivos guardados correctamente.'];
        } catch (RequestException | GuzzleException $e) {
            Log::error("Error al conectar con la API de dispositivos", ['message' => $e->getMessage()]);
            return ['error' => 'Error al conectar con la API'];
        } catch (\Exception $e) {
            Log::error("Error inesperado", ['message' => $e->getMessage()]);
            return ['error' => 'Error inesperado'];
        }
    }

    /**
     * Guarda o actualiza dispositivos en la base de datos.
     */
    private function storeDevices(array $devices, $accountId)
    {
        $config = Config::first();
        $servicios = collect($config->servicios)->keys()->all();

        $unitServices = [];
        // Por cada servicio, agrega una entrada con 'active' en false
        foreach ($servicios as $service) {
            $unitServices[$service] = ['active' => false];
        }



        foreach ($devices as $device) {


            $existingDevice = Devices::where('imei', $device['imei'])->first();

            if ($existingDevice) {
                // Solo actualizar los campos permitidos
                $existingDevice->update([
                    'id_api' => $device['imei'],
                    'account_id' => $accountId,
                    'name' => $device['deviceName'] ?? null,
                ]);
                // Eliminar el dispositivo de la lista de dispositivos existentes

            } else {
                // Crear un nuevo dispositivo con todos los campos

                Devices::create([
                    'id_api' => $device['imei'],
                    'account_id' => $accountId,
                    'name' => $device['deviceName'] ?? null,
                    'plate' => $device['deviceName'] ?? null,
                    'imei' => $device['imei'],
                    'services' => $unitServices,
                    'last_status' => null,
                    'last_position' => null,
                    'last_update' => now(),
                    'latest_position_id' => null,
                    'url_image' => null,
                ]);
            }
        }
    }
}
