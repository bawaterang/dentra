<?php

namespace App\Modules\Antrian\Http\Livewire;

use Livewire\Component;
use App\Models\TrxAntrian;
use App\Models\MstPasien;
use Carbon\Carbon;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;

class AntrianPage extends Component
{
    use WithPagination;

    public $selectedDate;
    public $selectedStatus = 'all';
    public $totalAntrian = 0;
    public $menunggu = 0;
    public $dipanggil = 0;
    public $selesai = 0;
    public $batal = 0;
    public $search = '';
    public $viewMode = 'table'; // table or grid

    public $syncAntrianId;
    public $searchPasien = '';
    public $pasienResults = [];
    public $showSyncModal = false;
    public $isSyncForEdit = false; // Flag to check if search is from Edit modal

    // Edit Antrian Modal
    public $editAntrianId;
    public $editNamaPasien, $editPoli, $editDokter, $editTanggal, $editAsuransi, $editNoAsuransi;
    public $poliList = [], $dokterList = [], $asuransiList = [];
    public $showEditModal = false;

    public function mount()
    {
        $this->selectedDate = now()->format('Y-m-d');
    }

    public function updatedSelectedDate()
    {
        $this->resetPage();
    }
    public function updatedSearch()
    {
        $this->resetPage();
    }

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

    public function setStatus($status)
    {
        $this->selectedStatus = $status;
        $this->resetPage();
    }

    public function panggilBerikutnya()
    {
        $next = TrxAntrian::where(fn($q) => $q->where('tanggal_antrian', $this->selectedDate))
            ->where(fn($q) => $q->where('status', 'menunggu'))
            ->orderBy('nomor_antrian')
            ->first();

        if (!$next) {
            $this->dispatch('alert', ['type' => 'info', 'message' => 'Tidak ada antrian yang menunggu.']);
            return;
        }

        $next->update([
            'status' => 'dipanggil',
            'waktu_panggil' => now(),
        ]);

        $this->dispatch('refresh-table');
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Memanggil antrian nomor ' . $next->nomor_antrian . ' - ' . ($next->pasien?->nama_pasien ?? $next->nama_pasien_input_manual)]);
    }

    public function ubahStatus($id, $status)
    {
        $antrian = TrxAntrian::findOrFail($id);
        $data = ['status' => $status];
        if ($status === 'hadir') {
            $data['waktu_hadir'] = now();
        }
        if ($status === 'dipanggil') {
            $data['waktu_panggil'] = now();
        }
        $antrian->update($data);
        $this->dispatch('refresh-table');
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Status antrian diubah menjadi ' . ucfirst(str_replace('_', ' ', $status))]);
    }

    // Sinkronisasi pasien
    public function openSyncModal($antrianId)
    {
        $this->syncAntrianId = $antrianId;
        $this->searchPasien = '';
        $this->pasienResults = [];
        $this->isSyncForEdit = false;
        $this->showSyncModal = true;
        $this->dispatch('refresh-table');
    }

    public function updatedSearchPasien($value)
    {
        if (strlen($value) >= 2) {
            $this->pasienResults = MstPasien::where(function ($q) use ($value) {
                $q->where('nama_pasien', 'like', '%' . $value . '%')
                    ->orWhere('nik', 'like', '%' . $value . '%')
                    ->orWhere('no_telepon', 'like', '%' . $value . '%')
                    ->orWhere('no_rm', 'like', '%' . $value . '%');
            })
                ->limit(10)
                ->get()
                ->toArray();
        } else {
            $this->pasienResults = [];
        }
    }

    public function pilihPasien($pasienId)
    {
        $pasien = MstPasien::findOrFail($pasienId);

        if ($this->isSyncForEdit) {
            $this->editNamaPasien = $pasien->nama_pasien;
            // Also update the hidden pasien_id for the edit
            $this->dispatch('alert', ['type' => 'info', 'message' => 'Pasien terpilih: ' . $pasien->nama_pasien]);
        } else {
            $antrian = TrxAntrian::findOrFail($this->syncAntrianId);
            $antrian->update(['pasien_id' => $pasien->id]);
            $this->dispatch('alert', ['type' => 'success', 'message' => 'Pasien berhasil disinkronkan: ' . $pasien->nama_pasien . ' (' . $pasien->no_rm . ')']);
        }

        $this->showSyncModal = false;
        $this->dispatch('refresh-table');
    }

    public function daftarkan($antrianId)
    {
        $antrian = TrxAntrian::findOrFail($antrianId);
        return redirect()->route('pendaftaran.create', ['antrian_id' => $antrian->id, 'pasien_id' => $antrian->pasien_id]);
    }

    public function editAntrian($id)
    {
        $antrian = TrxAntrian::findOrFail($id);
        $this->editAntrianId = $antrian->id;
        $this->editNamaPasien = $antrian->pasien?->nama_pasien ?? $antrian->nama_pasien_input_manual;
        $this->editPoli = $antrian->kode_poli;
        $this->editDokter = $antrian->kode_dokter;
        $this->editTanggal = \Carbon\Carbon::parse($antrian->tanggal_antrian)->format('Y-m-d');
        $this->editAsuransi = $antrian->asuransi;
        $this->editNoAsuransi = $antrian->no_asuransi;

        $this->poliList = \App\Models\MstPoli::where(fn($q) => $q->where('status', 'Aktif'))->get()->toArray();
        $this->dokterList = \App\Models\MstDokter::where(fn($q) => $q->where('status', 'Aktif'))->get()->toArray();
        $this->asuransiList = \App\Models\MstAsuransi::where(fn($q) => $q->where('status', 'Aktif'))->get()->toArray();
        $this->showEditModal = true;

        $this->dispatch('refresh-table');
    }

    public function updateAntrian()
    {
        $antrian = TrxAntrian::findOrFail($this->editAntrianId);

        $data = [
            'tanggal_antrian' => $this->editTanggal,
            'kode_poli' => $this->editPoli,
            'kode_dokter' => $this->editDokter,
            'asuransi' => $this->editAsuransi,
            'no_asuransi' => $this->editNoAsuransi,
        ];
        if (!$antrian->pasien_id) {
            $data['nama_pasien_input_manual'] = $this->editNamaPasien;
        }

        $antrian->update($data);
        $this->showEditModal = false;
        $this->dispatch('refresh-table');
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Data antrian berhasil diperbarui.']);
    }

    #[Computed]
    public function antrianList()
    {
        $query = TrxAntrian::with(['pasien', 'poli', 'dokter'])
            ->whereDate('tanggal_antrian', $this->selectedDate)
            ->where('status', '!=', 'batal');

        if ($this->selectedStatus !== 'all') {
            $query->where('status', $this->selectedStatus);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('nomor_antrian', 'like', '%' . $this->search . '%')
                    ->orWhere('nama_pasien_input_manual', 'like', '%' . $this->search . '%')
                    ->orWhereHas('pasien', function ($qp) {
                        $qp->where('nama_pasien', 'like', '%' . $this->search . '%')
                            ->orWhere('no_rm', 'like', '%' . $this->search . '%');
                    });
            });
        }

        return $query->orderBy('nomor_antrian')->paginate(25);
    }

    #[Computed]
    public function groupedAntrianList()
    {
        $query = TrxAntrian::with(['pasien', 'poli', 'dokter'])
            ->whereDate('tanggal_antrian', $this->selectedDate)
            ->where('status', '!=', 'batal');

        if ($this->selectedStatus !== 'all') {
            $query->where('status', $this->selectedStatus);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('nomor_antrian', 'like', '%' . $this->search . '%')
                    ->orWhere('nama_pasien_input_manual', 'like', '%' . $this->search . '%')
                    ->orWhereHas('pasien', function ($qp) {
                        $qp->where('nama_pasien', 'like', '%' . $this->search . '%')
                            ->orWhere('no_rm', 'like', '%' . $this->search . '%');
                    });
            });
        }

        $allData = $query->orderBy('nomor_antrian')->get();

        $grouped = [];
        foreach ($allData as $item) {
            $slot = $item->time_slot ? substr($item->time_slot, 0, 5) : 'Walk-in';
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
        $dayQuery = TrxAntrian::where(fn($q) => $q->where('tanggal_antrian', $this->selectedDate));
        $this->totalAntrian = (clone $dayQuery)->count();
        $this->menunggu = (clone $dayQuery)->where(fn($q) => $q->where('status', 'menunggu'))->count();
        $this->dipanggil = (clone $dayQuery)->where(fn($q) => $q->where('status', 'dipanggil'))->count();
        $this->selesai = (clone $dayQuery)->where(fn($q) => $q->where('status', 'selesai'))->count();
        $this->batal = (clone $dayQuery)->where(fn($q) => $q->where('status', 'batal'))->count();

        return view('livewire.modules.antrian.antrian-page');
    }
}
