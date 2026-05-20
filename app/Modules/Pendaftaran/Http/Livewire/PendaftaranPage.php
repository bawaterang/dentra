<?php

namespace App\Modules\Pendaftaran\Http\Livewire;

use Livewire\Component;
use App\Models\TrxPendaftaran;
use Carbon\Carbon;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;

class PendaftaranPage extends Component
{
    use WithPagination;

    public $selectedDate;
    public $selectedStatus = 'all';
    public $search = '';
    public $totalPendaftaran = 0;
    public $terdaftar = 0;
    public $menungguScreening = 0;
    public $selesai = 0;
    public $viewMode = 'table'; // table or grid

    // Edit Pendaftaran
    public $showEditModal = false;
    public $editPendaftaranId, $editPasienId, $editAntrianId;
    public $editTanggalAntrian, $editJenisAntrian, $editTimeSlot, $editModeAntrian;
    public $editAvailableTimeSlots = [];
    public $editPoliId, $editDokterId, $editAsuransiId, $editNoKartuAsuransi;
    public $editKesadaran = '01', $editTd, $editNadi, $editSuhu, $editBb, $editTb, $editLp;
    public $editRiwayat, $editKodeAlergi, $editAlergi, $editKet;
    
    // Dropdown Data
    public $poliList = [], $dokterList = [], $asuransiList = [];
    public $kesadaranList = [];
    public $alergiList = [];

    public function mount()
    {
        $this->selectedDate = now()->format('Y-m-d');
        $this->kesadaranList = \App\Models\MstKesadaran::all()->map(fn($k) => ['value' => $k->kdSadar, 'label' => $k->nmSadar, 'icon' => 'ri-checkbox-circle-line text-green-500'])->toArray();
        $this->alergiList = \App\Models\MstAlergi::all()->map(fn($a) => ['value' => $a->kdAlergi, 'label' => $a->nmAlergi, 'icon' => 'ri-bug-line text-red-500'])->toArray();
        $setting = \App\Models\MstSettingAntrian::first();
        if ($setting) {
            $this->editModeAntrian = $setting->mode_antrian;
        } else {
            $this->editModeAntrian = 'Nomor Urut';
        }
    }

    public function updatedSelectedDate() { $this->resetPage(); }
    public function updatedSearch() { $this->resetPage(); }

    public function prevDate()
    {
        $this->selectedDate = Carbon::parse($this->selectedDate)->subDay()->format('Y-m-d');
        $this->resetPage();
    }

    public function nextDate()
    {
        $this->selectedDate = Carbon::parse($this->selectedDate)->addDay()->format('Y-m-d');
        $this->resetPage();
    }

    public function setStatus($status) { $this->selectedStatus = $status; $this->resetPage(); }

    public function editPendaftaran($id)
    {
        $p = TrxPendaftaran::findOrFail($id);
        $this->editPendaftaranId = $p->id;
        $this->editPasienId = $p->pasien_id;
        $this->editAntrianId = $p->antrian_id;
        
        $antrian = $p->antrian;
        if ($antrian) {
            $this->editTanggalAntrian = $antrian->tanggal_antrian;
            $this->editJenisAntrian = $antrian->jenis_antrian ?? 'offline';
            $this->editTimeSlot = $antrian->time_slot;
        } else {
            $this->editTanggalAntrian = now()->format('Y-m-d');
            $this->editJenisAntrian = 'offline';
            $this->editTimeSlot = null;
        }

        $this->editPoliId = $p->poli_id;
        $this->editDokterId = $p->dokter_id;
        $this->editAsuransiId = $p->asuransi_id;
        $this->editNoKartuAsuransi = $p->no_kartu_asuransi;
        $this->editKesadaran = $p->kesadaran;
        $this->editTd = $p->tekanan_darah;
        $this->editNadi = $p->nadi;
        $this->editSuhu = $p->suhu;
        $this->editBb = $p->berat_badan;
        $this->editTb = $p->tinggi_badan;
        $this->editLp = $p->lingkar_perut;
        $this->editRiwayat = $p->riwayat_penyakit;
        $this->editKodeAlergi = $p->kode_alergi;
        $this->editAlergi = $p->alergi;
        $this->editKet = $p->keterangan_lain;

        $this->poliList = \App\Models\MstPoli::where(fn($q) => $q->where('status', 'Aktif'))->get()->map(fn($p) => ['value' => $p->id, 'label' => $p->nama_poli, 'icon' => 'ri-hospital-line text-blue-500'])->toArray();
        $this->dokterList = \App\Models\MstDokter::where(fn($q) => $q->where('status', 'Aktif'))->get()->map(fn($d) => ['value' => $d->id, 'label' => $d->nama_dokter, 'icon' => 'ri-user-star-line text-purple-500'])->toArray();
        $this->asuransiList = \App\Models\MstAsuransi::where(fn($q) => $q->where('status', 'Aktif'))->get()->map(fn($a) => ['value' => $a->id, 'label' => $a->nama_asuransi, 'icon' => 'ri-shield-check-line text-green-500'])->toArray();

        $this->loadEditAvailableSlots();
        $this->showEditModal = true;
        $this->dispatch('refresh-table');
    }

    public function updatedEditTanggalAntrian() { $this->loadEditAvailableSlots(); }
    public function updatedEditPoliId() { $this->editDokterId = null; $this->editTimeSlot = null; $this->loadEditAvailableSlots(); }
    public function updatedEditDokterId() { $this->editTimeSlot = null; $this->loadEditAvailableSlots(); }

    public function loadEditAvailableSlots()
    {
        if ($this->editModeAntrian === 'Nomor Urut' || empty($this->editTanggalAntrian)) {
            $this->editAvailableTimeSlots = [];
            return;
        }

        $hariMap = ['Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu'];
        $hariNama = $hariMap[Carbon::parse($this->editTanggalAntrian)->format('l')];
        
        $query = \App\Models\TrxAntrian::whereDate('tanggal_antrian', $this->editTanggalAntrian)
            ->where('status', '!=', 'batal')
            ->whereNotNull('time_slot');

        if ($this->editAntrianId) {
            $query->where('id', '!=', $this->editAntrianId);
        }

        if ($this->editPoliId) {
            $poli = \App\Models\MstPoli::find($this->editPoliId);
            if ($poli) $query->where('kode_poli', $poli->kode_poli);
        }
        if ($this->editDokterId) {
            $dokter = \App\Models\MstDokter::find($this->editDokterId);
            if ($dokter) $query->where('kode_dokter', $dokter->kode_dokter);
        }

        $bookedSlotsShort = $query->pluck('time_slot')
            ->map(function($t) { return substr($t, 0, 5); })
            ->toArray();

        $this->editAvailableTimeSlots = \App\Models\MstSettingAntrianDetail::where('hari', $hariNama)
            ->orderBy('waktu')
            ->get()
            ->filter(function($slot) use ($bookedSlotsShort) {
                return !in_array(substr($slot->waktu, 0, 5), $bookedSlotsShort);
            })
            ->map(function($slot) {
                return [
                    'value' => substr($slot->waktu, 0, 5) . ':00',
                    'label' => substr($slot->waktu, 0, 5) . ' (' . $slot->nomor_urut . ')',
                    'icon' => 'ri-time-line text-green-500'
                ];
            })->values()->toArray();
            
        if ($this->editTimeSlot && !in_array(substr($this->editTimeSlot, 0, 5).':00', array_column($this->editAvailableTimeSlots, 'value'))) {
            $this->editTimeSlot = null;
        }
    }

    public function updatePendaftaran()
    {
        $this->validate([
            'editPoliId' => 'required|exists:mst_poli,id',
            'editDokterId' => 'required|exists:mst_dokter,id',
        ]);

        $p = TrxPendaftaran::findOrFail($this->editPendaftaranId);
        $p->update([
            'poli_id' => $this->editPoliId,
            'dokter_id' => $this->editDokterId,
            'asuransi_id' => $this->editAsuransiId,
            'no_kartu_asuransi' => $this->editNoKartuAsuransi,
            'kesadaran' => $this->editKesadaran,
            'tekanan_darah' => $this->editTd,
            'nadi' => $this->editNadi,
            'suhu' => $this->editSuhu,
            'berat_badan' => $this->editBb,
            'tinggi_badan' => $this->editTb,
            'lingkar_perut' => $this->editLp,
            'riwayat_penyakit' => $this->editRiwayat,
            'kode_alergi' => $this->editKodeAlergi,
            'alergi' => $this->editAlergi,
            'keterangan_lain' => $this->editKet,
        ]);

        if ($p->antrian_id) {
            $poli = \App\Models\MstPoli::find($this->editPoliId);
            $dokter = \App\Models\MstDokter::find($this->editDokterId);
            \App\Models\TrxAntrian::where('id', $p->antrian_id)->update([
                'tanggal_antrian' => $this->editTanggalAntrian,
                'jenis_antrian' => $this->editJenisAntrian,
                'time_slot' => $this->editTimeSlot,
                'kode_poli' => $poli ? $poli->kode_poli : null,
                'kode_dokter' => $dokter ? $dokter->kode_dokter : null,
            ]);
        }

        $this->showEditModal = false;
        $this->dispatch('refresh-table');
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Pendaftaran berhasil diperbarui.']);
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->dispatch('refresh-table');
    }

    public function deletePendaftaran($id)
    {
        $p = TrxPendaftaran::findOrFail($id);
        
        // Kembalikan status antrian jika pendaftaran dibatalkan
        if ($p->antrian_id) {
            \App\Models\TrxAntrian::where('id', $p->antrian_id)->update(['status' => 'menunggu']);
        }
        
        $p->delete();
        
        $this->dispatch('refresh-table');
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Pendaftaran berhasil dibatalkan.']);
    }

    #[Computed]
    public function pendaftaranList()
    {
        $query = TrxPendaftaran::with(['pasien', 'poli', 'dokter', 'asuransi'])
            ->whereDate('created_at', $this->selectedDate);

        if ($this->selectedStatus !== 'all') {
            if ($this->selectedStatus === 'menunggu_screening') {
                $query->whereIn('status', ['terdaftar', 'menunggu_screening']);
            } else {
                $query->where('status', $this->selectedStatus);
            }
        }

        if ($this->search) {
            $query->where(function($q) {
                $q->where('nomor_kunjungan', 'like', '%' . $this->search . '%')
                  ->orWhereHas('pasien', function($qp) {
                      $qp->where('nama_pasien', 'like', '%' . $this->search . '%')
                        ->orWhere('no_rm', 'like', '%' . $this->search . '%');
                  });
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate(25);
    }

    #[Computed]
    public function groupedPendaftaranList()
    {
        $query = TrxPendaftaran::with(['pasien', 'poli', 'dokter', 'asuransi', 'antrian'])
            ->whereDate('created_at', $this->selectedDate);

        if ($this->selectedStatus !== 'all') {
            if ($this->selectedStatus === 'menunggu_screening') {
                $query->whereIn('status', ['terdaftar', 'menunggu_screening']);
            } else {
                $query->where('status', $this->selectedStatus);
            }
        }

        if ($this->search) {
            $query->where(function($q) {
                $q->where('nomor_kunjungan', 'like', '%' . $this->search . '%')
                  ->orWhereHas('pasien', function($qp) {
                      $qp->where('nama_pasien', 'like', '%' . $this->search . '%')
                        ->orWhere('no_rm', 'like', '%' . $this->search . '%');
                  });
            });
        }

        $allData = $query->orderBy('created_at', 'desc')->get();
        
        $grouped = [];
        foreach($allData as $item) {
            $slot = ($item->antrian && $item->antrian->time_slot) ? substr($item->antrian->time_slot, 0, 5) : 'Walk-in';
            if (!isset($grouped[$slot])) {
                $grouped[$slot] = [];
            }
            $grouped[$slot][] = $item;
        }
        
        ksort($grouped);
        return $grouped;
    }

    public function render()
    {
        $this->totalPendaftaran = TrxPendaftaran::whereDate('created_at', $this->selectedDate)->count();
        $this->terdaftar = TrxPendaftaran::whereDate('created_at', $this->selectedDate)->where('status', 'terdaftar')->count();
        $this->menungguScreening = TrxPendaftaran::whereDate('created_at', $this->selectedDate)
            ->whereIn('status', ['terdaftar', 'menunggu_screening'])->count();
        $this->selesai = TrxPendaftaran::whereDate('created_at', $this->selectedDate)->where('status', 'selesai')->count();

        return view('livewire.modules.pendaftaran.pendaftaran-page');
    }
}
