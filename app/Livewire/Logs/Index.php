<?php

namespace App\Livewire\Logs;

use App\Exports\LogsExport;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Route;

class Index extends Component
{
    public function refreshTable()
    {
        $this->dispatch('pg:eventRefresh-TablaLogs');
    }

    public function render()
    {
        return view('livewire.logs.index')
            ->layout('layouts.livewire');
    }

    public function export()
    {
        return Excel::download(new LogsExport, 'resend-data.xlsx');
    }
}
