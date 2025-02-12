<?php

namespace App\Livewire\Logs;

use App\Exports\LogsExport;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class Index extends Component
{
    public function refreshTable()
    {
        $this->dispatch('pg:eventRefresh-TablaLogs');
    }

    public function render()
    {
        return view('livewire.logs.index');
    }

    public function export()
    {
        return Excel::download(new LogsExport, 'resend-data.xlsx');
    }
}
