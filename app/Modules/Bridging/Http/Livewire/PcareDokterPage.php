<?php

namespace App\Modules\Bridging\Http\Livewire;

use Livewire\Component;
use App\Modules\Bridging\Services\BpjsPcareService;

class PcareDokterPage extends Component
{
    public $start = 0;
    public $limit = 20;
    public $dokterList = [];
    public $totalDokter = 0;
    public $isLoading = false;
    public $errorMessage = '';
    public $successMessage = '';
    public $rawResponse = '';
    public $lastFetched = '';

    // Debug info
    public $responseMetaCode;
    public $responseMetaMessage;

    public function mount()
    {
        // Kosongkan dulu, biar user yang trigger
    }

    /**
     * Ambil data dokter dari PCare BPJS
     */
    public function fetchDokter()
    {
        $this->isLoading = true;
        $this->errorMessage = '';
        $this->successMessage = '';
        $this->rawResponse = '';

        try {
            $service = new BpjsPcareService();

            if (!$service->isConfigured()) {
                $this->errorMessage = 'Konfigurasi BPJS belum lengkap. Silakan isi ConsID, Secret Key, dan Base URL PCare di halaman Setting API.';
                $this->dispatch('alert', ['type' => 'error', 'message' => $this->errorMessage]);
                $this->isLoading = false;
                return;
            }

            $result = $service->getDokter($this->start, $this->limit);

            $this->responseMetaCode = $result['metadata']['code'] ?? null;
            $this->responseMetaMessage = $result['metadata']['message'] ?? null;

            if ($result['success']) {
                $data = $result['data'];

                if (is_array($data)) {
                    // Format standar PCare: { count: N, list: [...] }
                    $this->dokterList = $data['list'] ?? $data;
                    $this->totalDokter = $data['count'] ?? count($this->dokterList);
                } else {
                    $this->dokterList = [];
                    $this->totalDokter = 0;
                }

                $this->lastFetched = now()->format('d M Y H:i:s');
                $this->successMessage = "Berhasil mengambil {$this->totalDokter} data dokter dari PCare BPJS.";
                $this->dispatch('alert', ['type' => 'success', 'message' => $this->successMessage]);
            } else {
                $this->errorMessage = 'Gagal: [' . ($result['metadata']['code'] ?? '?') . '] ' . ($result['metadata']['message'] ?? 'Unknown error');
                $this->dispatch('alert', ['type' => 'error', 'message' => $this->errorMessage]);

                if ($result['raw']) {
                    $this->rawResponse = is_string($result['raw']) ? $result['raw'] : json_encode($result['raw']);
                }
            }
        } catch (\Exception $e) {
            $this->errorMessage = 'Exception: ' . $e->getMessage();
            $this->dispatch('alert', ['type' => 'error', 'message' => $this->errorMessage]);
        }

        $this->isLoading = false;
    }

    /**
     * Navigasi halaman berikutnya
     */
    public function nextPage()
    {
        $this->start += $this->limit;
        $this->fetchDokter();
    }

    /**
     * Navigasi halaman sebelumnya
     */
    public function prevPage()
    {
        $this->start = max(0, $this->start - $this->limit);
        $this->fetchDokter();
    }

    /**
     * Reset ke halaman pertama
     */
    public function resetPage()
    {
        $this->start = 0;
        $this->dokterList = [];
        $this->totalDokter = 0;
        $this->errorMessage = '';
        $this->successMessage = '';
        $this->rawResponse = '';
    }

    public function render()
    {
        return view('bridging::livewire.pcare-dokter-page');
    }
}
