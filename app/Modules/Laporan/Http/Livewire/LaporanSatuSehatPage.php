<?php

namespace App\Modules\Laporan\Http\Livewire;

use App\Models\TrxPendaftaran;
use App\Models\TrxSatusehatStatus;
use App\Modules\Bridging\Services\SatuSehatService;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LaporanSatuSehatPage extends Component
{
    use WithPagination;

    public $periodType = 'DAILY'; // DAILY, MONTHLY, YEARLY

    public $selectedDate;
    public $selectedBulan;
    public $selectedTahun;
    public $search = '';

    public $availableYears = [];
    public $listBulan = [];

    public $listPeriodType = [
        ['value' => 'DAILY', 'label' => 'HARIAN', 'icon' => 'ri-calendar-event-line text-blue-500'],
        ['value' => 'MONTHLY', 'label' => 'BULANAN', 'icon' => 'ri-calendar-2-line text-indigo-500'],
        ['value' => 'YEARLY', 'label' => 'TAHUNAN', 'icon' => 'ri-calendar-todo-line text-purple-500'],
    ];

    public $selectedKunjungan = [];
    public $selectAll = false;

    // Detail modal state
    public $showDetailModal = false;
    public $detailNomorKunjungan = null;
    public $detailStatuses = [];

    // Sending state
    public $isSending = false;

    protected $queryString = ['periodType', 'selectedDate', 'selectedBulan', 'selectedTahun', 'search'];

    public function mount()
    {
        $this->selectedDate = date('Y-m-d');
        $this->selectedBulan = (int) date('n');
        $this->selectedTahun = (int) date('Y');
        $this->loadAvailableYears();
        $this->loadListBulan();
    }

    public function loadListBulan()
    {
        $this->listBulan = [
            ['value' => 1, 'label' => 'Januari', 'icon' => 'ri-calendar-line text-blue-500'],
            ['value' => 2, 'label' => 'Februari', 'icon' => 'ri-calendar-line text-indigo-500'],
            ['value' => 3, 'label' => 'Maret', 'icon' => 'ri-calendar-line text-purple-500'],
            ['value' => 4, 'label' => 'April', 'icon' => 'ri-calendar-line text-pink-500'],
            ['value' => 5, 'label' => 'Mei', 'icon' => 'ri-calendar-line text-cyan-500'],
            ['value' => 6, 'label' => 'Juni', 'icon' => 'ri-calendar-line text-teal-500'],
            ['value' => 7, 'label' => 'Juli', 'icon' => 'ri-calendar-line text-green-500'],
            ['value' => 8, 'label' => 'Agustus', 'icon' => 'ri-calendar-line text-lime-500'],
            ['value' => 9, 'label' => 'September', 'icon' => 'ri-calendar-line text-yellow-500'],
            ['value' => 10, 'label' => 'Oktober', 'icon' => 'ri-calendar-line text-orange-500'],
            ['value' => 11, 'label' => 'November', 'icon' => 'ri-calendar-line text-red-500'],
            ['value' => 12, 'label' => 'Desember', 'icon' => 'ri-calendar-line text-rose-500'],
        ];
    }

    public function loadAvailableYears()
    {
        // User requested year fetched from trx_diagnosis
        $years = DB::table('trx_diagnosis')
            ->selectRaw('YEAR(created_at) as year')
            ->whereNotNull('created_at')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        if (empty($years)) {
            $years = [(int) date('Y')];
        }

        $this->availableYears = $years;
    }

    /**
     * Hitung status bridging per kunjungan berdasarkan per-resource statuses.
     * 
     * Logika:
     * - Jika tidak ada record → "Pending"  
     * - Jika semua Success → "Success"
     * - Jika ada Failed → "Partial" (jika ada yg Success juga) atau "Failed" (semua gagal)
     */
    private function computeBundleStatus(string $nomorKunjungan): string
    {
        $statuses = TrxSatusehatStatus::where('nomor_kunjungan', $nomorKunjungan)->get();

        if ($statuses->isEmpty()) {
            return 'Pending';
        }

        $successCount = $statuses->where('resource_status', 'Success')->count();
        $failedCount  = $statuses->where('resource_status', 'Failed')->count();

        if ($failedCount === 0 && $successCount > 0) {
            return 'Success';
        }

        if ($successCount === 0 && $failedCount > 0) {
            return 'Failed';
        }

        return 'Partial';
    }

    #[Computed]
    public function laporanSatuSehat()
    {
        $query = TrxPendaftaran::with(['pasien', 'poli', 'asuransi'])
            ->whereNotNull('trx_pendaftaran.created_at');

        if ($this->periodType === 'DAILY') {
            $query->whereDate('trx_pendaftaran.created_at', $this->selectedDate);
        } elseif ($this->periodType === 'MONTHLY') {
            $query->whereMonth('trx_pendaftaran.created_at', $this->selectedBulan)
                ->whereYear('trx_pendaftaran.created_at', $this->selectedTahun);
        } elseif ($this->periodType === 'YEARLY') {
            $query->whereYear('trx_pendaftaran.created_at', $this->selectedTahun);
        }

        if (!empty($this->search)) {
            $query->whereHas('pasien', function ($q) {
                $q->where('nama_pasien', 'like', '%' . $this->search . '%')
                  ->orWhere('no_rm', 'like', '%' . $this->search . '%')
                  ->orWhere('nik', 'like', '%' . $this->search . '%');
            });
        }

        $results = $query->orderBy('trx_pendaftaran.created_at', 'desc')->paginate(20);

        // Compute bundle status per item
        $results->getCollection()->transform(function ($item) {
            $item->computed_status = $this->computeBundleStatus($item->nomor_kunjungan);

            // Load per-resource detail
            $item->resource_statuses = TrxSatusehatStatus::where('nomor_kunjungan', $item->nomor_kunjungan)
                ->orderBy('created_at', 'desc')
                ->get()
                ->groupBy('resource_type');

            return $item;
        });

        return $results;
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedKunjungan = $this->laporanSatuSehat->pluck('nomor_kunjungan')->map(fn($id) => (string) $id)->toArray();
        } else {
            $this->selectedKunjungan = [];
        }
    }

    public function updatedSelectedKunjungan()
    {
        $this->selectAll = count($this->selectedKunjungan) === $this->laporanSatuSehat->count();
    }

    public function updatedPeriodType()
    {
        $this->resetPage();
        $this->resetCheckboxes();
    }

    public function updatedSelectedDate()
    {
        $this->resetPage();
        $this->resetCheckboxes();
    }

    public function updatedSelectedBulan()
    {
        $this->resetPage();
        $this->resetCheckboxes();
    }

    public function updatedSelectedTahun()
    {
        $this->resetPage();
        $this->resetCheckboxes();
    }

    public function updatedSearch()
    {
        $this->resetPage();
        $this->resetCheckboxes();
    }

    private function resetCheckboxes()
    {
        $this->selectedKunjungan = [];
        $this->selectAll = false;
    }

    /**
     * Kirim resume medis ke SatuSehat untuk satu kunjungan.
     */
    public function kirim($nomor_kunjungan)
    {
        $this->isSending = true;

        try {
            $pendaftaran = TrxPendaftaran::with('dokter')
                ->where('nomor_kunjungan', $nomor_kunjungan)
                ->first();

            $dokterId = $pendaftaran?->dokter_id;
            $service  = new SatuSehatService($dokterId);
            $createdBy = Auth::user()?->name ?? 'System';

            // Cari status saat ini untuk melihat bagian mana yang belum sukses
            $statuses = TrxSatusehatStatus::where('nomor_kunjungan', $nomor_kunjungan)->get();
            $statusGroup = $statuses->groupBy('resource_type');

            $hasEncounterSuccess = $statusGroup->get('Encounter', collect())->where('resource_status', 'Success')->count() > 0;
            $hasConditionSuccess = $statusGroup->get('Condition', collect())->where('resource_status', 'Success')->count() > 0;
            $hasObservationSuccess = $statusGroup->get('Observation', collect())->where('resource_status', 'Success')->count() > 0;

            if ($statuses->isEmpty() || !$hasEncounterSuccess) {
                // Jika Encounter belum pernah sukses, jalankan ulang seluruh flow
                $result = $service->sendResumeMedis($nomor_kunjungan, $createdBy);
            } else {
                // Smart Retry: kirim hanya resource yang masih gagal atau belum terkirim
                $errors = [];
                if (!$hasConditionSuccess) {
                    $res = $service->retrySendResource($nomor_kunjungan, 'Condition', $createdBy);
                    if (!empty($res['errors'])) $errors = array_merge($errors, $res['errors']);
                }
                if (!$hasObservationSuccess) {
                    $res = $service->retrySendResource($nomor_kunjungan, 'Observation', $createdBy);
                    if (!empty($res['errors'])) $errors = array_merge($errors, $res['errors']);
                }
                $result = ['errors' => $errors];
            }

            if (empty($result['errors'])) {
                session()->flash('success', "Resume medis kunjungan {$nomor_kunjungan} berhasil dikirim ke SatuSehat.");
            } else {
                $errorCount = count($result['errors']);
                session()->flash('warning', "Resume medis {$nomor_kunjungan} dikirim dengan {$errorCount} error. Klik detail untuk info lengkap.");
            }
        } catch (\Exception $e) {
            Log::error("Kirim SatuSehat gagal [{$nomor_kunjungan}]: " . $e->getMessage());
            session()->flash('error', "Gagal kirim {$nomor_kunjungan}: " . $e->getMessage());
        }

        $this->isSending = false;
        unset($this->laporanSatuSehat);
    }

    /**
     * Kirim batch resume medis untuk semua kunjungan yang dipilih.
     */
    public function kirimSemua()
    {
        if (count($this->selectedKunjungan) === 0) {
            session()->flash('warning', 'Pilih minimal satu kunjungan untuk dikirim.');
            return;
        }

        $this->isSending = true;
        $total   = count($this->selectedKunjungan);
        $success = 0;
        $failed  = 0;
        $partial = 0;

        foreach ($this->selectedKunjungan as $nomorKunjungan) {
            try {
                $pendaftaran = TrxPendaftaran::with('dokter')
                    ->where('nomor_kunjungan', $nomorKunjungan)
                    ->first();

                $dokterId = $pendaftaran?->dokter_id;
                $service  = new SatuSehatService($dokterId);
                $createdBy = Auth::user()?->name ?? 'System';

                // Smart Retry / Kirim Logic per Kunjungan
                $statuses = TrxSatusehatStatus::where('nomor_kunjungan', $nomorKunjungan)->get();
                $statusGroup = $statuses->groupBy('resource_type');

                $hasEncounterSuccess = $statusGroup->get('Encounter', collect())->where('resource_status', 'Success')->count() > 0;
                $hasConditionSuccess = $statusGroup->get('Condition', collect())->where('resource_status', 'Success')->count() > 0;
                $hasObservationSuccess = $statusGroup->get('Observation', collect())->where('resource_status', 'Success')->count() > 0;

                $hasError = false;

                if ($statuses->isEmpty() || !$hasEncounterSuccess) {
                    $result = $service->sendResumeMedis($nomorKunjungan, $createdBy);
                    if (!empty($result['errors'])) $hasError = true;
                } else {
                    if (!$hasConditionSuccess) {
                        $res = $service->retrySendResource($nomorKunjungan, 'Condition', $createdBy);
                        if (!empty($res['errors'])) $hasError = true;
                    }
                    if (!$hasObservationSuccess) {
                        $res = $service->retrySendResource($nomorKunjungan, 'Observation', $createdBy);
                        if (!empty($res['errors'])) $hasError = true;
                    }
                }

                if (!$hasError) {
                    $success++;
                } else {
                    $partial++;
                }
            } catch (\Exception $e) {
                $failed++;
                Log::error("Kirim batch SatuSehat gagal [{$nomorKunjungan}]: " . $e->getMessage());
            }
        }

        $msg = "Batch selesai ({$total} kunjungan): ";
        $parts = [];
        if ($success > 0) $parts[] = "{$success} berhasil";
        if ($partial > 0) $parts[] = "{$partial} partial";
        if ($failed > 0)  $parts[] = "{$failed} gagal";
        $msg .= implode(', ', $parts) . '.';

        if ($failed === 0 && $partial === 0) {
            session()->flash('success', $msg);
        } elseif ($failed === $total) {
            session()->flash('error', $msg);
        } else {
            session()->flash('warning', $msg);
        }

        $this->isSending = false;
        $this->resetCheckboxes();
        unset($this->laporanSatuSehat);
    }

    /**
     * Retry kirim ulang resource yang gagal.
     */
    public function retryResource($nomorKunjungan, $resourceType)
    {
        $this->isSending = true;

        try {
            $pendaftaran = TrxPendaftaran::with('dokter')
                ->where('nomor_kunjungan', $nomorKunjungan)
                ->first();

            $dokterId  = $pendaftaran?->dokter_id;
            $service   = new SatuSehatService($dokterId);
            $createdBy = Auth::user()?->name ?? 'System';

            $result = $service->retrySendResource($nomorKunjungan, $resourceType, $createdBy);

            if (empty($result['errors'] ?? [])) {
                session()->flash('success', "Retry {$resourceType} untuk {$nomorKunjungan} berhasil.");
            } else {
                session()->flash('warning', "Retry {$resourceType} selesai dengan error.");
            }
        } catch (\Exception $e) {
            Log::error("Retry resource gagal [{$nomorKunjungan}][{$resourceType}]: " . $e->getMessage());
            session()->flash('error', "Retry {$resourceType} gagal: " . $e->getMessage());
        }

        $this->isSending = false;
        $this->showDetailModal = false;
        unset($this->laporanSatuSehat);
    }

    /**
     * Tampilkan detail status per-resource di modal.
     */
    public function showDetail($nomorKunjungan)
    {
        $this->detailNomorKunjungan = $nomorKunjungan;
        $this->detailStatuses = TrxSatusehatStatus::where('nomor_kunjungan', $nomorKunjungan)
            ->orderBy('resource_type')
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
        $this->showDetailModal = true;
    }

    public function closeDetail()
    {
        $this->showDetailModal = false;
        $this->detailNomorKunjungan = null;
        $this->detailStatuses = [];
    }

    public function render()
    {
        return <<<'HTML'
        <div>
            <style>
                .glass-header {
                    background: rgba(255, 255, 255, 0.8) !important;
                    backdrop-filter: blur(8px);
                    -webkit-backdrop-filter: blur(8px);
                }
                .laporan-row:hover {
                    background-color: #f8fafc !important;
                    transition: all 0.3s ease;
                }
                .action-btn-soft {
                    width: 32px;
                    height: 32px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 50%;
                    transition: all 0.2s ease;
                }
                .search-focus-glow:focus {
                    box-shadow: 0 0 0 4px rgba(64, 81, 137, 0.15);
                    border-color: #405189;
                }
                .pagination-custom nav span.relative.z-0 { 
                    display: flex !important; 
                    gap: 4px !important; 
                    flex-wrap: wrap !important;
                    justify-content: center !important;
                }
                .pagination-custom nav a, 
                .pagination-custom nav span[aria-disabled="true"] span,
                .pagination-custom nav span[aria-current="page"] span {
                    display: inline-flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    min-width: 38px !important;
                    height: 38px !important;
                    padding: 0 12px !important;
                    border-radius: 8px !important;
                    border: 1px solid #e2e8f0 !important;
                    font-size: 13px !important;
                    font-weight: 700 !important;
                    transition: all 0.2s ease-in-out !important;
                    background-color: #767070ff !important;
                    color: #eaecefff !important;
                    text-decoration: none !important;
                }
                .pagination-custom nav a:hover {
                    background-color: #f8fafc !important;
                    border-color: #405189 !important;
                    color: #405189 !important;
                    transform: translateY(-1px) !important;
                }
                .pagination-custom nav p.text-sm {
                    display: none !important;
                }
                .pagination-custom nav > div:last-child > div:first-child {
                    display: none !important;
                }
                .pagination-custom [aria-current="page"], 
                .pagination-custom [aria-current="page"] *,
                .pagination-custom .active,
                .pagination-custom .active * {
                    background-color: #405189 !important;
                    color: #ffffff !important;
                    border-color: #405189 !important;
                    box-shadow: 0 4px 10px rgba(64, 81, 137, 0.3) !important;
                    z-index: 10 !important;
                }
                
                .checkbox-custom {
                    appearance: none;
                    background-color: #fff;
                    margin: 0;
                    font: inherit;
                    color: currentColor;
                    width: 1.15em;
                    height: 1.15em;
                    border: 1.5px solid #cbd5e1;
                    border-radius: 0.25em;
                    display: grid;
                    place-content: center;
                    transition: all 0.2s;
                    cursor: pointer;
                }

                .checkbox-custom::before {
                    content: "";
                    width: 0.65em;
                    height: 0.65em;
                    transform: scale(0);
                    transition: 120ms transform ease-in-out;
                    box-shadow: inset 1em 1em white;
                    transform-origin: center;
                    clip-path: polygon(14% 44%, 0 65%, 50% 100%, 100% 16%, 80% 0%, 43% 62%);
                }

                .checkbox-custom:checked {
                    background-color: #405189;
                    border-color: #405189;
                }

                .checkbox-custom:checked::before {
                    transform: scale(1);
                }

                .resource-badge {
                    display: inline-flex;
                    align-items: center;
                    gap: 4px;
                    padding: 2px 8px;
                    border-radius: 6px;
                    font-size: 9px;
                    font-weight: 700;
                    text-transform: uppercase;
                    letter-spacing: 0.05em;
                    transition: all 0.2s;
                }

                .resource-badge.success {
                    background: #ecfdf5;
                    color: #059669;
                    border: 1px solid #a7f3d0;
                }

                .resource-badge.failed {
                    background: #fef2f2;
                    color: #dc2626;
                    border: 1px solid #fecaca;
                }

                .resource-badge.pending {
                    background: #fffbeb;
                    color: #d97706;
                    border: 1px solid #fde68a;
                }

                .modal-overlay {
                    position: fixed;
                    inset: 0;
                    background: rgba(0,0,0,0.5);
                    backdrop-filter: blur(4px);
                    z-index: 9999;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    animation: fadeIn 0.2s ease;
                }

                .modal-container {
                    background: white;
                    border-radius: 20px;
                    width: 95%;
                    max-width: 700px;
                    max-height: 85vh;
                    overflow-y: auto;
                    box-shadow: 0 25px 60px rgba(0,0,0,0.15);
                    animation: slideUp 0.3s ease;
                }

                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }

                @keyframes slideUp {
                    from { transform: translateY(20px); opacity: 0; }
                    to { transform: translateY(0); opacity: 1; }
                }

                .sending-pulse {
                    animation: sendPulse 1.5s ease-in-out infinite;
                }

                @keyframes sendPulse {
                    0%, 100% { opacity: 1; }
                    50% { opacity: 0.5; }
                }
            </style>

            <div class="page-header">
                <div class="page-header-title">
                    <div class="page-header-icon bg-gradient-to-br from-[#10b981] to-[#047857] text-white shadow-lg animate-pulse" style="animation-duration: 3s;">
                        <i class="ri-heart-pulse-fill"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold tracking-tight text-[#2c3e50]">Laporan Satu Sehat</h1>
                        <p class="text-xs text-[#878a99] font-medium mt-0.5">Rekapitulasi status bridging dan pengiriman pasien ke portal Satu Sehat.</p>
                    </div>
                </div>
                <div class="page-header-breadcrumb">
                    <a href="/dashboard" wire:navigate class="hover:text-[#405189] transition-colors"><i class="ri-home-4-line"></i></a>
                    <span class="sep text-gray-300">/</span>
                    <span class="text-gray-400 font-medium">Laporan</span>
                    <span class="sep text-gray-300">/</span>
                    <span class="text-[#10b981] font-bold">Satu Sehat</span>
                </div>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 mb-12 relative">
                {{-- Flash Messages --}}
                @if (session()->has('success'))
                    <div class="bg-emerald-50 text-emerald-700 border border-emerald-200 px-4 py-3 rounded-t-3xl sm:px-6 relative text-sm font-bold flex items-center gap-2">
                        <i class="ri-checkbox-circle-fill text-lg"></i> {{ session('success') }}
                    </div>
                @endif
                @if (session()->has('warning'))
                    <div class="bg-amber-50 text-amber-700 border border-amber-200 px-4 py-3 rounded-t-3xl sm:px-6 relative text-sm font-bold flex items-center gap-2">
                        <i class="ri-alert-fill text-lg"></i> {{ session('warning') }}
                    </div>
                @endif
                @if (session()->has('error'))
                    <div class="bg-red-50 text-red-700 border border-red-200 px-4 py-3 rounded-t-3xl sm:px-6 relative text-sm font-bold flex items-center gap-2">
                        <i class="ri-error-warning-fill text-lg"></i> {{ session('error') }}
                    </div>
                @endif

                {{-- Sending Overlay --}}
                @if($isSending)
                <div class="fixed inset-0 bg-white/80 backdrop-blur-md flex flex-col items-center justify-center" style="z-index: 9999;">
                    <div class="bg-white p-8 rounded-3xl shadow-2xl flex flex-col items-center gap-4 sending-pulse max-w-sm w-full text-center border border-emerald-100">
                        <div class="relative w-20 h-20 flex items-center justify-center">
                            <div class="absolute inset-0 border-4 border-emerald-100 rounded-full"></div>
                            <div class="absolute inset-0 border-4 border-emerald-500 rounded-full border-t-transparent animate-spin"></div>
                            <i class="ri-heart-pulse-fill text-3xl text-emerald-500"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-[#2c3e50] mb-1">Memproses Pengiriman...</h3>
                            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-widest px-4">Mohon tunggu, jangan tutup atau refresh halaman ini</p>
                        </div>
                    </div>
                </div>
                @endif

                <div class="p-4 sm:p-6 border-b border-gray-50 flex flex-col lg:flex-row justify-between items-stretch lg:items-center gap-4 sm:gap-6 glass-header sticky top-0 z-20 rounded-t-3xl">
                    <div class="grid grid-cols-2 lg:flex lg:items-end gap-3 sm:gap-4 w-full lg:w-auto">
                        <div class="space-y-1 col-span-2 lg:w-40 min-w-0">
                            <label class="text-[9px] font-black text-gray-400 uppercase tracking-widest px-1">Periode</label>
                            <x-custom-dropdown 
                                model="periodType" 
                                :options="$listPeriodType"
                                placeholder="Pilih Periode"
                                live="true"
                            />
                        </div>

                        @if($periodType === 'DAILY')
                        <div class="space-y-1 col-span-2 lg:w-44 min-w-0">
                            <label class="text-[9px] font-black text-gray-400 uppercase tracking-widest px-1">Tanggal</label>
                            <input type="date" wire:model.live="selectedDate" class="w-full bg-gray-50 border border-gray-100 rounded-xl py-2 px-4 text-sm font-bold text-[#2c3e50] focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-[#405189] transition-all outline-none">
                        </div>
                        @elseif($periodType === 'MONTHLY')
                        <div class="space-y-1 col-span-1 lg:w-40 min-w-0">
                            <label class="text-[9px] font-black text-gray-400 uppercase tracking-widest px-1">Bulan</label>
                            <x-custom-dropdown 
                                model="selectedBulan" 
                                :options="$listBulan"
                                placeholder="Pilih Bulan"
                                live="true"
                            />
                        </div>
                        <div class="space-y-1 col-span-1 lg:w-32 min-w-0">
                            <label class="text-[9px] font-black text-gray-400 uppercase tracking-widest px-1">Tahun</label>
                            <select wire:model.live="selectedTahun" class="w-full bg-gray-50 border border-gray-100 rounded-xl py-2.5 px-4 text-sm font-bold text-[#2c3e50] focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-[#405189] transition-all outline-none">
                                @foreach($availableYears as $year)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>
                        @elseif($periodType === 'YEARLY')
                        <div class="space-y-1 col-span-2 lg:w-32 min-w-0">
                            <label class="text-[9px] font-black text-gray-400 uppercase tracking-widest px-1">Tahun</label>
                            <select wire:model.live="selectedTahun" class="w-full bg-gray-50 border border-gray-100 rounded-xl py-2.5 px-4 text-sm font-bold text-[#2c3e50] focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-[#405189] transition-all outline-none">
                                @foreach($availableYears as $year)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                    </div>

                    <div class="flex flex-col sm:flex-row lg:items-end gap-4 w-full lg:w-auto">
                        <div class="relative flex-grow lg:min-w-[280px]">
                            <label class="lg:hidden text-[9px] font-black text-gray-400 uppercase tracking-widest px-1 block mb-1">Cari Pasien</label>
                            <div class="relative">
                                <i class="ri-search-2-line absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                                <input type="text" wire:model.live.debounce.300ms="search" 
                                       class="w-full bg-gray-50/50 border border-gray-200 rounded-2xl py-2.5 pl-12 pr-4 text-sm font-medium outline-none transition-all search-focus-glow placeholder:text-gray-300" 
                                       placeholder="Cari nama pasien, no rm, NIK...">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 sm:flex sm:items-center gap-3 w-full lg:w-auto lg:p-1 lg:rounded-lg lg:border lg:border-[#e2e8f0] lg:bg-white">
                            <a href="{{ route('laporan.satu-sehat.print', ['periodType' => $periodType, 'selectedDate' => $selectedDate, 'selectedBulan' => $selectedBulan, 'selectedTahun' => $selectedTahun, 'search' => $search]) }}" target="_blank" 
                               class="flex flex-row items-center justify-center gap-2 p-3 lg:p-0 lg:h-8 lg:w-8 rounded-xl lg:rounded-md bg-white border border-gray-100 lg:border-none shadow-sm lg:shadow-none hover:bg-indigo-50 transition-all group/print" title="Cetak PDF">
                                <i class="ri-printer-line text-lg text-indigo-500 group-hover/print:scale-110 transition-transform"></i>
                                <span class="lg:hidden text-[11px] font-bold text-gray-600">Cetak PDF</span>
                            </a>
                            <div class="hidden lg:block w-[1px] h-4 bg-[#e2e8f0]"></div>
                            <a href="{{ route('laporan.satu-sehat.export', ['periodType' => $periodType, 'selectedDate' => $selectedDate, 'selectedBulan' => $selectedBulan, 'selectedTahun' => $selectedTahun, 'search' => $search]) }}" target="_blank" 
                               class="flex flex-row items-center justify-center gap-2 p-3 lg:p-0 lg:h-8 lg:w-8 rounded-xl lg:rounded-md bg-white border border-gray-100 lg:border-none shadow-sm lg:shadow-none hover:bg-emerald-50 transition-all group/export" title="Unduh Excel">
                                <i class="ri-file-excel-2-line text-lg text-emerald-500 group-hover/export:scale-110 transition-transform"></i>
                                <span class="lg:hidden text-[11px] font-bold text-gray-600">Excel</span>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50">
                                <th class="px-6 py-4 border-b border-gray-50 w-[50px] text-center">
                                    <input type="checkbox" wire:model.live="selectAll" class="checkbox-custom mx-auto">
                                </th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">No</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Kunjungan & Tgl Periksa</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Nama Pasien & Info</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">NIK</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Status Bridging</th>
                                <th class="px-6 py-4 border-b border-gray-50 w-[140px]">
                                    <div class="flex flex-col items-center gap-1.5">
                                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">AKSI</span>
                                        <button wire:click="kirimSemua" wire:loading.attr="disabled" class="px-3 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 border border-emerald-200 rounded-lg text-[9px] font-bold uppercase transition-all shadow-sm group disabled:opacity-50">
                                            <i class="ri-send-plane-fill mr-1 group-hover:translate-x-0.5 transition-transform"></i> Kirim Semua
                                        </button>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($this->laporanSatuSehat as $index => $item)
                            <tr wire:key="kunjungan-{{ $item->nomor_kunjungan }}" class="laporan-row transition-all duration-200">
                                <td class="px-6 py-4 text-center">
                                    <input type="checkbox" wire:model.live="selectedKunjungan" value="{{ $item->nomor_kunjungan }}" class="checkbox-custom mx-auto">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-bold text-gray-400">{{ $this->laporanSatuSehat->firstItem() + $index }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-[#405189] text-xs">
                                        {{ $item->nomor_kunjungan }}
                                    </div>
                                    <div class="text-[10px] font-medium text-gray-500 mt-1">
                                        <i class="ri-calendar-event-line"></i> {{ $item->created_at ? $item->created_at->format('d/m/Y H:i') : '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-[#2c3e50] text-sm group-hover:text-[#405189] transition-colors">
                                        {{ $item->pasien ? $item->pasien->nama_pasien : '-' }}
                                    </div>
                                    <div class="mt-1 flex flex-wrap gap-1.5">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-gray-100 text-gray-600 border border-gray-200 uppercase">
                                            RM: {{ $item->pasien ? $item->pasien->no_rm : '-' }}
                                        </span>
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-indigo-50 text-indigo-600 border border-indigo-100 uppercase">
                                            {{ $item->poli ? $item->poli->nama_poli : '-' }}
                                        </span>
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-rose-50 text-rose-600 border border-rose-100 uppercase">
                                            {{ $item->asuransi ? $item->asuransi->nama_asuransi : 'PRIBADI' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-mono text-xs text-gray-600 font-bold">
                                        {{ $item->pasien && $item->pasien->nik ? $item->pasien->nik : '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    @php
                                        $status = $item->computed_status ?? 'Pending';
                                        $resourceStatuses = $item->resource_statuses ?? collect();
                                    @endphp

                                    {{-- Overall Status Badge --}}
                                    @if($status === 'Success')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-600 border border-emerald-100 text-[10px] font-bold uppercase tracking-wider">
                                            <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></div> Berhasil
                                        </span>
                                    @elseif($status === 'Partial')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-50 text-amber-600 border border-amber-100 text-[10px] font-bold uppercase tracking-wider">
                                            <div class="w-1.5 h-1.5 rounded-full bg-amber-500"></div> Partial
                                        </span>
                                    @elseif($status === 'Failed')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-red-50 text-red-600 border border-red-100 text-[10px] font-bold uppercase tracking-wider">
                                            <div class="w-1.5 h-1.5 rounded-full bg-red-500"></div> Gagal
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-gray-50 text-gray-500 border border-gray-200 text-[10px] font-bold uppercase tracking-wider">
                                            <div class="w-1.5 h-1.5 rounded-full bg-gray-400"></div> Pending
                                        </span>
                                    @endif

                                    {{-- Per-Resource Mini Badges --}}
                                    @if($status !== 'Pending')
                                    <div class="mt-2 flex flex-wrap gap-1">
                                        @foreach(['Encounter', 'Condition', 'Observation'] as $resType)
                                            @php
                                                $resGroup = $resourceStatuses->get($resType, collect());
                                                $hasSuccess = $resGroup->where('resource_status', 'Success')->count() > 0;
                                                $hasFailed  = $resGroup->where('resource_status', 'Failed')->count() > 0;
                                                $badgeClass = $resGroup->isEmpty() ? 'pending' : ($hasFailed ? 'failed' : ($hasSuccess ? 'success' : 'pending'));
                                                $icon = match($badgeClass) {
                                                    'success' => 'ri-check-line',
                                                    'failed'  => 'ri-close-line',
                                                    default   => 'ri-time-line',
                                                };
                                            @endphp
                                            <span class="resource-badge {{ $badgeClass }}">
                                                <i class="{{ $icon }}" style="font-size: 10px;"></i>
                                                {{ substr($resType, 0, 3) }}
                                            </span>
                                        @endforeach
                                    </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        @if($status !== 'Pending')
                                        <button wire:click="showDetail('{{ $item->nomor_kunjungan }}')" class="action-btn-soft bg-blue-50 text-blue-600 hover:bg-blue-100 shadow-sm" title="Detail Status">
                                            <i class="ri-eye-line text-sm"></i>
                                        </button>
                                        @endif
                                        <button wire:click="kirim('{{ $item->nomor_kunjungan }}')" wire:loading.attr="disabled" class="action-btn-soft bg-emerald-50 text-emerald-600 hover:bg-emerald-100 shadow-sm disabled:opacity-50" title="Kirim ke Satu Sehat">
                                            <i class="ri-send-plane-fill text-sm"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="py-20 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center mb-6 animate-bounce" style="animation-duration: 4s;">
                                            <i class="ri-heart-pulse-line text-5xl text-gray-200"></i>
                                        </div>
                                        <p class="text-lg font-black text-gray-400">Belum Ada Riwayat Bridging</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($this->laporanSatuSehat->hasPages())
                <div class="px-6 py-5 sm:px-8 sm:py-6 bg-gray-50/50 border-t border-gray-100 pagination-custom rounded-b-3xl">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-5">
                        <div class="text-[11px] font-bold text-[#878a99] tracking-tight text-center sm:text-left">
                            <i class="ri-list-check-2 text-[#405189] mr-1 hidden sm:inline"></i>
                            <span class="hidden sm:inline">Menampilkan</span> 
                            <span class="text-[#405189] font-black">{{ $this->laporanSatuSehat->firstItem() ?: 0 }} - {{ $this->laporanSatuSehat->lastItem() ?: 0 }}</span> 
                            dari <span class="text-[#405189] font-black">{{ number_format($this->laporanSatuSehat->total()) }}</span> 
                            <span class="hidden sm:inline">data</span>
                            <span class="sm:hidden">total data</span>
                        </div>
                        {{ $this->laporanSatuSehat->links() }}
                    </div>
                </div>
                @endif
            </div>

            {{-- ===== DETAIL MODAL ===== --}}
            @if($showDetailModal)
            <div class="modal-overlay" wire:click.self="closeDetail">
                <div class="modal-container">
                    <div class="sticky top-0 bg-white z-10 px-6 py-5 border-b border-gray-100 rounded-t-[20px] flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-black text-[#2c3e50]">
                                <i class="ri-file-list-3-line text-[#405189] mr-1"></i> Detail Status Bridging
                            </h3>
                            <p class="text-[11px] font-bold text-gray-400 mt-0.5">Kunjungan: <span class="text-[#405189]">{{ $detailNomorKunjungan }}</span></p>
                        </div>
                        <button wire:click="closeDetail" class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500 transition-all">
                            <i class="ri-close-line text-lg"></i>
                        </button>
                    </div>

                    <div class="p-6 space-y-4">
                        @php
                            $groupedStatuses = collect($detailStatuses)->groupBy('resource_type');
                        @endphp

                        @foreach(['Encounter', 'Condition', 'Observation'] as $resType)
                            @php
                                $resGroup = $groupedStatuses->get($resType, collect());
                                $hasSuccess = collect($resGroup)->where('resource_status', 'Success')->count() > 0;
                                $hasFailed  = collect($resGroup)->where('resource_status', 'Failed')->count() > 0;
                            @endphp
                            <div class="rounded-2xl border {{ $hasFailed ? 'border-red-200 bg-red-50/30' : ($hasSuccess ? 'border-emerald-200 bg-emerald-50/30' : 'border-gray-200 bg-gray-50/30') }} overflow-hidden">
                                <div class="px-4 py-3 flex items-center justify-between {{ $hasFailed ? 'bg-red-50' : ($hasSuccess ? 'bg-emerald-50' : 'bg-gray-50') }}">
                                    <div class="flex items-center gap-2">
                                        @if($hasFailed)
                                            <div class="w-6 h-6 rounded-full bg-red-100 flex items-center justify-center">
                                                <i class="ri-close-line text-red-600 text-xs font-bold"></i>
                                            </div>
                                        @elseif($hasSuccess)
                                            <div class="w-6 h-6 rounded-full bg-emerald-100 flex items-center justify-center">
                                                <i class="ri-check-line text-emerald-600 text-xs font-bold"></i>
                                            </div>
                                        @else
                                            <div class="w-6 h-6 rounded-full bg-gray-200 flex items-center justify-center">
                                                <i class="ri-time-line text-gray-500 text-xs font-bold"></i>
                                            </div>
                                        @endif
                                        <span class="text-xs font-black text-[#2c3e50] uppercase tracking-wider">{{ $resType }}</span>
                                        <span class="text-[9px] font-bold {{ $hasFailed ? 'text-red-500' : ($hasSuccess ? 'text-emerald-500' : 'text-gray-400') }}">
                                            ({{ collect($resGroup)->count() }} record)
                                        </span>
                                    </div>
                                    @if($hasFailed)
                                        <button wire:click="retryResource('{{ $detailNomorKunjungan }}', '{{ $resType }}')" 
                                                wire:loading.attr="disabled"
                                                class="px-3 py-1.5 bg-red-100 hover:bg-red-200 text-red-700 rounded-lg text-[9px] font-bold uppercase transition-all flex items-center gap-1 disabled:opacity-50">
                                            <i class="ri-refresh-line"></i> Kirim Ulang
                                        </button>
                                    @endif
                                </div>

                                @if(collect($resGroup)->isNotEmpty())
                                <div class="divide-y divide-gray-100">
                                    @foreach($resGroup as $statusRow)
                                    <div class="px-4 py-2.5 flex items-center justify-between text-[11px]">
                                        <div class="flex items-center gap-2">
                                            @if($statusRow['resource_status'] === 'Success')
                                                <span class="resource-badge success">
                                                    <i class="ri-check-line" style="font-size: 10px;"></i> Success
                                                </span>
                                            @else
                                                <span class="resource-badge failed">
                                                    <i class="ri-close-line" style="font-size: 10px;"></i> Failed
                                                </span>
                                            @endif
                                            @if(!empty($statusRow['resource_uuid']))
                                                <span class="font-mono text-[9px] text-gray-400 max-w-[200px] truncate" title="{{ $statusRow['resource_uuid'] }}">
                                                    UUID: {{ $statusRow['resource_uuid'] }}
                                                </span>
                                            @endif
                                        </div>
                                        <span class="text-[9px] font-medium text-gray-400">
                                            {{ \Carbon\Carbon::parse($statusRow['created_at'])->format('d/m/Y H:i') }}
                                        </span>
                                    </div>
                                    @endforeach
                                </div>
                                @else
                                <div class="px-4 py-3 text-[11px] text-gray-400 font-medium text-center italic">
                                    Belum ada data untuk resource ini
                                </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50 rounded-b-[20px] flex justify-end">
                        <button wire:click="closeDetail" class="px-5 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-xl text-[11px] font-bold uppercase transition-all">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
            @endif
        </div>
        HTML;
    }
}
