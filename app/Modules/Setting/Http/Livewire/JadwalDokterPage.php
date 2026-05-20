<?php

namespace App\Modules\Setting\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use App\Models\MstJadwalDokter;
use App\Models\MstDokter;
use Carbon\Carbon;


class JadwalDokterPage extends Component
{
    use WithPagination;

    // Form Properties

    public $jadwalId;
    public $kode_dokter, $hari, $jam_mulai, $jam_selesai, $status_kehadiran = 'Hadir';
    public $is_active = true;
    public $isEdit = false;

    public $totalAktif = 0;
    public $totalLiburCuti = 0;
    public $totalJadwal = 0;
    public $search = '';
    public $selectedHari = 'all';

    protected $queryString = ['search', 'selectedHari'];

    #[Computed]
    public function jadwals()
    {
        $query = MstJadwalDokter::with('dokter');

        if ($this->selectedHari !== 'all') {
            $query->where('hari', $this->selectedHari);
        }

        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->whereHas('dokter', function ($dq) {
                    $dq->where('nama_dokter', 'like', '%'.$this->search.'%')
                        ->orWhere('spesialisasi', 'like', '%'.$this->search.'%');
                })->orWhere('hari', 'like', '%'.$this->search.'%');
            });
        }

        return $query->orderByRaw("FIELD(hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu')")->paginate(10);
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }


    public $hariOptions = [];
    public $statusOptions = [];
    public $dokterOptions = [];
    public $dokterList = [];

    public function setHari($hari)
    {
        $this->selectedHari = $hari;
        $this->dispatch('refresh-table');
    }

    protected function rules()
    {
        return [
            'kode_dokter' => 'required|exists:mst_dokter,kode_dokter',
            'hari' => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
            'jam_mulai' => 'nullable|date_format:H:i',
            'jam_selesai' => 'nullable|date_format:H:i',
            'status_kehadiran' => 'required|in:Hadir,Libur,Cuti',
            'is_active' => 'boolean',
        ];
    }

    public function mount()
    {
        $this->dokterList = MstDokter::where('status', 'Aktif')->get()->toArray();
    }

    public function resetForm()
    {
        $this->reset(['jadwalId', 'kode_dokter', 'hari', 'jam_mulai', 'jam_selesai', 'isEdit']);
        $this->status_kehadiran = 'Hadir';
        $this->is_active = true;
        $this->resetErrorBag();
    }

    public function create()
    {
        $this->resetForm();
        $this->dispatch('open-jadwal-modal');
    }


    public function edit($id)
    {
        $this->resetForm();
        
        $jadwal = MstJadwalDokter::findOrFail($id);
        
        $this->jadwalId = $jadwal->id;
        $this->kode_dokter = $jadwal->kode_dokter;
        $this->hari = $jadwal->hari;
        $this->jam_mulai = $jadwal->jam_mulai ? \Carbon\Carbon::parse($jadwal->jam_mulai)->format('H:i') : null;
        $this->jam_selesai = $jadwal->jam_selesai ? \Carbon\Carbon::parse($jadwal->jam_selesai)->format('H:i') : null;
        $this->status_kehadiran = $jadwal->status_kehadiran;
        $this->is_active = $jadwal->is_active;
        
        $this->isEdit = true;
        $this->dispatch('open-jadwal-modal');
        $this->dispatch('refresh-table');
    }

    public function save()
    {
        try {
            $this->validate();

            $jadwal = $this->jadwalId 
                ? MstJadwalDokter::findOrFail($this->jadwalId) 
                : new MstJadwalDokter();

            $jadwal->fill([
                'kode_dokter' => $this->kode_dokter,
                'hari' => $this->hari,
                'jam_mulai' => $this->jam_mulai ?: null,
                'jam_selesai' => $this->jam_selesai ?: null,
                'status_kehadiran' => $this->status_kehadiran,
                'is_active' => $this->is_active,
            ]);

            $jadwal->save();

            $this->dispatch('close-jadwal-modal');
            $this->dispatch('alert', ['type' => 'success', 'message' => $this->isEdit ? 'Jadwal dokter berhasil diperbarui!' : 'Jadwal dokter baru berhasil ditambahkan!']);
            $this->resetForm();

        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Simpan Gagal: Data yang Anda masukkan tidak valid. Silakan periksa kembali kolom yang bertanda merah.']);
            throw $e;
        } catch (\Exception $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Simpan Gagal: ' . $e->getMessage()]);
        }
    }

    public function delete($id)
    {
        $jadwal = MstJadwalDokter::findOrFail($id);
        $jadwal->delete();
        
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Jadwal dokter berhasil dihapus!']);
    }


    public function render()
    {
        $this->totalAktif = MstJadwalDokter::where('is_active', true)->where('status_kehadiran', 'Hadir')->count();
        $this->totalLiburCuti = MstJadwalDokter::whereIn('status_kehadiran', ['Libur', 'Cuti'])->count();
        $this->totalJadwal = MstJadwalDokter::count();

        $this->hariOptions = collect(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'])->map(fn($h) => [
            'value' => $h,
            'label' => $h,
            'icon' => 'ri-calendar-event-line'
        ])->toArray();

        $this->statusOptions = [
            ['value' => 'Hadir', 'label' => 'Hadir Praktik', 'icon' => 'ri-checkbox-circle-line'],
            ['value' => 'Libur', 'label' => 'Libur Reguler', 'icon' => 'ri-calendar-close-line'],
            ['value' => 'Cuti', 'label' => 'Sedang Cuti/Izin', 'icon' => 'ri-user-unfollow-line'],
        ];

        $this->dokterList = MstDokter::where('status', 'Aktif')->get()->toArray();
        $this->dokterOptions = collect($this->dokterList)->map(fn($d) => [
            'value' => $d['kode_dokter'],
            'label' => $d['nama_dokter'] . ' (' . ($d['spesialisasi'] ?? 'Umum') . ')',
            'icon' => 'ri-user-star-line'
        ])->toArray();

        return view('livewire.modules.setting.jadwal-dokter-page');
    }
}
