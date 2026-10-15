<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Services\Senders\OsinergminSender;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\Transformers\UnitTransformer;
use App\Services\Formatters\OsinergminFormatter;

class SendToOsinergminJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $units;
    protected $source;

    /**
     * Sin reintentos: las posiciones viejas no se reenvían, el siguiente ciclo
     * (cada 30s) manda datos nuevos. Debe ser menor que retry_after (300s).
     */
    public $tries = 1;
    public $timeout = 120;
    public $failOnTimeout = true;

    public function __construct(array $units)
    {
        $this->units = $units;
        $this->onQueue('osinergmin');
    }

    public function handle()
    {
        $transformer = new UnitTransformer();
        $formatter = new OsinergminFormatter($transformer);
        $tramas = $formatter->format($this->units);

        $url = "https://pmgo.osinergmin.gob.pe/api-gps-ingesta/api/v1/trama";

        $sender = new OsinergminSender();
        $sender->send($tramas, $url);
    }
}
