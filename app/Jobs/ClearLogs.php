<?php

namespace App\Jobs;

use App\Models\Logs;
use App\Models\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Carbon\Carbon;
use Illuminate\Foundation\Bus\Dispatchable;


class ClearLogs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $errorDays;
    protected $successDays;
    protected $maxRows;

    public function __construct(int $errorDays = null, int $successDays = null, int $maxRows = 100000)
    {
        $this->errorDays = $errorDays;
        $this->successDays = $successDays;
        $this->maxRows = $maxRows;
    }

    public function handle(): void
    {
        // Si no se proporcionaron días, obtenemos los valores de la configuración
        $config = Config::first();

        if (is_null($this->errorDays)) {
            $this->errorDays = $config->logs_retention_days['error'] ?? 30; // Por defecto 30 días para errores
        }

        if (is_null($this->successDays)) {
            $this->successDays = $config->logs_retention_days['success'] ?? 7; // Por defecto 7 días para éxitos
        }

        try {
            // 1. Limpiar logs exitosos antiguos (política más agresiva)
            $dateSuccess = Carbon::now()->subDays($this->successDays);
            $deletedSuccess = Logs::where('status', 'success')
                ->where('created_at', '<', $dateSuccess)
                ->delete();

            Log::info("Se eliminaron $deletedSuccess registros de logs exitosos (más de {$this->successDays} días)");

            // 2. Limpiar logs de error antiguos (más conservador)
            $dateError = Carbon::now()->subDays($this->errorDays);
            $deletedErrors = Logs::where('status', '<>', 'success')
                ->where('created_at', '<', $dateError)
                ->delete();

            Log::info("Se eliminaron $deletedErrors registros de logs de error (más de {$this->errorDays} días)");

            // 3. Verificamos si hay más registros que el máximo permitido
            $totalLogs = Logs::count();
            if ($totalLogs > $this->maxRows) {
                $this->truncateExcessLogs($totalLogs);
            }

            // 4. Optimizamos la tabla después de eliminar registros
            DB::statement("OPTIMIZE TABLE logs");

            // 5. Eliminar log_summaries antiguos (mantenemos solo los del último mes)
            $summaryDate = Carbon::now()->subDays(30);
            $deletedSummaries = DB::table('log_summaries')
                ->where('date', '<', $summaryDate->toDateString())
                ->delete();

            Log::info("Se eliminaron $deletedSummaries registros de resúmenes de logs (más de 30 días)");

            // 6. Optimizamos la tabla de resúmenes
            DB::statement("OPTIMIZE TABLE log_summaries");
        } catch (\Exception $e) {
            Log::error("Error al limpiar logs: " . $e->getMessage());
        }
    }

    protected function truncateExcessLogs($totalLogs)
    {
        try {
            $logsToDelete = $totalLogs - $this->maxRows;

            // Primero identificamos y eliminamos logs exitosos antiguos
            $successToDelete = (int)($logsToDelete * 0.8); // 80% de los logs a eliminar serán logs exitosos

            if ($successToDelete > 0) {
                // Obtenemos el ID del log exitoso más antiguo que debemos conservar
                $oldestSuccessToKeepId = Logs::where('status', 'success')
                    ->orderBy('id', 'desc')
                    ->skip($successToDelete)
                    ->take(1)
                    ->value('id');

                if ($oldestSuccessToKeepId) {
                    // Eliminamos todos los logs exitosos con ID menor
                    $deletedSuccess = Logs::where('status', 'success')
                        ->where('id', '<', $oldestSuccessToKeepId)
                        ->delete();

                    Log::info("Se eliminaron $deletedSuccess logs exitosos adicionales para mantener un máximo de {$this->maxRows} filas");
                    $logsToDelete -= $deletedSuccess;
                }
            }

            // Si aún necesitamos eliminar más logs, eliminamos algunos de error
            if ($logsToDelete > 0) {
                $oldestErrorToKeepId = Logs::where('status', '<>', 'success')
                    ->orderBy('id', 'desc')
                    ->skip($logsToDelete)
                    ->take(1)
                    ->value('id');

                if ($oldestErrorToKeepId) {
                    // Eliminamos logs de error con ID menor
                    $deletedErrors = Logs::where('status', '<>', 'success')
                        ->where('id', '<', $oldestErrorToKeepId)
                        ->delete();

                    Log::info("Se eliminaron $deletedErrors logs de error adicionales para mantener un máximo de {$this->maxRows} filas");
                }
            }
        } catch (\Exception $e) {
            Log::error("Error al truncar exceso de logs: " . $e->getMessage());
        }
    }
}
