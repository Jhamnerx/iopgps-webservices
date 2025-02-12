<?php

namespace App\Livewire\Web;

use App\Models\Devices;
use Livewire\Component;

class InputPlate extends Component
{

    public $unit;

    public function mount(Devices $unit)
    {
        $this->unit = $unit;
    }


    public function render()
    {
        return view('livewire.web.input-plate');
    }



    public function rules()
    {
        return [
            'unit.plate' => 'required'
        ];
    }
    public function updatePlate($plate, Devices $unit)
    {
        $this->validate(
            [
                'unit.plate' => 'required|min:7|max:7|regex:/^[A-Z0-9]{3}-[A-Z0-9]{3}$/'
            ],
            [
                'unit.plate.required' => 'La placa es requerida',
                'unit.plate.min' => 'La placa debe tener al menos 7 caracteres',
                'unit.plate.max' => 'La placa debe tener máximo 7 caracteres',
                'unit.plate.regex' => 'La placa debe tener el formato AAA-000'
            ]
        );
        try {


            $unit->update(['plate' => $plate]);


            $this->dispatch(
                'notify-toast',
                icon: 'success',
                title: 'NUEVA PLACA: ' . $plate,
                mensaje: 'La placa se ha actualizo la placa de la unidad' . $unit->name
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
}
