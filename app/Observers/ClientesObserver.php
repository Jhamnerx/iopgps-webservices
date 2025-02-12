<?php

namespace App\Observers;

use App\Models\Clientes;
use Illuminate\Support\Facades\App;

class ClientesObserver
{

    public function creating(Clientes $clientes)
    {

        if (!App::runningInConsole()) {

            $clientes->local_id = session('local_id');
            $clientes->user_id = auth()->id();
        }
    }
    /**
     * Handle the Clientes "created" event.
     */
    public function created(Clientes $clientes): void
    {
        //
    }

    /**
     * Handle the Clientes "updated" event.
     */
    public function updated(Clientes $clientes): void
    {
        //
    }

    /**
     * Handle the Clientes "deleted" event.
     */
    public function deleted(Clientes $clientes): void
    {
        //
    }

    /**
     * Handle the Clientes "restored" event.
     */
    public function restored(Clientes $clientes): void
    {
        //
    }

    /**
     * Handle the Clientes "force deleted" event.
     */
    public function forceDeleted(Clientes $clientes): void
    {
        //
    }
}
