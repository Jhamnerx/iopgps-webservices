<?php

namespace App\Jobs;

use App\Services\Api\DeviceService;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class CheckDevicesExists implements ShouldQueue
{
    use Queueable;
    protected DeviceService $deviceService;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->deviceService = app(DeviceService::class);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $this->deviceService->fetchAndStoreDevices();
    }
}
