<?php

namespace App\Services\Formatters;

use DateTime;
use DateTimeZone;
use App\Models\Config;
use App\Models\Devices;
use App\Services\Transformers\UnitTransformer;

class SidersalesFormatter implements UnitFormatterInterface
{
    protected UnitTransformer $transformer;

    // URLs por entorno — declaradas como constantes
    const URL_DEV = 'https://dev-experience-gerdau-sidersales-pe.us-e1.cloudhub.io/api/gps-tracking';
    const URL_QA  = 'https://qa-experience-gerdau-sidersales-pe.us-e1.cloudhub.io/api/gps-tracking';
    const URL_PRD = 'https://api.gerdau.com/experiencepe/api/gps-tracking';

    const PROVEEDOR_GPS = 'VINITRACKER';

    public function __construct(UnitTransformer $transformer)
    {
        $this->transformer = $transformer;
    }

    /**
     * Devuelve la URL según el entorno configurado en la base de datos.
     */
    public static function getUrl(?string $environment = null): string
    {
        $env = $environment ?? Config::first()?->servicios['sidersales']['environment'] ?? 'dev';

        return match ($env) {
            'prd'   => self::URL_PRD,
            'qa'    => self::URL_QA,
            default => self::URL_DEV,
        };
    }

    /**
     * Formatea una lista de unidades al payload esperado por Sidersales.
     * Sidersales recibe posiciones una a una (no en bulk).
     *
     * @param array $units
     * @return array  Array de payloads individuales listos para enviar
     */
    public function format(array $units): array
    {
        $normalizedUnits = $this->transformer->transform($units);

        $payloads = [];

        foreach ($normalizedUnits as $unit) {
            $device = Devices::where('imei', $unit['imei'])->first();

            if (!$device) {
                continue;
            }

            $plate = trim($device->plate);

            $date = new DateTime('@' . $unit['signalTime']);
            $date->setTimezone(new DateTimeZone('America/Lima'));
            $fechaHoraGPS = $date->format('Y-m-d H:i:s');

            $payloads[] = [
                'placa'             => $plate,
                'latitud'           => (string) $unit['location']['lat'],
                'longitud'          => (string) $unit['location']['lng'],
                'velocidadKPH'      => (string) intval($unit['speed']),
                'fechaHoraGPS'      => $fechaHoraGPS,
                'rucTransportista'  => $device->services['sidersales']['ruc_transportista'] ?? '',
                'ProveedorGPS'      => self::PROVEEDOR_GPS,
                // Meta interna (no se envía a la API, se usa para logging y actualización del dispositivo)
                '_imei'   => $unit['imei'],
                '_id'     => $unit['id'] ?? null,
            ];
        }

        return $payloads;
    }
}
