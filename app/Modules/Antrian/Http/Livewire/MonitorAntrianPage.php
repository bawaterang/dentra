<?php

namespace App\Modules\Antrian\Http\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\TrxAntrian;

#[Layout('components.layouts.blank')]
class MonitorAntrianPage extends Component
{
    public $tanggalAntrian;
    public $currentCalled = null;
    public $waitingList = [];
    public $isOpen = true;
    public $runningText = '';

    public function mount()
    {
        $this->tanggalAntrian = now()->format('Y-m-d');
        $this->refreshData();
    }

    public function refreshData()
    {
        $this->currentCalled = TrxAntrian::with(['pasien', 'poli'])
            ->where('tanggal_antrian', $this->tanggalAntrian)
            ->where('status', 'dipanggil')
            ->orderBy('waktu_panggil', 'desc')
            ->first();

        $this->waitingList = TrxAntrian::with(['pasien', 'poli'])
            ->where('tanggal_antrian', $this->tanggalAntrian)
            ->where('status', 'menunggu')
            ->orderBy('nomor_antrian')
            ->limit(10)
            ->get();

        $setting = \App\Models\MstSettingAntrian::first();
        if ($setting && $setting->running_text) {
            $this->runningText = $setting->running_text;
        } else {
            $this->runningText = "Selamat datang di SIGI Dental Clinic! Mohon menunggu antrian Anda dipanggil.";
        }
    }

    public function getListeners()
    {
        return ['refresh-monitor' => 'refreshData'];
    }

    public function render()
    {
        $this->refreshData();

        return view('livewire.modules.antrian.monitor-antrian-page');
    }
}
