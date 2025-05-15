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
        // Verificar si ProcessUnitsJob está en ejecución
        if (Redis::exists('iopgps_process_units_in_progress')) {
            $startTime = Redis::get('iopgps_process_units_in_progress');
            $elapsedTime = time() - $startTime;

            Log::info("ProcessUnitsJob en ejecución (iniciado hace {$elapsedTime} segundos). Esperando a que termine...");

            // Esperar hasta 30 segundos para que finalice ProcessUnitsJob
            $waitCount = 0;
            while (Redis::exists('iopgps_process_units_in_progress') && $waitCount < 30) {
                sleep(1);
                $waitCount++;
            }

            if (Redis::exists('iopgps_process_units_in_progress')) {
                Log::warning("ProcessUnitsJob sigue en ejecución después de esperar 30 segundos. Continuando de todas formas.");
            } else {
                Log::info("ProcessUnitsJob completado. Continuando con DeviceService.");
            }
        }

        // Establecer un lock en Redis para indicar que DeviceService está en uso
        Redis::set('iopgps_device_service_in_progress', time(), 'EX', 300); // Expira en 5 minutos por seguridad

        try {
            $accounts = Account::all();

            foreach ($accounts as $account) {
                $this->fetchDevicesForAccount($account->accountId);
            }
        } finally {
            // Liberar el lock cuando terminemos
            Redis::del('iopgps_device_service_in_progress');
            Log::info("DeviceService completado y lock liberado");
        }
    }
    /**
     * Obtiene y almacena dispositivos para una cuenta específica con paginación automática.
     */
    public function fetchDevicesForAccount($accountId, $currentPage = 1, $pageSize = 100)
    {
        // Si es la primera página, verificamos si ProcessUnitsJob está en ejecución
        if ($currentPage === 1 && Redis::exists('iopgps_process_units_in_progress')) {
            $startTime = Redis::get('iopgps_process_units_in_progress');
            $elapsedTime = time() - $startTime;

            Log::info("ProcessUnitsJob en ejecución al iniciar fetchDevicesForAccount (iniciado hace {$elapsedTime} segundos).");

            // Esperar hasta 20 segundos para que finalice ProcessUnitsJob
            $waitCount = 0;
            while (Redis::exists('iopgps_process_units_in_progress') && $waitCount < 20) {
                sleep(1);
                $waitCount++;
            }

            if (Redis::exists('iopgps_process_units_in_progress')) {
                Log::warning("ProcessUnitsJob sigue en ejecución después de esperar 20 segundos. Continuando de todas formas.");
            }
        }

        // Solo establecemos el lock si es la primera página
        if ($currentPage === 1) {
            Redis::set('iopgps_device_service_in_progress', time(), 'EX', 300);
        }

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
                } else if ($currentPage === 1 || $currentPage >= $totalPages) {
                    // Si es la primera página y no hay más páginas, o si es la última página
                    Redis::del('iopgps_device_service_in_progress');
                    Log::info("DeviceService para cuenta {$accountId} completado y lock liberado");
                }
            } else {
                // Si no hay información de paginación, asumimos que es la única página
                if ($currentPage === 1) {
                    Redis::del('iopgps_device_service_in_progress');
                    Log::info("DeviceService para cuenta {$accountId} completado y lock liberado (sin paginación)");
                }
            }
            return ['success' => 'Dispositivos guardados correctamente.'];
        } catch (RequestException | GuzzleException $e) {
            // Liberar el lock en caso de error (solo si es la primera página)
            if ($currentPage === 1) {
                Redis::del('iopgps_device_service_in_progress');
                Log::info("DeviceService para cuenta {$accountId} falló y lock liberado por error");
            }

            Log::error("Error al conectar con la API de dispositivos", ['message' => $e->getMessage()]);
            return ['error' => 'Error al conectar con la API'];
        } catch (\Exception $e) {
            // Liberar el lock en caso de error (solo si es la primera página)
            if ($currentPage === 1) {
                Redis::del('iopgps_device_service_in_progress');
                Log::info("DeviceService para cuenta {$accountId} falló y lock liberado por error");
            }

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
