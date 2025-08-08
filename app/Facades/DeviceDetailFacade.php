<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class DeviceDetailFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'device-detail-helper';
    }
}
