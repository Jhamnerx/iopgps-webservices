<?php

namespace App\Jobs;

use App\Services\Senders\SidersalesSender;
use App\Services\Formatters\SidersalesFormatter;
use App\Services\Transformers\UnitTransformer;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendToSidersalesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $units;

    /**
     * Sin reintentos: las posiciones viejas no se reenvían, el siguiente ciclo
     * (cada 30s) manda datos nuevos. Debe ser menor que retry_after (300s).
     */
    public $tries = 1;
    public $timeout = 240;
    public $failOnTimeout = true;

    public function __construct(array $units)
    {
        $this->units = $units;
        $this->onQueue('sidersales');
    }

    public function handle(): void
    {
        $transformer = new UnitTransformer();
        $formatter   = new SidersalesFormatter($transformer);

        // El formatter devuelve un payload por unidad con metadatos internos
        $payloads = $formatter->format($this->units);

        if (empty($payloads)) {
            return;
        }

        // Determinar la URL según el entorno configurado
        $url    = SidersalesFormatter::getUrl();
        $sender = new SidersalesSender();

        // Enviar en lotes de 50 para no saturar la API
        $chunks = array_chunk($payloads, 50);
        foreach ($chunks as $chunk) {
            $sender->send($chunk, $url);
        }
    }
}
