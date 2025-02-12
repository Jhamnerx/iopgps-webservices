<?php

namespace App\Livewire\Ajustes\Cuenta;

use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;
use Livewire\Component;

class UpdatePassword extends Component
{
    public $modalOpen = false;

    protected $listeners = [
        'openModalPassword' => 'openModal'
    ];

    public $state = [
        'current_password' => '',
        'password' => '',
        'password_confirmation' => '',
    ];

    public function render()
    {
        return view('livewire.ajustes.cuenta.update-password');
    }

    public function openModal()
    {

        $this->modalOpen = true;
    }
    public function closeModal()
    {

        $this->modalOpen = false;
        $this->resetErrorBag();
        $this->resetValidation();
        $this->reset();
    }

    public function updatePassword(UpdatesUserPasswords $updater)
    {
        $this->resetErrorBag();

        $updater->update(Auth::user(), $this->state);

        if (request()->hasSession()) {
            request()->session()->put([
                'password_hash_' . Auth::getDefaultDriver() => Auth::user()->getAuthPassword(),
            ]);
        }

        $this->state = [
            'current_password' => '',
            'password' => '',
            'password_confirmation' => '',
        ];

        $this->dispatch('saved-pass');

        $this->dispatch(
            'notify-toast',
            icon: 'success',
            title: 'CONTRASEÑA ACTUALIZADA',
            mensaje: 'La contraseña se ha actualizado correctamente'
        );

        $this->closeModal();
    }


    public function getUserProperty()
    {
        return Auth::user();
    }
}
