<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use App\Models\Config;

class Card extends Component
{

    public function render()
    {

        $data = Config::first()->counterServices->data;


        return view('livewire.dashboard.card', compact('data'));
    }


    public function actualizarVista()
    {
        $this->render();
    }
}
