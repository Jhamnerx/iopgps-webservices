<?php

namespace App\Livewire\Logs;

use App\Models\Logs;
use Livewire\Component;
use App\Models\LogSummary;
use Illuminate\Support\Facades\Artisan;

class Summary extends Component
{
    public $isOptimizing = false;
    public $optimizationMessage = '';

    public function refreshTable()
    {
        $this->dispatch('pg:eventRefresh-LogSummaryTable');
    }

    public function render()
    {
        return view('livewire.logs.summary')
            ->layout('layouts.livewire');
    }

    public function getStats()
    {
        $totalLogs = Logs::count();
        $totalSummaries = LogSummary::count();

        return [
            'total_logs' => $totalLogs,
            'total_summaries' => $totalSummaries,
        ];
    }

    public function optimizeLogs()
    {
        try {
            $this->isOptimizing = true;
            $this->optimizationMessage = 'Iniciando optimización de logs...';

            // Ejecutar el comando en segundo plano
            Artisan::queue('logs:optimize', [
                '--success-days' => 7,
                '--error-days' => 30,
                '--keep-evidence' => true
            ]);

            // Notificar al usuario
            $this->dispatch('notify-toast', [
                'icon' => 'info',
                'title' => 'Optimización en progreso',
                'mensaje' => 'El proceso de optimización de logs se está ejecutando en segundo plano. Esto puede tardar unos minutos.',
                'timer' => 5000
            ]);

            // Refrescar la tabla después de un tiempo para mostrar los resultados
            sleep(2); // Pequeña pausa para dar tiempo a que inicie el proceso
            $this->refreshTable();
        } catch (\Exception $e) {
            $this->dispatch('error', [
                'title' => 'Error al optimizar logs',
                'mensaje' => 'Ha ocurrido un error: ' . $e->getMessage()
            ]);
        } finally {
            $this->isOptimizing = false;
        }
    }
}
