<?php

namespace App\Livewire\Devices;

use App\Models\Devices;
use App\Models\Account;
use App\Helpers\DeviceDetailHelper;
use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Log;

class InfoDevice extends Component
{
    public $showModal = false;
    public Devices $device;
    public $account = null;
    public $updating = false;
    public $updateResult = null;
    protected $deviceDetailHelper;

    public function boot(DeviceDetailHelper $deviceDetailHelper)
    {
        $this->deviceDetailHelper = $deviceDetailHelper;
    }

    public function render()
    {
        return view('livewire.devices.info-device');
    }

    #[On('openModalVerInfo')]
    public function openModal(Devices $device)
    {
        $this->showModal = true;
        $this->device = $device;
        $this->updating = false;
        $this->updateResult = null;

        // Cargar la cuenta asociada si existe
        if ($device->account_id) {
            $this->account = Account::where('accountId', $device->account_id)->first();
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
    }

    public function updateAccountInfo()
    {
        $this->updating = true;
        $this->updateResult = null;

        try {
            // Llamar al helper para actualizar la información
            $result = $this->deviceDetailHelper->updateDeviceDetails($this->device->imei, true);

            if ($result && $result['updated']) {
                // Refrescar la información del dispositivo y la cuenta
                $this->device = Devices::find($this->device->id);

                if ($this->device->account_id) {
                    $this->account = Account::where('accountId', $this->device->account_id)->first();
                }

                $this->updateResult = [
                    'success' => true,
                    'message' => 'Información actualizada correctamente'
                ];

                // Notificar al usuario
                $this->dispatch(
                    'notify-toast',
                    icon: 'success',
                    title: 'ACTUALIZADO',
                    mensaje: 'La información de la cuenta ha sido actualizada correctamente',
                );
            } else {
                $this->updateResult = [
                    'success' => false,
                    'message' => $result ? $result['message'] : 'No se pudo actualizar la información'
                ];

                // Notificar error
                $this->dispatch(
                    'notify-toast',
                    icon: 'error',
                    title: 'ERROR',
                    mensaje: $result ? $result['message'] : 'No se pudo actualizar la información',
                );
            }
        } catch (\Exception $e) {
            Log::error('Error al actualizar información de la cuenta: ' . $e->getMessage());

            $this->updateResult = [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];

            // Notificar error
            $this->dispatch(
                'notify-toast',
                icon: 'error',
                title: 'ERROR',
                mensaje: 'Error al actualizar la información: ' . $e->getMessage(),
            );
        } finally {
            $this->updating = false;
        }
    }

    public function notifyClient($message)
    {
        $this->dispatch(
            'notify-toast',
            icon: 'success',
            title: 'COPIADO',
            mensaje: $message,
        );
    }
}
