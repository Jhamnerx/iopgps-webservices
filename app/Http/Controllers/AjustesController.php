<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Ajustes;
use App\Models\plantilla;
use Illuminate\Http\Request;

class AjustesController extends Controller
{

    public function cuenta()
    {
        return view('ajustes.cuenta');
    }
}
