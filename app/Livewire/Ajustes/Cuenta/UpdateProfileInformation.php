<?php

namespace App\Livewire\Ajustes\Cuenta;

use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;
use Livewire\Component;
use Livewire\WithFileUploads;

class UpdateProfileInformation extends Component
{
    use WithFileUploads;
    public $state = [];

    public $photo;

    protected $listeners = [
        'render' => 'render'
    ];

    public function mount()
    {

        $this->state = Auth::user()->withoutRelations()->toArray();
    }

    public function render()
    {
        return view('livewire.ajustes.cuenta.update-profile-information');
    }

    public function updateProfileInformation(UpdatesUserProfileInformation $updater)
    {
        $this->resetErrorBag();

        $updater->update(
            Auth::user(),
            $this->photo
                ? array_merge($this->state, ['photo' => $this->photo])
                : $this->state
        );

        if (isset($this->photo)) {
            return redirect()->route('admin.ajustes.cuenta');
        }

        $this->dispatch('saved');

        $this->render();
    }
    public function deleteProfilePhoto()
    {
        Auth::user()->deleteProfilePhoto();
        $this->getUserProperty();
        $this->render();
    }
    public function sendEmailVerification()
    {
        Auth::user()->sendEmailVerificationNotification();

        $this->verificationLinkSent = true;
    }

    public function getUserProperty()
    {
        return Auth::user();
    }

    public function openModalPassword()
    {
        $this->dispatch('openModalPassword');
    }
}
