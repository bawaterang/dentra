<?php

namespace App\Modules\Master\Http\Livewire;

use App\Models\MstDokter;
use App\Traits\DynamicKodeGenerator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class DokterPage extends Component
{
    use WithPagination, DynamicKodeGenerator;

    public $dokterId;

    public $kode_dokter;

    public $nama_dokter;

    public $nik;

    public $jenis_kelamin;

    public $tempat_lahir;

    public $tanggal_lahir;

    public $alamat;

    public $no_telepon;

    public $agama;

    public $spesialisasi;

    public $no_sip;

    public $no_str;

    public $status;
    public $color;
    public $user_id;

    // SatuSehat & BPJS
    public $practitioner_id;
    public $dokter_bpjs_id;

    // Search SS Practitioner
    public $searchPracQuery = '';
    public $foundPractitioners = [];

    // Search BPJS Dokter
    public $searchBpjsQuery = '';
    public $foundBpjsDokters = [];


    public $totalDokter = 0;
    public $totalSpesialis = 0;
    public $takAktif = 0;
    public $dokterCutiCount = 0;
    public $userOptions = [];

    public $selectedStatus = 'all';

    public $search = '';

    public $isEdit = false;

    public $kodeReadonly = false;

    protected $queryString = ['search', 'selectedStatus'];

    #[Computed]
    public function dokters()
    {
        $query = MstDokter::withTrashed();

        if ($this->selectedStatus !== 'all') {
            $query->where('status', $this->selectedStatus);
        }

        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('kode_dokter', 'like', '%'.$this->search.'%')
                    ->orWhere('nama_dokter', 'like', '%'.$this->search.'%')
                    ->orWhere('spesialisasi', 'like', '%'.$this->search.'%')
                    ->orWhere('no_sip', 'like', '%'.$this->search.'%');
            });
        }

        return $query->orderBy('kode_dokter', 'asc')->paginate(10);
    }

    public function setStatus($status)
    {
        $this->selectedStatus = $status;
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    protected function rules()
    {
        return [
            'kode_dokter' => ['required', 'string', 'max:20', Rule::unique('mst_dokter', 'kode_dokter')->ignore($this->dokterId)],
            'nama_dokter' => 'required|string|max:100',
            'nik' => ['nullable', 'string', 'max:20', Rule::unique('mst_dokter', 'nik')->ignore($this->dokterId)],
            'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
            'tempat_lahir' => 'nullable|string',
            'tanggal_lahir' => 'nullable|date',
            'alamat' => 'nullable|string',
            'no_telepon' => 'nullable|string|max:20',
            'agama' => 'nullable|string|max:20',
            'spesialisasi' => 'nullable|string|max:100',
            'no_sip' => 'nullable|string|max:50',
            'no_str' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'user_id' => ['nullable', 'exists:mst_user,id'],
            'practitioner_id' => 'nullable|string|max:50',
            'dokter_bpjs_id' => 'nullable|string|max:50',
        ];
    }


    public function resetForm()
    {
        $this->reset(['dokterId', 'kode_dokter', 'nama_dokter', 'nik', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'alamat', 'no_telepon', 'agama', 'spesialisasi', 'no_sip', 'no_str', 'isEdit', 'user_id', 'practitioner_id', 'dokter_bpjs_id']);
        $this->status = 'Aktif';
        $this->color = '#405189';
        $this->resetErrorBag();
    }


    public function create()
    {
        $this->resetForm();
        $generated = $this->generateDynamicKode('mst_dokter', 'kode_dokter');
        if ($generated) {
            $this->kode_dokter = $generated;
            $this->kodeReadonly = true;
        } else {
            $this->kodeReadonly = false;
        }
        $this->dispatch('open-modal');
    }

    public function history($id)
    {
        $this->dispatch('alert', ['type' => 'info', 'message' => 'Fitur riwayat dokter sedang dalam pengembangan.']);
    }

    public function edit($id)
    {
        $this->resetForm();
        $dokter = MstDokter::withTrashed()->findOrFail($id);

        $this->dokterId = $dokter->id;
        $this->kode_dokter = $dokter->kode_dokter;
        $this->nama_dokter = $dokter->nama_dokter;
        $this->nik = $dokter->nik;
        $this->jenis_kelamin = $dokter->jenis_kelamin;
        $this->tempat_lahir = $dokter->tempat_lahir;
        $this->tanggal_lahir = $dokter->tanggal_lahir instanceof \DateTimeInterface ? $dokter->tanggal_lahir->format('Y-m-d') : null;
        $this->alamat = $dokter->alamat;
        $this->no_telepon = $dokter->no_telepon;
        $this->agama = $dokter->agama;
        $this->spesialisasi = $dokter->spesialisasi;
        $this->no_sip = $dokter->no_sip;
        $this->no_str = $dokter->no_str;
        $this->status = $dokter->status;
        $this->color = $dokter->color ?? '#405189';
        $this->user_id = $dokter->user_id;
        $this->practitioner_id = $dokter->practitioner_id;
        $this->dokter_bpjs_id = $dokter->dokter_bpjs_id;

        $this->isEdit = true;
        $this->dispatch('open-modal');
    }


    public function save()
    {
        try {
            $this->validate($this->rules());

            $dokter = $this->dokterId
                ? MstDokter::withTrashed()->findOrFail($this->dokterId)
                : new MstDokter;

            if (! $this->dokterId && empty($this->kode_dokter)) {
                $this->kode_dokter = $this->generateDynamicKode('mst_dokter', 'kode_dokter');
            }

            $dokter->fill([
                'kode_dokter' => $this->kode_dokter,
                'nama_dokter' => $this->nama_dokter,
                'nik' => $this->nik,
                'jenis_kelamin' => $this->jenis_kelamin,
                'tempat_lahir' => $this->tempat_lahir,
                'tanggal_lahir' => $this->tanggal_lahir,
                'alamat' => $this->alamat,
                'no_telepon' => $this->no_telepon,
                'agama' => $this->agama,
                'spesialisasi' => $this->spesialisasi,
                'no_sip' => $this->no_sip,
                'no_str' => $this->no_str,
                'status' => $this->status ?? 'Aktif',
                'color' => $this->color,
                'user_id' => $this->user_id || $this->user_id == '0' ? $this->user_id : null,
                'practitioner_id' => $this->practitioner_id,
                'dokter_bpjs_id' => $this->dokter_bpjs_id,
            ]);


            $dokter->save();

            if ($this->status === 'Aktif' || $this->status === 'Cuti') {
                if ($dokter->trashed()) {
                    $dokter->restore();
                }
            } elseif ($this->status === 'Tidak Aktif' && ! $dokter->trashed()) {
                $dokter->delete();
            }

            $this->dispatch('close-modal');
            $this->dispatch('alert', ['type' => 'success', 'message' => $this->isEdit ? 'Data dokter berhasil diperbarui!' : 'Dokter baru berhasil ditambahkan!']);
            $this->resetForm();
        } catch (ValidationException $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Simpan Gagal: Data tidak valid.']);
            throw $e;
        } catch (\Exception $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Simpan Gagal: '.$e->getMessage()]);
        }
    }

    public function delete($id)
    {
        $dokter = MstDokter::withTrashed()->findOrFail($id);

        if ($dokter->status === 'Cuti' || $dokter->status === 'Tidak Aktif') {
            $this->dispatch('alert', ['type' => 'info', 'message' => 'Data dengan status '.$dokter->status.' tidak dapat dihapus. Silakan kembalikan ke status Aktif terlebih dahulu.']);

            return;
        }

        $dokter->update(['status' => 'Tidak Aktif']);
        $dokter->delete();

        $this->dispatch('alert', ['type' => 'success', 'message' => 'Status dokter telah diubah menjadi Tidak Aktif!']);
    }

    public function forceDelete($id)
    {
        $dokter = MstDokter::withTrashed()->findOrFail($id);
        $dokter->forceDelete();

        $this->dispatch('alert', ['type' => 'success', 'message' => 'Data dokter berhasil dihapus secara permanen dari database!']);
    }

    public function searchSatuSehatPrac()
    {
        if (empty($this->searchPracQuery)) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Masukkan NIK atau Nama Dokter!']);
            return;
        }

        try {
            $service = new \App\Modules\Bridging\Services\SatuSehatService();
            if (is_numeric($this->searchPracQuery) && strlen($this->searchPracQuery) === 16) {
                $results = $service->searchPractitionerByNik($this->searchPracQuery);
            } else {
                // Search by detail requires Gender and BirthDate
                if (!$this->jenis_kelamin || !$this->tanggal_lahir) {
                    $this->dispatch('alert', ['type' => 'warning', 'message' => 'Lengkapi Jenis Kelamin dan Tanggal Lahir untuk pencarian berdasar Nama.']);
                    return;
                }
                $gender = $this->jenis_kelamin === 'Laki-laki' ? 'male' : 'female';
                $results = $service->searchPractitionerByDetail($this->searchPracQuery, $gender, $this->tanggal_lahir);
            }

            $this->foundPractitioners = $results ?: [];

            if (empty($this->foundPractitioners)) {
                $this->dispatch('alert', ['type' => 'warning', 'message' => 'Dokter tidak ditemukan di SatuSehat.']);
            }
        } catch (\Exception $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Gagal mencari Dokter: ' . $e->getMessage()]);
        }
    }

    public function selectPrac($id)
    {
        $this->practitioner_id = $id;
        $this->dispatch('close-search-prac-modal');
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Practitioner ID dipilih!']);
    }

    public function searchBpjsPrac()
    {
        try {
            $service = new \App\Modules\Bridging\Services\BpjsPcareService();
            $response = $service->getDokter(0, 100);
            
            if ($response['success']) {
                $data = $response['data'] ?? [];
                $doctors = $data['list'] ?? $data;
                
                // Filter if query is provided
                if (!empty($this->searchBpjsQuery)) {
                    $doctors = array_filter($doctors, function($d) {
                        return str_contains(strtolower($d['nmDokter'] ?? ''), strtolower($this->searchBpjsQuery)) ||
                               str_contains(strtolower($d['kdDokter'] ?? ''), strtolower($this->searchBpjsQuery));
                    });
                }
                
                $this->foundBpjsDokters = $doctors;
                $this->dispatch('open-search-bpjs-modal');
            } else {
                $msg = $response['metadata']['message'] ?? 'Gagal mengambil data dari BPJS.';
                $this->dispatch('alert', ['type' => 'error', 'message' => $msg]);
            }
        } catch (\Exception $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Gagal mencari Dokter BPJS: ' . $e->getMessage()]);
        }
    }

    public function selectBpjsDokter($id)
    {
        $this->dokter_bpjs_id = $id;
        $this->dispatch('close-search-bpjs-modal');
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Kode Dokter BPJS dipilih!']);
    }


    public function render()
    {
        $this->totalDokter = MstDokter::withTrashed()->count();
        $this->totalSpesialis = MstDokter::withTrashed()->whereNotNull('spesialisasi')->where('spesialisasi', '!=', '')->count();
        $this->takAktif = MstDokter::withTrashed()->where('status', 'Tidak Aktif')->count();
        $this->dokterCutiCount = MstDokter::withTrashed()->where('status', 'Cuti')->count();

        // Fetch active users for mapping
        $users = \App\Models\User::where('is_active', true)->orderBy('full_name')->get();
        $this->userOptions = $users->map(fn($u) => [
            'value' => $u->id, 
            'label' => $u->full_name . ' (' . $u->username . ')', 
            'icon' => 'ri-user-settings-line ' . ($u->role === 'dokter' ? 'text-primary' : 'text-gray-400')
        ])->toArray();

        return view('livewire.modules.master.dokter-page');
    }
}
