<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApiController extends Controller
{
    public function index()
    {
        return view('web.index');
    }

    public function config()
    {
        return view('web.config');
    }
}
