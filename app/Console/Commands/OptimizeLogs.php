<?php

namespace App\Console\Commands;

use App\Jobs\ClearLogs;
use App\Models\Config;
use App\Models\Logs;
use App\Models\LogSummary;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OptimizeLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:optimize {--force} {--error-days=30} {--success-days=7} {--max-rows=100000} {--keep-evidence} {--no-compress}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimiza los logs eliminando registros antiguos y consolidando datos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $force = $this->option('force');
        $errorDays = $this->option('error-days');
        $successDays = $this->option('success-days');
        $maxRows = $this->option('max-rows');
        $keepEvidence = $this->option('keep-evidence');
        $compress = !$this->option('no-compress');

        $this->info('Iniciando proceso de optimización de logs...');

        // 1. Limpiar logs antiguos con diferentes políticas según tipo
        $this->info('Paso 1: Limpiando logs antiguos...');
        $this->info("   - Logs de error: eliminando registros con más de $errorDays días");
        $this->info("   - Logs de éxito: eliminando registros con más de $successDays días");

        ClearLogs::dispatch($errorDays, $successDays, $maxRows);

        // 2. Extraer información crítica de evidencia antes de eliminar logs antiguos
        if ($keepEvidence) {
            $this->info('Paso 2: Extrayendo evidencia de logs antiguos antes de eliminarlos...');
            $this->extractEvidenceFromOldLogs($successDays);
        }

        // 3. Consolidar logs exitosos en resúmenes si no existe ya un registro
        if ($compress) {
            $this->info('Paso 3: Consolidando logs exitosos...');
            $this->consolidateSuccessLogs();
        }

        // 4. Optimizar tablas
        $this->info('Paso 4: Optimizando tablas...');
        $this->optimizeTables();

        $this->info('Proceso de optimización completado con éxito.');
        $this->showStats();

        return Command::SUCCESS;
    }

    /**
     * Extrae información crítica de evidencia de logs antiguos antes de eliminarlos
     */
    private function extractEvidenceFromOldLogs($successDays)
    {
        try {
            $date = Carbon::now()->subDays($successDays);

            // Buscar logs exitosos que están por ser eliminados
            $oldLogs = Logs::where('status', 'success')
                ->where('created_at', '<', $date)
                ->limit(500)
                ->get();

            if ($oldLogs->isEmpty()) {
                $this->info('No hay logs antiguos para extraer evidencia.');
                return;
            }

            $this->info('Extrayendo evidencia de ' . $oldLogs->count() . ' logs antiguos...');

            $logService = app(\App\Services\LogService::class);
            $processedCount = 0;

            // Agrupamos por service_name, fecha, placa e imei
            $grouped = $oldLogs->groupBy(function ($log) {
                $date = Carbon::parse($log->created_at)->toDateString();
                $hour = Carbon::parse($log->created_at)->format('H');
                return "{$log->service_name}|{$date}|{$hour}|{$log->plate_number}|{$log->imei}";
            });

            foreach ($grouped as $key => $logs) {
                $parts = explode('|', $key);
                $service = $parts[0] ?? '';
                $date = $parts[1] ?? date('Y-m-d');
                $hour = $parts[2] ?? '00';
                $plate = $parts[3] ?? '';
                $imei = $parts[4] ?? '';

                // Buscar o crear un registro de resumen
                $summary = LogSummary::firstOrNew([
                    'service_name' => $service,
                    'date' => $date,
                    'hour' => $hour,
                    'plate_number' => $plate,
                    'imei' => $imei,
                ]);

                // Extraer evidencias de los logs
                $successSamples = $summary->success_samples ?? [];

                foreach ($logs as $log) {
                    // Intentar parsear la respuesta JSON
                    $response = json_decode($log->response, true);

                    if ($response) {
                        // Usar el método del LogService para extraer datos críticos
                        $evidenceData = $logService->extractEvidenceData($response);

                        if ($evidenceData && count($successSamples) < 20) {
                            $successSamples[] = [
                                'time' => Carbon::parse($log->created_at)->format('H:i:s'),
                                'status' => 'success',
                                'evidence' => $evidenceData,
                                'preserved_from' => $log->id
                            ];
                            $processedCount++;
                        }
                    }
                }

                if (!empty($successSamples)) {
                    $summary->success_samples = $successSamples;
                    $summary->save();
                }
            }

            $this->info("Se preservó evidencia de $processedCount logs antiguos en la tabla de resúmenes.");
        } catch (\Exception $e) {
            $this->error('Error al extraer evidencia de logs antiguos: ' . $e->getMessage());
            Log::error('Error en comando OptimizeLogs: ' . $e->getMessage());
        }
    }

    /**
     * Consolida logs exitosos en la tabla de resúmenes
     */
    private function consolidateSuccessLogs()
    {
        try {
            // Obtenemos los logs exitosos que aún no han sido consolidados
            $successLogs = Logs::where('status', 'success')
                ->orderBy('created_at')
                ->limit(1000)
                ->get();

            if ($successLogs->isEmpty()) {
                $this->info('No hay logs exitosos para consolidar.');
                return;
            }

            $this->info('Consolidando ' . $successLogs->count() . ' logs exitosos...');

            // Agrupamos por service_name + fecha + hora + plate_number + imei
            $groupedLogs = $successLogs->groupBy(function ($log) {
                $date = Carbon::parse($log->created_at)->toDateString();
                $hour = Carbon::parse($log->created_at)->format('H');
                return "{$log->service_name}|{$date}|{$hour}|{$log->plate_number}|{$log->imei}";
            });

            foreach ($groupedLogs as $key => $logs) {
                $parts = explode('|', $key);
                $service = $parts[0] ?? '';
                $date = $parts[1] ?? date('Y-m-d');
                $hour = $parts[2] ?? '00';
                $plate = $parts[3] ?? '';
                $imei = $parts[4] ?? '';

                // Buscar o crear un registro de resumen
                $summary = LogSummary::firstOrNew([
                    'service_name' => $service,
                    'date' => $date,
                    'hour' => $hour,
                    'plate_number' => $plate,
                    'imei' => $imei,
                ]);

                // Actualizar contadores
                $summary->success_count = ($summary->success_count ?? 0) + $logs->count();
                $summary->total_count = ($summary->total_count ?? 0) + $logs->count();

                // Extraer evidencias de los logs (hasta 20)
                $successSamples = $summary->success_samples ?? [];
                $logService = app(\App\Services\LogService::class);

                foreach ($logs as $log) {
                    if (count($successSamples) >= 20) {
                        break; // Ya tenemos suficientes muestras
                    }

                    // Intentar parsear la respuesta JSON
                    $response = json_decode($log->response, true);

                    if ($response) {
                        // Usar el método del LogService para extraer datos críticos
                        $evidenceData = $logService->extractEvidenceData($response);

                        if ($evidenceData) {
                            $successSamples[] = [
                                'time' => Carbon::parse($log->created_at)->format('H:i:s'),
                                'status' => 'success',
                                'evidence' => $evidenceData,
                                'log_id' => $log->id
                            ];
                        }
                    }
                }

                $summary->success_samples = $successSamples;
                $summary->save();

                // Eliminar los logs que ya fueron consolidados
                $logIds = $logs->pluck('id')->toArray();
                Logs::whereIn('id', $logIds)->delete();

                $this->info("Consolidados {$logs->count()} logs para {$service} / {$date} {$hour}h / {$plate}");
            }
        } catch (\Exception $e) {
            $this->error('Error al consolidar logs: ' . $e->getMessage());
            Log::error('Error en comando OptimizeLogs: ' . $e->getMessage());
        }
    }

    /**
     * Optimiza las tablas de logs
     */
    private function optimizeTables()
    {
        try {
            DB::statement("OPTIMIZE TABLE logs");
            DB::statement("OPTIMIZE TABLE log_summaries");
            $this->info('Tablas optimizadas correctamente.');
        } catch (\Exception $e) {
            $this->error('Error al optimizar tablas: ' . $e->getMessage());
        }
    }

    /**
     * Muestra las estadísticas de logs actuales
     */
    private function showStats()
    {
        $totalLogs = Logs::count();
        $successLogs = Logs::where('status', 'success')->count();
        $errorLogs = Logs::where('status', '<>', 'success')->count();
        $summaryCount = LogSummary::count();

        $this->info("\nEstadísticas actuales:");
        $this->info("---------------------------");
        $this->info("Total de logs: $totalLogs");
        $this->info("Logs exitosos: $successLogs");
        $this->info("Logs de error: $errorLogs");
        $this->info("Registros de resumen: $summaryCount");

        // Calcular el espacio aproximado que ocupan los logs
        $sizeQuery = DB::select("
            SELECT 
                ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb
            FROM 
                information_schema.tables
            WHERE 
                table_schema = DATABASE() 
                AND table_name IN ('logs', 'log_summaries')
        ");

        if (!empty($sizeQuery)) {
            $sizeMB = $sizeQuery[0]->size_mb ?? 0;
            $this->info("Espacio total ocupado: {$sizeMB} MB");
        }
    }
}
