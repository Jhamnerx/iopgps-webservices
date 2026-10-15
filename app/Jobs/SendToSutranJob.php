<?php

namespace App\Jobs;

use App\Services\Senders\SutranSender;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\Formatters\SutranFormatter;
use App\Services\Transformers\UnitTransformer;

class SendToSutranJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $units;

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
        $this->onQueue('sutran');
    }

    public function handle()
    {
        $transformer = new UnitTransformer();
        $formatter = new SutranFormatter($transformer);
        $tramas = $formatter->format($this->units);


        $this->chunckTramas($tramas);
    }

    public function chunckTramas($tramas)
    {
        $url_sutran = config('app.env') == 'local' ? 'https://ws03.sutran.ehg.pe/api/v1.0/transmisiones' : 'https://ws03.sutran.gob.pe/api/v1.0/transmisiones';
        $tramas_por_grupo = array_chunk($tramas, 150);

        foreach ($tramas_por_grupo as $grupo) {
            $sender = new SutranSender();
            $sender->send($grupo, $url_sutran);
        }
    }
}
