<?php

namespace App\Modules\Bridging\Http\Livewire;

use Livewire\Component;
use App\Models\TrxApiMonitoringLog;
use App\Modules\Bridging\Services\ApiMonitoringService;

class ApiMonitoringPage extends Component
{
    public $activeTab = 'satusehat';
    
    public $ssEndpoint = 'organization';
    public $bpjsEndpoint = 'referensi_poli';

    // Results from last check
    public $satusehatResult = null;
    public $bpjsResult = null;

    // Stats
    public $satusehatStats = [];
    public $bpjsStats = [];

    // Log detail modal
    public $showLogDetail = false;
    public $logDetail = null;

    // Loading states
    public $isCheckingSatusehat = false;
    public $isCheckingBpjs = false;

    public function mount()
    {
        $service = new ApiMonitoringService();

        // Load stats from historical logs
        $this->satusehatStats = $service->getUptimeStats('satusehat', 7);
        $this->bpjsStats = $service->getUptimeStats('bpjs', 7);

        // Load last check result
        $lastSs = TrxApiMonitoringLog::where('api_type', 'satusehat')
            ->orderBy('created_at', 'desc')
            ->first();
        if ($lastSs) {
            $this->satusehatResult = $lastSs->toArray();
        }

        $lastBpjs = TrxApiMonitoringLog::where('api_type', 'bpjs')
            ->orderBy('created_at', 'desc')
            ->first();
        if ($lastBpjs) {
            $this->bpjsResult = $lastBpjs->toArray();
        }
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function checkSatuSehat()
    {
        $this->isCheckingSatusehat = true;
        $service = new ApiMonitoringService();
        $username = auth()->user()->username ?? auth()->user()->name ?? 'System';

        $this->satusehatResult = $service->checkSatuSehat($username, $this->ssEndpoint);
        $this->satusehatStats = $service->getUptimeStats('satusehat', 7);
        $this->isCheckingSatusehat = false;

        $status = $this->satusehatResult['is_up'] ? 'success' : 'error';
        $msg = $this->satusehatResult['is_up']
            ? 'SatuSehat API aktif dan merespons dengan baik!'
            : 'SatuSehat API tidak merespons: ' . ($this->satusehatResult['error_message'] ?? 'Unknown error');

        $this->dispatch('alert', ['type' => $status, 'message' => $msg]);
    }

    public function checkBpjs()
    {
        $this->isCheckingBpjs = true;
        $service = new ApiMonitoringService();
        $username = auth()->user()->username ?? auth()->user()->name ?? 'System';

        $this->bpjsResult = $service->checkBpjs($username, $this->bpjsEndpoint);
        $this->bpjsStats = $service->getUptimeStats('bpjs', 7);
        $this->isCheckingBpjs = false;

        $status = $this->bpjsResult['is_up'] ? 'success' : 'error';
        $msg = $this->bpjsResult['is_up']
            ? 'BPJS API aktif dan merespons dengan baik!'
            : 'BPJS API tidak merespons: ' . ($this->bpjsResult['error_message'] ?? 'Unknown error');

        $this->dispatch('alert', ['type' => $status, 'message' => $msg]);
    }

    public function checkAll()
    {
        $this->checkSatuSehat();
        $this->checkBpjs();
    }

    public function viewLogDetail($id)
    {
        $this->logDetail = TrxApiMonitoringLog::find($id);
        $this->showLogDetail = true;
    }

    public function closeLogDetail()
    {
        $this->showLogDetail = false;
        $this->logDetail = null;
    }

    public function clearLogs($apiType)
    {
        $service = new ApiMonitoringService();
        $deleted = $service->clearLogs($apiType);

        // Refresh stats
        if ($apiType === 'satusehat') {
            $this->satusehatStats = $service->getUptimeStats('satusehat', 7);
            $this->satusehatResult = null;
        } else {
            $this->bpjsStats = $service->getUptimeStats('bpjs', 7);
            $this->bpjsResult = null;
        }

        $this->dispatch('alert', [
            'type' => 'success',
            'message' => 'Log ' . strtoupper($apiType) . ' berhasil dihapus (' . $deleted . ' records).'
        ]);
    }

    public function render()
    {
        $satusehatLogs = TrxApiMonitoringLog::recentLogs('satusehat', 15);
        $bpjsLogs = TrxApiMonitoringLog::recentLogs('bpjs', 15);

        return view('bridging::livewire.api-monitoring-page', [
            'satusehatLogs' => $satusehatLogs,
            'bpjsLogs' => $bpjsLogs,
        ]);
    }
}
