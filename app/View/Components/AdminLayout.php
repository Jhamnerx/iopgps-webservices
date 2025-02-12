<?php

namespace App\View\Components;

use Closure;
use App\Models\System\Empresa;
use Illuminate\View\Component;
use Illuminate\Contracts\View\View;

class AdminLayout extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $empresa = Empresa::first();
        return view('layouts.admin', compact('empresa'));
    }
}
