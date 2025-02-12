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

    public function __construct(array $units)
    {
        $this->units = $units;
    }

    public function handle()
    {
        $transformer = new UnitTransformer();
        $formatter = new OsinergminFormatter($transformer);
        $tramas = $formatter->format($this->units);

        $url = "https://prod.osinergmin-agent-2021.com/api/v1/trama";

        $sender = new OsinergminSender();
        $sender->send($tramas, $url);
    }
}
