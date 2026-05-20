<?php

namespace App\Modules\Master\Http\Livewire;

use App\Models\MstPasien;
use App\Traits\DynamicKodeGenerator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class PasienPage extends Component
{
    use WithPagination, DynamicKodeGenerator, \App\Traits\HasPatientHistory;

    public $pasienId;
    public $no_rm;
    public $nama_pasien;
    public $nik;
    public $jenis_kelamin;
    public $tempat_lahir;
    public $tanggal_lahir;
    public $alamat;
    public $no_telepon;
    public $agama;
    public $pekerjaan;
    public $no_penjamin;
    public $golongan_darah;
    public $alergi;
    public $status;
    
    public $satusehat_uuid;
    public $marital_status;
    public $kode_pos;
    public $provinsi_id;
    public $kabupaten_id;
    public $kecamatan_id;
    public $kelurahan_id;

    public $totalPasien = 0;
    public $pasienBaru = 0;
    public $takAktif = 0;
    public $selectedStatus = 'all';
    public $search = '';
    public $isEdit = false;
    public $kodeReadonly = false;



    protected $queryString = ['search', 'selectedStatus'];

    #[Computed]
    public function pasiens()
    {
        $query = MstPasien::withTrashed();

        if ($this->selectedStatus !== 'all') {
            $query->where('status', $this->selectedStatus);
        }

        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('no_rm', 'like', '%'.$this->search.'%')
                    ->orWhere('nama_pasien', 'like', '%'.$this->search.'%')
                    ->orWhere('nik', 'like', '%'.$this->search.'%')
                    ->orWhere('no_telepon', 'like', '%'.$this->search.'%');
            });
        }

        return $query->orderBy('no_rm', 'asc')->paginate(10);
    }

    #[Computed]
    public function provinsiOptions()
    {
        return \App\Models\MstWilayahProvinsi::orderBy('nama')->get();
    }

    #[Computed]
    public function kabupatenOptions()
    {
        return $this->provinsi_id 
            ? \App\Models\MstWilayahKabupaten::where('provinsi_kode', $this->provinsi_id)->orderBy('nama')->get() 
            : collect([]);
    }


    #[Computed]
    public function kecamatanOptions()
    {
        return $this->kabupaten_id 
            ? \App\Models\MstWilayahKecamatan::where('kabupaten_kode', $this->kabupaten_id)->orderBy('nama')->get() 
            : collect([]);
    }


    #[Computed]
    public function kelurahanOptions()
    {
        return $this->kecamatan_id 
            ? \App\Models\MstWilayahKelurahan::where('kecamatan_kode', $this->kecamatan_id)->orderBy('nama')->get() 
            : collect([]);
    }


    public function updatedProvinsiId()
    {
        $this->reset(['kabupaten_id', 'kecamatan_id', 'kelurahan_id']);
    }

    public function updatedKabupatenId()
    {
        $this->reset(['kecamatan_id', 'kelurahan_id']);
    }

    public function updatedKecamatanId()
    {
        $this->reset(['kelurahan_id']);
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
            'no_rm' => ['required', 'string', 'max:20', Rule::unique('mst_pasien', 'no_rm')->ignore($this->pasienId)],
            'nama_pasien' => 'required|string|max:100',
            'nik' => ['nullable', 'string', 'max:20', Rule::unique('mst_pasien', 'nik')->ignore($this->pasienId)],
            'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
            'tempat_lahir' => 'nullable|string',
            'tanggal_lahir' => 'nullable|date',
            'alamat' => 'nullable|string',
            'kode_pos' => 'nullable|string|max:10',
            'provinsi_id' => 'nullable|exists:mst_wilayah_provinsi,kode',
            'kabupaten_id' => 'nullable|exists:mst_wilayah_kabupaten,kode',
            'kecamatan_id' => 'nullable|exists:mst_wilayah_kecamatan,kode',
            'kelurahan_id' => 'nullable|exists:mst_wilayah_kelurahan,kode',
            'no_telepon' => 'nullable|string|max:20',
            'agama' => 'nullable|string|max:20',
            'pekerjaan' => 'nullable|string|max:50',
            'no_penjamin' => 'nullable|string|max:50',
            'marital_status' => 'nullable|string',
            'golongan_darah' => 'nullable|string|max:5',
            'alergi' => 'nullable|string',
        ];

    }

    public function resetForm()
    {
        $this->reset([
            'pasienId', 'no_rm', 'nama_pasien', 'nik', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 
            'alamat', 'kode_pos', 'provinsi_id', 'kabupaten_id', 'kecamatan_id', 'kelurahan_id', 
            'no_telepon', 'agama', 'pekerjaan', 'no_penjamin', 'marital_status', 'golongan_darah', 'alergi', 'satusehat_uuid', 'isEdit'
        ]);
        $this->status = 'Aktif';

        $this->resetErrorBag();
    }

    public function create()
    {
        $this->resetForm();
        $generated = $this->generateDynamicKode('mst_pasien', 'no_rm');
        if ($generated) {
            $this->no_rm = $generated;
            $this->kodeReadonly = true;
        } else {
            $this->kodeReadonly = false;
        }
        $this->dispatch('open-modal');
    }

    public function history($id)
    {
        $this->openRiwayatModal($id);
    }

    public function edit($id)
    {
        $this->resetForm();
        $pasien = MstPasien::withTrashed()->findOrFail($id);

        $this->pasienId = $pasien->id;
        $this->no_rm = $pasien->no_rm;
        $this->nama_pasien = $pasien->nama_pasien;
        $this->nik = $pasien->nik;
        $this->jenis_kelamin = $pasien->jenis_kelamin;
        $this->tempat_lahir = $pasien->tempat_lahir;
        $this->tanggal_lahir = $pasien->tanggal_lahir ? $pasien->tanggal_lahir->format('Y-m-d') : null;
        $this->alamat = $pasien->alamat;
        $this->no_telepon = $pasien->no_telepon;
        $this->agama = $pasien->agama;
        $this->pekerjaan = $pasien->pekerjaan;
        $this->no_penjamin = $pasien->no_penjamin;
        $this->marital_status = $pasien->marital_status;
        $this->golongan_darah = $pasien->golongan_darah;
        $this->alergi = $pasien->alergi;
        $this->status = $pasien->status;
        $this->satusehat_uuid = $pasien->satusehat_uuid;
        $this->kode_pos = $pasien->kode_pos;
        $this->provinsi_id = $pasien->provinsi_id;
        $this->kabupaten_id = $pasien->kabupaten_id;
        $this->kecamatan_id = $pasien->kecamatan_id;
        $this->kelurahan_id = $pasien->kelurahan_id;


        $this->isEdit = true;
        $this->dispatch('open-modal');
    }

    public function save()
    {
        try {
            $this->validate($this->rules());

            $pasien = $this->pasienId
                ? MstPasien::withTrashed()->findOrFail($this->pasienId)
                : new MstPasien;

            if (! $this->pasienId && empty($this->no_rm)) {
                $this->no_rm = $this->generateDynamicKode('mst_pasien', 'no_rm');
            }

            $pasien->fill([
                'no_rm' => $this->no_rm,
                'nama_pasien' => $this->nama_pasien,
                'nik' => $this->nik,
                'jenis_kelamin' => $this->jenis_kelamin,
                'tempat_lahir' => $this->tempat_lahir,
                'tanggal_lahir' => $this->tanggal_lahir,
                'alamat' => $this->alamat,
                'kode_pos' => $this->kode_pos,
                'provinsi_id' => $this->provinsi_id,
                'kabupaten_id' => $this->kabupaten_id,
                'kecamatan_id' => $this->kecamatan_id,
                'kelurahan_id' => $this->kelurahan_id,
                'no_telepon' => $this->no_telepon,
                'agama' => $this->agama,
                'pekerjaan' => $this->pekerjaan,
                'no_penjamin' => $this->no_penjamin,
                'marital_status' => $this->marital_status,
                'golongan_darah' => $this->golongan_darah,
                'alergi' => $this->alergi,
                'status' => $this->status ?? 'Aktif',
            ]);


            $pasien->save();

            if ($this->status === 'Aktif' && $pasien->trashed()) {
                $pasien->restore();
            } elseif ($this->status === 'Tidak Aktif' && ! $pasien->trashed()) {
                $pasien->delete();
            }

            $this->dispatch('close-modal');
            $this->dispatch('alert', ['type' => 'success', 'message' => $this->isEdit ? 'Data pasien berhasil diperbarui!' : 'Pasien baru berhasil ditambahkan!']);
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
        $pasien = MstPasien::withTrashed()->findOrFail($id);

        if ($pasien->status === 'Tidak Aktif') {
            $this->dispatch('alert', ['type' => 'info', 'message' => 'Data dengan status Tidak Aktif tidak dapat dihapus. Silakan kembalikan ke status Aktif terlebih dahulu.']);

            return;
        }

        $pasien->update(['status' => 'Tidak Aktif']);
        $pasien->delete();

        $this->dispatch('alert', ['type' => 'success', 'message' => 'Status pasien telah diubah menjadi Tidak Aktif!']);
    }

    public function syncSatuSehat($id)
    {
        $pasien = MstPasien::findOrFail($id);
        
        if (empty($pasien->nik)) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Gagal Sync: NIK Pasien kosong.']);
            return;
        }

        try {
            $service = new \App\Modules\Bridging\Services\SatuSehatService();
            
            // Step 1: Search SS
            $this->dispatch('alert', ['type' => 'info', 'message' => 'Sedang mencari data pasien di SatuSehat...']);
            $ssPatient = $service->searchPatient($pasien->nik, $pasien->nama_pasien);

            if ($ssPatient) {
                $uuid = $ssPatient['id'];
                $pasien->update(['satusehat_uuid' => $uuid]);
                $this->dispatch('alert', ['type' => 'success', 'message' => 'Pasien ditemukan di SatuSehat. UUID berhasil sinkron.']);
            } else {
                // Step 2: Create if not found
                $this->dispatch('alert', ['type' => 'info', 'message' => 'Pasien tidak ditemukan, mencoba mendaftarkan ke SatuSehat...']);
                $service->createPatient($pasien);
                $this->dispatch('alert', ['type' => 'success', 'message' => 'Pasien berhasil didaftarkan ke SatuSehat!']);
            }
        } catch (\Exception $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Sync Gagal: ' . $e->getMessage()]);
        }
    }


    public function render()
    {
        $this->totalPasien = MstPasien::withTrashed()->count();
        $this->pasienBaru = MstPasien::withTrashed()->where('created_at', '>=', now()->subDays(30))->count();
        $this->takAktif = MstPasien::withTrashed()->where('status', 'Tidak Aktif')->count();

        return view('livewire.modules.master.pasien-page');
    }
}
