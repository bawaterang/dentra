<?php

namespace App\Modules\Screening\Http\Livewire;

use Livewire\Component;
use App\Models\TrxPendaftaran;
use Carbon\Carbon;
use Livewire\WithPagination;
use App\Traits\HasAccessControl;
use Livewire\Attributes\Computed;

class ScreeningPage extends Component
{
    use WithPagination, HasAccessControl;

    public $selectedDate;
    public $selectedTab = 'belum'; // belum / sudah
    public $totalBelum = 0;
    public $totalSudah = 0;
    public $search = '';

    // Edit Modal Properties
    public $showEditModal = false;
    public $editPendaftaranId;
    public $pertanyaanList = [];
    public $jawaban = [];
    public $keterangan = [];
    public $editPasienName = '', $editNoRm = '', $editKunjungan = '', $editPoliName = '', $editDokterName = '';

    public function mount()
    {
        $this->authorizeAccess('/admisi/screening-pasien');
        $this->selectedDate = now()->format('Y-m-d');
        $this->pertanyaanList = \App\Models\MstSurvei::where('status', 'Aktif')->where('jenis_survei', 'screening')->get();
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

    public function setTab($tab)
    {
        $this->selectedTab = $tab;
        $this->resetPage();
    }

    public function editScreening($id)
    {
        $p = TrxPendaftaran::with(['pasien', 'poli', 'dokter'])->findOrFail($id);
        $this->editPendaftaranId = $p->id;
        $this->editPasienName = $p->pasien->nama_pasien ?? '-';
        $this->editNoRm = $p->pasien->no_rm ?? '-';
        $this->editKunjungan = $p->nomor_kunjungan;
        $this->editPoliName = $p->poli->nama_poli ?? '-';
        $this->editDokterName = $p->dokter->nama_dokter ?? '-';

        $existing = \App\Models\TrxScreening::where('pendaftaran_id', $id)->get();
        if ($existing->count() > 0) {
            foreach ($existing as $scr) {
                $this->jawaban[$scr->survei_id] = $scr->jawaban;
                $this->keterangan[$scr->survei_id] = $scr->keterangan;
            }
        } else {
            foreach ($this->pertanyaanList as $p_survei) {
                $this->jawaban[$p_survei->id] = 'tidak';
                $this->keterangan[$p_survei->id] = '';
            }
        }

        $this->showEditModal = true;
        $this->dispatch('refresh-table');
    }

    public function updateScreening()
    {
        $p = TrxPendaftaran::findOrFail($this->editPendaftaranId);
        foreach ($this->pertanyaanList as $q) {
            \App\Models\TrxScreening::updateOrCreate(
                ['pendaftaran_id' => $this->editPendaftaranId, 'survei_id' => $q->id],
                [
                    'pasien_id' => $p->pasien_id,
                    'jawaban' => $this->jawaban[$q->id] ?? 'tidak',
                    'keterangan' => $this->keterangan[$q->id] ?? null,
                ]
            );
        }

        $this->showEditModal = false;
        $this->dispatch('refresh-table');
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Data screening berhasil diperbarui.']);
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->dispatch('refresh-table');
    }

    #[Computed]
    public function pendaftaranList()
    {
        $query = TrxPendaftaran::with(['pasien', 'poli', 'dokter', 'screenings'])
            ->whereDate('created_at', $this->selectedDate);

        if ($this->selectedTab === 'belum') {
            $query->whereIn('status', ['terdaftar', 'menunggu_screening']);
        } else {
            $query->where('status', 'selesai');
        }

        // Security Check: Poli Isolation (Skip for Admin)
        $user = auth()->user();
        $userRoleNames = $user->roles->pluck('nama_role')->toArray();
        $isAdmin = in_array('Administrator', $userRoleNames) || in_array('Admin', $userRoleNames);

        if (!$isAdmin) {
            $userPoliIds = $user->polis->pluck('id')->toArray();
            $query->whereIn('poli_id', $userPoliIds);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('nomor_kunjungan', 'like', '%' . $this->search . '%')
                    ->orWhereHas('pasien', function ($qp) {
                        $qp->where('nama_pasien', 'like', '%' . $this->search . '%')
                            ->orWhere('no_rm', 'like', '%' . $this->search . '%');
                    });
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate(25);
    }

    public function render()
    {
        $this->totalBelum = TrxPendaftaran::whereDate('created_at', $this->selectedDate)->whereIn('status', ['terdaftar', 'menunggu_screening'])->count();
        $this->totalSudah = TrxPendaftaran::whereDate('created_at', $this->selectedDate)->where('status', 'selesai')->count();

        return view('livewire.modules.screening.screening-page');
    }
}
