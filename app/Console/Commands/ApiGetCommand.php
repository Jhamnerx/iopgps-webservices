<?php

namespace App\Console\Commands;

use App\Jobs\ProcessUnitsJob;
use Illuminate\Console\Command;



class ApiGetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:get';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        ProcessUnitsJob::dispatch();
    }
}
