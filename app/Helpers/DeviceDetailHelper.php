<?php

namespace App\Helpers;

use App\Models\Account;
use App\Models\Devices;
use App\Services\Api\DeviceDetail;
use Illuminate\Support\Facades\Log;

class DeviceDetailHelper
{
    protected $deviceDetailService;

    public function __construct(DeviceDetail $deviceDetailService)
    {
        $this->deviceDetailService = $deviceDetailService;
    }

    /**
     * Actualiza los detalles de un dispositivo por su IMEI
     * Enfocado principalmente en la información de la cuenta asociada
     * 
     * @param string $imei El IMEI del dispositivo a actualizar
     * @param bool $force Si es true, fuerza la actualización aunque se haya actualizado recientemente
     * @return array|null Datos del dispositivo o null si no se pudo actualizar
     */
    public function updateDeviceDetails(string $imei, bool $force = false): ?array
    {
        try {
            // Buscar el dispositivo por IMEI
            $device = Devices::where('imei', $imei)->first();

            if (!$device) {
                Log::warning("No se encontró el dispositivo con IMEI: {$imei}");
                return null;
            }

            // Verificar si debemos actualizar (solo una vez al día, a menos que se fuerce)
            if (
                !$force && $device->details_last_updated &&
                now()->diffInHours($device->details_last_updated) < 24
            ) {
                Log::info("Detalles del dispositivo {$imei} ya actualizados en las últimas 24 horas.");
                return [
                    'device' => $device,
                    'updated' => false,
                    'message' => 'Detalles actualizados recientemente'
                ];
            }

            // Obtener detalles desde la API
            $response = $this->deviceDetailService->fetchDeviceDetail($imei);

            // Verificar si hubo algún error
            if (isset($response['error'])) {
                Log::error("Error al obtener detalles del dispositivo {$imei}: {$response['error']}");
                return [
                    'device' => $device,
                    'updated' => false,
                    'message' => 'Error: ' . $response['error']
                ];
            }

            // Verificar si hay datos en la respuesta
            if (empty($response['data'])) {
                Log::warning("No se recibieron datos para el dispositivo {$imei}");
                return [
                    'device' => $device,
                    'updated' => false,
                    'message' => 'Sin datos disponibles'
                ];
            }

            // Extraer los datos formateados
            $data = $response['data'];
            $deviceData = $data['device'] ?? [];
            $statusData = $data['status'] ?? [];
            $vehicleData = $data['vehicle'] ?? [];
            $accountData = $data['account'] ?? [];

            // Actualizar principalmente la información de la cuenta
            $this->updateAccountInformation($device, $accountData);

            // Actualizar información básica del dispositivo
            $this->updateBasicDeviceInfo($device, $deviceData, $vehicleData);

            Log::info("Detalles del dispositivo {$imei} actualizados correctamente");

            return [
                'device' => $device,
                'updated' => true,
                'message' => 'Detalles actualizados correctamente'
            ];
        } catch (\Exception $e) {
            Log::error("Error al actualizar detalles del dispositivo {$imei}: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'device' => $device ?? null,
                'updated' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Actualiza solo la información de la cuenta asociada al dispositivo
     * 
     * @param Devices $device El dispositivo a actualizar
     * @param array $accountData Datos de la cuenta obtenidos de la API
     * @return Account|null La cuenta actualizada o null si no se pudo actualizar
     */
    public function updateAccountInformation(Devices $device, array $accountData): ?Account
    {
        // Si no hay datos de cuenta, no hacemos nada
        if (empty($accountData) || empty($accountData['id'])) {
            Log::info("No hay datos de cuenta para actualizar del dispositivo {$device->imei}");
            return null;
        }

        $accountId = $accountData['id'];

        // Buscar o crear la cuenta en la base de datos
        $account = Account::firstOrCreate(
            ['accountId' => $accountId],
            [
                'userName' => $accountData['name'] ?? '',
                'account' => $accountData['username'] ?? '',
                'parentAccountId' => null // Si necesitas establecer una cuenta padre, deberías buscarla primero
            ]
        );

        // Actualizar campos adicionales de la cuenta
        $account->contactUser = $accountData['contact'] ?? $account->contactUser;
        $account->contactTel = $accountData['phone'] ?? $account->contactTel;
        $account->email = $accountData['email'] ?? $account->email;
        $account->address = $accountData['address'] ?? $account->address;
        $account->renewalContact = $accountData['renewalContact'] ?? $account->renewalContact;

        // Almacenar datos adicionales que puedan ser útiles pero no tienen campo específico
        $additionalData = $account->additional_data ? $account->additional_data : [];
        $newData = [];

        // Agregar cualquier otro dato que pueda ser útil
        foreach ($accountData as $key => $value) {
            if (!in_array($key, ['id', 'name', 'username', 'contact', 'phone', 'email', 'address', 'renewalContact'])) {
                $newData[$key] = $value;
            }
        }

        if (!empty($newData)) {
            $account->additional_data = array_merge($additionalData, $newData);
        }

        // Actualizar la fecha de última actualización
        $account->details_last_updated = now();
        $account->save();

        // Asociar la cuenta con el dispositivo (usando accountId, no id)
        $device->account_id = $account->accountId;
        $device->save();

        Log::info("Dispositivo {$device->imei} asociado a la cuenta {$accountId}. Información de cuenta actualizada.");

        return $account;
    }

    /**
     * Actualiza la información básica del dispositivo
     * 
     * @param Devices $device El dispositivo a actualizar
     * @param array $deviceData Datos del dispositivo obtenidos de la API
     * @param array $vehicleData Datos del vehículo obtenidos de la API
     * @return Devices El dispositivo actualizado
     */
    public function updateBasicDeviceInfo(Devices $device, array $deviceData, array $vehicleData = []): Devices
    {
        // Actualizar información básica del dispositivo
        $device->name = $deviceData['name'] ?? $device->name;
        $device->plate = $vehicleData['licensePlate'] ?? $device->plate;

        // Marcar como actualizado
        $device->details_last_updated = now();

        // Guardar los cambios
        $device->save();

        return $device;
    }

    /**
     * Actualiza la información completa del dispositivo (uso opcional)
     * 
     * @param Devices $device El dispositivo a actualizar
     * @param array $deviceData Datos del dispositivo obtenidos de la API
     * @param array $statusData Datos del estado obtenidos de la API
     * @param array $vehicleData Datos del vehículo obtenidos de la API
     * @return Devices El dispositivo actualizado
     */
    public function updateDetailedDeviceInfo(Devices $device, array $deviceData, array $statusData = [], array $vehicleData = []): Devices
    {
        // Actualizar información básica primero
        $this->updateBasicDeviceInfo($device, $deviceData, $vehicleData);

        // Aquí puedes añadir lógica para actualizar información más detallada del dispositivo
        // Por ejemplo, estado, configuraciones específicas, etc.

        // Ejemplo de actualización de campos adicionales (ajustar según sea necesario)
        if (!empty($statusData)) {
            // Actualizar información de estado si es necesario
            // $device->estado = $statusData['status'] ?? $device->estado;
            // $device->bateria = $statusData['battery'] ?? $device->bateria;
            // etc.
        }

        if (!empty($vehicleData)) {
            // Actualizar información del vehículo si es necesario
            // $device->modelo = $vehicleData['model'] ?? $device->modelo;
            // $device->color = $vehicleData['color'] ?? $device->color;
            // etc.
        }

        // Guardar los cambios
        $device->save();

        return $device;
    }

    /**
     * Actualiza en masa los detalles de varios dispositivos
     * 
     * @param array $imeis Lista de IMEIs a actualizar
     * @param bool $force Si es true, fuerza la actualización aunque se haya actualizado recientemente
     * @return array Resultados de las actualizaciones
     */
    public function updateMultipleDevices(array $imeis, bool $force = false): array
    {
        $results = [];

        foreach ($imeis as $imei) {
            $results[$imei] = $this->updateDeviceDetails($imei, $force);
        }

        return $results;
    }
}
