<?php

namespace App\Http\Controllers;

use App\Models\Devices;
use App\Helpers\DeviceDetailHelper;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    protected $deviceDetailHelper;

    public function __construct(DeviceDetailHelper $deviceDetailHelper)
    {
        $this->deviceDetailHelper = $deviceDetailHelper;
    }

    /**
     * Actualizar los detalles de un dispositivo específico
     */
    public function updateDeviceDetails(Request $request, $imei)
    {
        $result = $this->deviceDetailHelper->updateDeviceDetails($imei, $request->boolean('force', false));

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró el dispositivo con el IMEI proporcionado.'
            ], 404);
        }

        return response()->json([
            'success' => $result['updated'],
            'message' => $result['message'],
            'device' => $result['device']
        ]);
    }

    /**
     * Actualizar detalles de todos los dispositivos
     */
    public function updateAllDevices(Request $request)
    {
        // Obtener todos los dispositivos con IMEI válido
        $devices = Devices::whereNotNull('imei')->get();
        $results = ['successful' => 0, 'failed' => 0, 'skipped' => 0];

        foreach ($devices as $device) {
            $result = $this->deviceDetailHelper->updateDeviceDetails($device->imei, $request->boolean('force', false));

            if (!$result) {
                $results['failed']++;
            } elseif ($result['updated']) {
                $results['successful']++;
            } else {
                $results['skipped']++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Proceso completado: {$results['successful']} actualizados, {$results['failed']} fallidos, {$results['skipped']} omitidos.",
            'results' => $results
        ]);
    }
}
