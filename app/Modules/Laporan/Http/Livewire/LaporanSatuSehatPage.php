<?php

namespace App\Modules\Laporan\Http\Livewire;

use App\Models\TrxPendaftaran;
use App\Models\TrxSatusehatLog;
use App\Models\TrxTindakan;
use App\Modules\Bridging\Services\SatuSehatService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;


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
        $statuses = TrxSatusehatLog::where('nomor_kunjungan', $nomorKunjungan)->get();

        if ($statuses->isEmpty()) {
            return 'Pending';
        }

        $successCount = $statuses->where('status', 'Success')->count();
        $failedCount = $statuses->where('status', 'Failed')->count();

        $hasEncounter = $statuses->where('resource_type', 'Encounter')->where('status', 'Success')->count() > 0;
        $hasCondition = $statuses->where('resource_type', 'Condition')->where('status', 'Success')->count() > 0;
        $hasObservation = $statuses->where('resource_type', 'Observation')->where('status', 'Success')->count() > 0;
        $hasProcedure = $statuses->where('resource_type', 'Procedure')->where('status', 'Success')->count() > 0;
        $hasComposition = $statuses->where('resource_type', 'Composition')->where('status', 'Success')->count() > 0;
        $hasMedication = $statuses->where('resource_type', 'Medication')->where('status', 'Success')->count() > 0;
        $hasMedicationRequest = $statuses->where('resource_type', 'MedicationRequest')->where('status', 'Success')->count() > 0;

        // Procedure dianggap opsional: jika tidak ada tindakan, tidak perlu sukses
        $hasTindakan = TrxTindakan::where('nomor_kunjungan', $nomorKunjungan)->exists();
        $procedureOk = ! $hasTindakan || $hasProcedure;

        // Medication opsional: jika tidak ada obat di kunjungan, tidak perlu sukses
        $hasObat = DB::table('trx_obat')->where('nomor_kunjungan', $nomorKunjungan)->whereNull('deleted_at')->exists();
        $medicationOk = ! $hasObat || ($hasMedication && $hasMedicationRequest);

        if ($hasEncounter && $hasCondition && $hasObservation && $procedureOk && $medicationOk && $hasComposition && $failedCount === 0) {
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

        if (! empty($this->search)) {
            $query->whereHas('pasien', function ($q) {
                $q->where('nama_pasien', 'like', '%'.$this->search.'%')
                    ->orWhere('no_rm', 'like', '%'.$this->search.'%')
                    ->orWhere('nik', 'like', '%'.$this->search.'%');
            });
        }

        $results = $query->orderBy('trx_pendaftaran.created_at', 'desc')->paginate(20);

        // Compute bundle status per item
        $results->getCollection()->transform(function ($item) {
            $item->computed_status = $this->computeBundleStatus($item->nomor_kunjungan);

            // Load per-resource detail
            $item->resource_statuses = TrxSatusehatLog::where('nomor_kunjungan', $item->nomor_kunjungan)
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
            $this->selectedKunjungan = $this->laporanSatuSehat->pluck('nomor_kunjungan')->map(fn ($id) => (string) $id)->toArray();
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
            $service = new SatuSehatService($dokterId);
            $createdBy = Auth::user()?->username ?? 'System';

            // Cari status saat ini untuk melihat bagian mana yang belum sukses
            $statuses = TrxSatusehatLog::where('nomor_kunjungan', $nomor_kunjungan)->get();
            $statusGroup = $statuses->groupBy('resource_type');

            $hasEncounterSuccess = $statusGroup->get('Encounter', collect())->where('status', 'Success')->count() > 0;
            $hasConditionSuccess = $statusGroup->get('Condition', collect())->where('status', 'Success')->count() > 0;
            $hasObservationSuccess = $statusGroup->get('Observation', collect())->where('status', 'Success')->count() > 0;
            $hasProcedureSuccess = $statusGroup->get('Procedure', collect())->where('status', 'Success')->count() > 0;
            $hasMedicationSuccess = $statusGroup->get('Medication', collect())->where('status', 'Success')->count() > 0;
            $hasMedicationRequestSuccess = $statusGroup->get('MedicationRequest', collect())->where('status', 'Success')->count() > 0;
            $hasCompositionSuccess = $statusGroup->get('Composition', collect())->where('status', 'Success')->count() > 0;

            if ($statuses->isEmpty() || ! $hasEncounterSuccess) {
                // Jika Encounter belum pernah sukses, jalankan ulang seluruh flow
                $result = $service->sendResumeMedis($nomor_kunjungan, $createdBy);

                // Tampilkan warning obat yang belum di-mapping KFA
                if (! empty($result['unmapped_drugs'])) {
                    $unmappedNames = collect($result['unmapped_drugs'])->pluck('nama_obat')->implode(', ');
                    session()->flash('warning', "Obat berikut belum di-mapping KFA dan tidak dikirim: {$unmappedNames}. Silakan mapping di menu Setting Obat KFA.");
                }
            } else {
                // Smart Retry: kirim hanya resource yang masih gagal atau belum terkirim
                $errors = [];
                if (! $hasConditionSuccess) {
                    $res = $service->retrySendResource($nomor_kunjungan, 'Condition', $createdBy);
                    if (! empty($res['errors'])) {
                        $errors = array_merge($errors, $res['errors']);
                    }
                }
                if (! $hasObservationSuccess) {
                    $res = $service->retrySendResource($nomor_kunjungan, 'Observation', $createdBy);
                    if (! empty($res['errors'])) {
                        $errors = array_merge($errors, $res['errors']);
                    }
                }
                if (! $hasProcedureSuccess) {
                    $res = $service->retrySendResource($nomor_kunjungan, 'Procedure', $createdBy);
                    if (! empty($res['errors'])) {
                        $errors = array_merge($errors, $res['errors']);
                    }
                }
                if (! $hasMedicationSuccess || ! $hasMedicationRequestSuccess) {
                    $res = $service->retrySendResource($nomor_kunjungan, 'Medication', $createdBy);
                    if (! empty($res['errors'])) {
                        $errors = array_merge($errors, $res['errors']);
                    }
                }
                if (! $hasCompositionSuccess) {
                    $res = $service->retrySendResource($nomor_kunjungan, 'Composition', $createdBy);
                    if (! empty($res['errors'])) {
                        $errors = array_merge($errors, $res['errors']);
                    }
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
            Log::error("Kirim SatuSehat gagal [{$nomor_kunjungan}]: ".$e->getMessage());
            session()->flash('error', "Gagal kirim {$nomor_kunjungan}: ".$e->getMessage());
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
        $total = count($this->selectedKunjungan);
        $success = 0;
        $failed = 0;
        $partial = 0;

        foreach ($this->selectedKunjungan as $nomorKunjungan) {
            try {
                $pendaftaran = TrxPendaftaran::with('dokter')
                    ->where('nomor_kunjungan', $nomorKunjungan)
                    ->first();

                $dokterId = $pendaftaran?->dokter_id;
                $service = new SatuSehatService($dokterId);
                $createdBy = Auth::user()?->username ?? 'System';

                // Smart Retry / Kirim Logic per Kunjungan
                $statuses = TrxSatusehatLog::where('nomor_kunjungan', $nomorKunjungan)->get();
                $statusGroup = $statuses->groupBy('resource_type');

                $hasEncounterSuccess = $statusGroup->get('Encounter', collect())->where('status', 'Success')->count() > 0;
                $hasConditionSuccess = $statusGroup->get('Condition', collect())->where('status', 'Success')->count() > 0;
                $hasObservationSuccess = $statusGroup->get('Observation', collect())->where('status', 'Success')->count() > 0;
                $hasProcedureSuccess = $statusGroup->get('Procedure', collect())->where('status', 'Success')->count() > 0;
                $hasMedicationSuccess = $statusGroup->get('Medication', collect())->where('status', 'Success')->count() > 0;
                $hasMedicationRequestSuccess = $statusGroup->get('MedicationRequest', collect())->where('status', 'Success')->count() > 0;
                $hasCompositionSuccess = $statusGroup->get('Composition', collect())->where('status', 'Success')->count() > 0;

                $hasError = false;

                if ($statuses->isEmpty() || ! $hasEncounterSuccess) {
                    $result = $service->sendResumeMedis($nomorKunjungan, $createdBy);
                    if (! empty($result['errors'])) {
                        $hasError = true;
                    }
                } else {
                    if (! $hasConditionSuccess) {
                        $res = $service->retrySendResource($nomorKunjungan, 'Condition', $createdBy);
                        if (! empty($res['errors'])) {
                            $hasError = true;
                        }
                    }
                    if (! $hasObservationSuccess) {
                        $res = $service->retrySendResource($nomorKunjungan, 'Observation', $createdBy);
                        if (! empty($res['errors'])) {
                            $hasError = true;
                        }
                    }
                    if (! $hasProcedureSuccess) {
                        $res = $service->retrySendResource($nomorKunjungan, 'Procedure', $createdBy);
                        if (! empty($res['errors'])) {
                            $hasError = true;
                        }
                    }
                    if (! $hasMedicationSuccess || ! $hasMedicationRequestSuccess) {
                        $res = $service->retrySendResource($nomorKunjungan, 'Medication', $createdBy);
                        if (! empty($res['errors'])) {
                            $hasError = true;
                        }
                    }
                    if (! $hasCompositionSuccess) {
                        $res = $service->retrySendResource($nomorKunjungan, 'Composition', $createdBy);
                        if (! empty($res['errors'])) {
                            $hasError = true;
                        }
                    }
                }

                if (! $hasError) {
                    $success++;
                } else {
                    $partial++;
                }
            } catch (\Exception $e) {
                $failed++;
                Log::error("Kirim batch SatuSehat gagal [{$nomorKunjungan}]: ".$e->getMessage());
            }
        }

        $msg = "Batch selesai ({$total} kunjungan): ";
        $parts = [];
        if ($success > 0) {
            $parts[] = "{$success} berhasil";
        }
        if ($partial > 0) {
            $parts[] = "{$partial} partial";
        }
        if ($failed > 0) {
            $parts[] = "{$failed} gagal";
        }
        $msg .= implode(', ', $parts).'.';

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
            // Cek apakah ada resource yang gagal
            $failedCount = TrxSatusehatLog::where('nomor_kunjungan', $nomorKunjungan)
                ->where('resource_type', $resourceType)
                ->where('status', 'Failed')
                ->count();

            if ($failedCount === 0) {
                session()->flash('info', "Tidak ada resource {$resourceType} yang gagal untuk {$nomorKunjungan}.");
                $this->isSending = false;

                return;
            }

            $pendaftaran = TrxPendaftaran::with('dokter')
                ->where('nomor_kunjungan', $nomorKunjungan)
                ->first();

            $dokterId = $pendaftaran?->dokter_id;
            $service = new SatuSehatService($dokterId);
            $createdBy = Auth::user()?->username ?? 'System';

            $result = $service->retrySendResource($nomorKunjungan, $resourceType, $createdBy);

            // Cek apakah ada item yang di-skip
            $skippedItems = collect($result['items'] ?? [])->where('status', 'Skipped')->count();

            if ($skippedItems > 0 && empty($result['errors'] ?? [])) {
                session()->flash('info', "Retry {$resourceType}: {$skippedItems} resource tidak perlu dikirim ulang (sudah berhasil).");
            } elseif (empty($result['errors'] ?? [])) {
                session()->flash('success', "Retry {$resourceType} untuk {$nomorKunjungan} berhasil.");
            } else {
                session()->flash('warning', "Retry {$resourceType} selesai dengan error.");
            }
        } catch (\Exception $e) {
            Log::error("Retry resource gagal [{$nomorKunjungan}][{$resourceType}]: ".$e->getMessage());
            session()->flash('error', "Retry {$resourceType} gagal: ".$e->getMessage());
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
        $this->detailStatuses = TrxSatusehatLog::where('nomor_kunjungan', $nomorKunjungan)
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
        return view('livewire.modules.laporan.laporan-satu-sehat-page');
    }
}
