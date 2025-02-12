<?php

namespace App\Jobs;

use App\Models\Logs;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Carbon\Carbon;

use Illuminate\Foundation\Bus\Dispatchable;


class ClearLogs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $days;

    public function __construct(int $days)
    {
        $this->days = $days;
    }


    public function handle(): void
    {
        $date = Carbon::now()->subDays($this->days);
        Logs::where('created_at', '<', $date)->delete();
    }
}
