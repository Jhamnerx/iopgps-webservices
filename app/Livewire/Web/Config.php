<?php

namespace App\Livewire\Web;

use Exception;
use App\Models\Account;
use App\Models\Devices;
use Livewire\Component;

use Livewire\WithPagination;
use Illuminate\Support\Collection;
use App\Models\Config as ConfigApp;
use App\Services\Api\DeviceService;
use Illuminate\Support\Facades\Redis;
use App\Services\Api\AccountTreeService;


class Config extends Component
{
    use WithPagination;
    public $hash, $status;
    public Collection $servicios;
    public ConfigApp $config;

    public $search;
    public $search_accounts;
    public array $values = [];

    protected DeviceService $deviceService;
    protected AccountTreeService $accountTreeService;

    public function __construct()
    {
        $this->deviceService = app(DeviceService::class);
        $this->accountTreeService = app(AccountTreeService::class);
    }


    public function mount()
    {

        $config = ConfigApp::first();

        $this->hash = $config->hash;
        $this->status = $config->status;
        $this->servicios = collect($config->servicios);
        $this->config = $config;
    }


    public function render()
    {
        $unidades = Devices::where('plate', 'like', '%' . $this->search . '%')
            ->Orwhere('id_api', 'like', '%' . $this->search . '%')
            ->Orwhere('name', 'like', '%' . $this->search . '%')
            ->Orwhere('imei', 'like', '%' . $this->search . '%')
            ->paginate(10, pageName: 'unidades-page');


        $accounts = Account::where('accountId', 'like', '%' . $this->search_accounts . '%')
            ->Orwhere('userName', 'like', '%' . $this->search_accounts . '%')
            ->Orwhere('account', 'like', '%' . $this->search_accounts . '%')
            ->paginate(10, pageName: 'accounts-page');
        return view('livewire.web.config', compact('unidades', 'accounts'));
    }

    public function updatedSearchAccounts()
    {
        $this->resetPage(pageName: 'accounts-page');
    }
    public function updatedSearch()
    {
        $this->resetPage(pageName: 'unidades-page');
    }

    public function loadUnits()
    {

        if (Account::count() == 0) {
            $this->dispatch(
                'notify-toast',
                icon: 'error',
                title: 'ERROR',
                mensaje: 'No se encontraron cuentas para sincronizar'
            );
            return;
        }


        $this->deviceService->fetchAndStoreDevices();

        $this->dispatch(
            'notify-toast',
            icon: 'success',
            title: 'UNIDADES SINCRONIZADAS',
            mensaje: 'Las unidades se han sincronizado correctamente'
        );
    }

    /**
     * Sincronizar cuentas con la API
     */
    public function syncAccounts()
    {
        $this->accountTreeService->fetchAndSyncAccounts();
        $this->dispatch(
            'notify-toast',
            icon: 'success',
            title: 'CUENTAS SINCRONIZADAS',
            mensaje: 'Las cuentas se han sincronizado correctamente'
        );
    }

    public function save()
    {

        $this->validate([
            'status' => 'required',
            'hash' => 'required',
            'servicios' => 'required',
        ]);

        try {

            $this->config->update([
                'hash' => $this->hash,
                'servicios' => $this->servicios,
            ]);


            $this->dispatch(
                'notify-toast',
                icon: 'success',
                title: 'CONFIGURACIÓN ACTUALIZADA',
                mensaje: 'La configuración se ha actualizado correctamente'
            );
        } catch (\Throwable $th) {
            $this->dispatch(
                'notify-toast',
                icon: 'error',
                title: 'ERROR',
                mensaje: $th->getMessage()
            );
        }
    }


    public function changeServiceStatus(Devices $unit, $service, $status)
    {
        // Validar el formato de la placa
        if (!preg_match('/^[A-Z0-9]{3}-[A-Z0-9]{3}$/', $unit->plate)) {
            $this->dispatch(
                'notify-toast',
                icon: 'error',
                title: 'ERROR',
                mensaje: 'El formato de la placa es incorrecto. Debe ser del tipo ABC-123'
            );
            $this->render();
            return;
        }

        $services = collect($unit->services);
        $services->put($service, ['active' => $status]);
        $unit->update([
            'services' => $services->toArray()
        ]);
    }

    public function saveServicioSutran()
    {
        $datos = $this->validate(
            [
                'servicios.sutran.token' => 'required_if:servicios.sutran.status,true|regex:/^[a-zA-Z0-9]{8}-[a-zA-Z0-9]{4}-[a-zA-Z0-9]{4}-[a-zA-Z0-9]{4}-[a-zA-Z0-9]{12}$/',
            ],
            [
                'servicios.sutran.token.required_if' => 'El token es requerido cuando el servicio está activo',
                'servicios.sutran.token.regex' => 'El token debe tener 32 caracteres alfanuméricos',
            ]
        );

        $nuevosServicios = array_merge(
            $this->config->servicios,
            ['sutran' => $this->servicios['sutran']]
        );

        $this->config->update([
            'servicios' => $nuevosServicios,
        ]);

        $this->dispatch(
            'notify-toast',
            icon: 'success',
            title: 'SERVICIO ACTUALIZADO',
            mensaje: 'El servicio de SUTRAN se ha actualizado correctamente'
        );
    }

    public function saveServicioOsinergmin()
    {

        $this->validate(
            [
                'servicios.osinergmin.token' => 'required_if:servicios.osinergmin.status,true|regex:/^[A-Z0-9]{8}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{12}$/',
            ],
            [
                'servicios.osinergmin.token.regex' => 'El token debe tener 32 caracteres alfanuméricos',
                'servicios.osinergmin.token.required_if' => 'El token es requerido cuando el servicio está activo',

            ]
        );

        $nuevosServicios = array_merge(
            $this->config->servicios,
            ['osinergmin' => $this->servicios['osinergmin']]
        );

        $this->config->update([
            'servicios' => $nuevosServicios,
        ]);

        $this->dispatch(
            'notify-toast',
            icon: 'success',
            title: 'SERVICIO ACTUALIZADO',
            mensaje: 'El servicio de OSINERGMIN se ha actualizado correctamente'
        );
    }


    public function updatedServicios($value, $name)
    {

        if ($name == 'osinergmin.status' && $value == false) {
            $this->servicios['osinergmin']['token'] = '';
        }

        if ($name == 'sutran.status' && $value == false) {
            $this->servicios->put('sutran', array_merge($this->servicios['sutran'], ['token' => '']));
        }
    }


    public function openModalReevio()
    {

        $this->dispatch('openModalReenvio');
    }
}
