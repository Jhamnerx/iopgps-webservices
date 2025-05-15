<?php

namespace App\Livewire\Logs;

use App\Models\LogSummary;
use Livewire\Component;

class LogEvidenceModal extends Component
{
    public $showModal = false;
    public $logSummary = null;
    public $summaryId;

    protected $listeners = [
        'showLogEvidence' => 'showEvidence'
    ];

    public function showEvidence($summaryId)
    {
        $this->summaryId = $summaryId;
        $this->logSummary = LogSummary::find($summaryId);
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->logSummary = null;
    }

    public function render()
    {
        return view('livewire.logs.log-evidence-modal');
    }
}
