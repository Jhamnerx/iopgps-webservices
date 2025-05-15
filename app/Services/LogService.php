<?php

namespace App\Services;

use App\Models\Logs;
use App\Models\Config;
use App\Models\LogSummary;
use Carbon\Carbon;

class LogService
{
    protected $config;
    protected $maxRequestSize = 1000; // Caracteres máximos para request
    protected $maxResponseSize = 500; // Caracteres máximos para response
    protected $useSummaryLogging = true; // Activar sistema de logs resumidos
    protected $keepSuccessLogs = true; // Mantener logs exitosos completos

    public function __construct()
    {
        try {
            $this->config = app(Config::class)->first();
            if (isset($this->config->log_settings)) {
                $this->useSummaryLogging = $this->config->log_settings['use_summary_logging'] ?? true;
                $this->keepSuccessLogs = $this->config->log_settings['keep_success_logs'] ?? true;
                $this->maxRequestSize = $this->config->log_settings['max_request_size'] ?? 1000;
                $this->maxResponseSize = $this->config->log_settings['max_response_size'] ?? 500;
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error al inicializar LogService: " . $e->getMessage());
        }
    }

    /**
     * Guardar un log en la base de datos.
     * Ahora con optimizaciones de almacenamiento para reducir el tamaño.
     *
     * @param string $proveedor El proveedor del servicio
     * @param string $service El nombre del servicio web.
     * @param string $plate El número de la placa.
     * @param string $status El estado de la petición
     * @param array $trama Los datos enviados
     * @param array $response La respuesta recibida
     * @param array $additionalData Datos adicionales opcionales.
     * @param string|null $datePosicion La fecha de la posición
     * @param string|null $imei El IMEI del dispositivo
     * @return void
     */
    public function logToDatabase($proveedor, $service, $plate, $status = '', $trama = [], $response = [], $additionalData = [], $datePosicion = null, $imei = null): void
    {
        // Verificar si debemos almacenar logs basados en la configuración
        if (isset($this->config->servicios[$service]['enabled_logs']) && $this->config->servicios[$service]['enabled_logs'] === false) {
            return;
        }

        // Si la respuesta fue exitosa y no queremos guardar logs exitosos, salimos
        if ($status === 'success' && isset($this->config->servicios[$service]['log_only_errors']) && $this->config->servicios[$service]['log_only_errors'] === true) {
            return;
        }

        // Usar el sistema de logs resumidos si está activado
        if ($this->useSummaryLogging) {
            $this->updateLogSummary($service, $plate, $status, $trama, $response, $imei);
        }

        // Siempre guardamos logs completos (exitosos y errores)
        // Los logs exitosos tienen una política de retención más agresiva
        $this->saveDetailedLog($service, $plate, $status, $trama, $response, $additionalData, $datePosicion, $imei);
    }

    /**
     * Guarda un log detallado en la tabla logs
     */
    protected function saveDetailedLog($service, $plate, $status, $trama, $response, $additionalData, $datePosicion, $imei): void
    {
        // Comprimimos y limitamos el tamaño de la petición
        $requestJson = $this->optimizeJson($trama);

        // Comprimimos y limitamos el tamaño de la respuesta
        $responseJson = $this->optimizeJson($response);

        try {
            // Extraer el token y datos críticos de la respuesta para evidencia
            $evidenceData = $this->extractEvidenceData($response);

            Logs::create([
                'service_name' => $service,
                'method' => 'POST',
                'date' => Carbon::now()->format('Y-m-d H:i:s'),
                'plate_number' => $plate,
                'request' => $requestJson,
                'response' => $responseJson,
                'status' => $status,
                'additional_data' => empty($additionalData) ? ($evidenceData ? json_encode($evidenceData) : null) : json_encode(array_merge($additionalData, $evidenceData ?: [])),
                'fecha_hora_posicion' => $datePosicion,
                'imei' => $imei,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error al guardar log en base de datos: " . $e->getMessage());
        }
    }

    /**
     * Actualiza el resumen de logs en la tabla log_summaries
     */
    protected function updateLogSummary($service, $plate, $status, $trama, $response, $imei): void
    {
        try {
            $now = Carbon::now();
            $date = $now->toDateString();
            $hour = $now->format('H');

            // Buscar o crear registro de resumen
            $summary = LogSummary::firstOrNew([
                'service_name' => $service,
                'date' => $date,
                'hour' => $hour,
                'plate_number' => $plate,
                'imei' => $imei,
            ]);

            // Incrementar contadores
            $summary->total_count = ($summary->total_count ?? 0) + 1;

            if ($status === 'success') {
                $summary->success_count = ($summary->success_count ?? 0) + 1;

                // Para las respuestas exitosas, guardamos información crítica para evidencia
                $evidenceData = $this->extractEvidenceData($response);
                if ($evidenceData) {
                    // Guardar hasta 20 ejemplos de respuestas exitosas
                    $successSamples = $summary->success_samples ?? [];
                    if (count($successSamples) < 20) {
                        $successSamples[] = [
                            'time' => $now->format('H:i:s'),
                            'status' => $status,
                            'evidence' => $evidenceData
                        ];
                        $summary->success_samples = $successSamples;
                    }
                }
            } else {
                $summary->error_count = ($summary->error_count ?? 0) + 1;

                // Guardar hasta 5 ejemplos de errores
                $errorSamples = $summary->error_samples ?? [];

                // Solo guardamos hasta 5 ejemplos de errores
                if (count($errorSamples) < 5) {
                    $errorSamples[] = [
                        'time' => $now->format('H:i:s'),
                        'status' => $status,
                        'response' => $this->truncateText(json_encode($response), 200)
                    ];
                    $summary->error_samples = $errorSamples;
                }
            }

            $summary->save();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error al actualizar resumen de logs: " . $e->getMessage());
        }
    }

    /**
     * Extrae datos críticos de evidencia de la respuesta
     * 
     * @param array $response La respuesta a analizar
     * @return array|null Datos de evidencia extraídos o null si no hay datos relevantes
     */
    protected function extractEvidenceData($response): ?array
    {
        if (empty($response)) {
            return null;
        }

        $evidence = [];

        // Extraer timestamp si existe
        if (isset($response['message']['timestamp'])) {
            $evidence['timestamp'] = $response['message']['timestamp'];
        }

        // Extraer status si existe
        if (isset($response['status'])) {
            $evidence['status'] = $response['status'];
        } elseif (isset($response['message']['status'])) {
            $evidence['status'] = $response['message']['status'];
        }

        // Extraer ID o token único si existe
        if (isset($response['id'])) {
            $evidence['id'] = $response['id'];
        } elseif (isset($response['token'])) {
            $evidence['token'] = $response['token'];
        }

        return !empty($evidence) ? $evidence : null;
    }

    /**
     * Comprime y limita el tamaño del JSON para optimizar almacenamiento
     * 
     * @param array $data Los datos a comprimir
     * @return string JSON optimizado
     */
    protected function optimizeJson($data): string
    {
        if (empty($data)) {
            return null;
        }

        // Intentamos optimizar el array antes de convertirlo a JSON
        $optimizedData = $this->optimizeDataStructure($data);

        // Convertimos a JSON sin espacios adicionales para ahorrar espacio
        $json = json_encode($optimizedData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // Si el JSON es muy grande, lo truncamos
        if (strlen($json) > $this->maxRequestSize) {
            $json = substr($json, 0, $this->maxRequestSize) . '...';
        }

        return $json;
    }

    /**
     * Optimiza estructuras de datos antes de convertirlas a JSON
     * Elimina datos redundantes o demasiado grandes
     * 
     * @param array $data Datos a optimizar
     * @return array Datos optimizados
     */
    protected function optimizeDataStructure($data): array
    {
        // Si no es un array, lo devolvemos tal cual
        if (!is_array($data)) {
            return $data;
        }

        $result = [];

        // Procesamos el array para optimizar su tamaño
        foreach ($data as $key => $value) {
            // Si es un array anidado, lo procesamos recursivamente
            if (is_array($value)) {
                // Si tiene más de 10 elementos, limitamos a los primeros 5
                if (count($value) > 10) {
                    $value = array_slice($value, 0, 5);
                    $value['...'] = '(truncado)';
                }
                $result[$key] = $this->optimizeDataStructure($value);
            }
            // Si es un string muy largo, lo truncamos
            elseif (is_string($value) && strlen($value) > 100) {
                $result[$key] = substr($value, 0, 100) . '...';
            }
            // Otros valores los mantenemos igual
            else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Trunca un texto a la longitud máxima especificada
     */
    protected function truncateText($text, $maxLength): string
    {
        if (strlen($text) <= $maxLength) {
            return $text;
        }

        return substr($text, 0, $maxLength) . '...';
    }
}
