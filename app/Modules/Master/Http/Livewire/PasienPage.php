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

        return <<<'HTML'
        <div x-data="{ showModal: false, init(){this.$watch('showModal',v=>{if(v){$nextTick(()=>{this.$refs.firstInput&&this.$refs.firstInput.focus()})}})} }" @open-modal.window="showModal=true" @close-modal.window="showModal=false" x-init="init()">
            <style>
                .glass-header {
                    background: rgba(255, 255, 255, 0.8) !important;
                    backdrop-filter: blur(8px);
                    -webkit-backdrop-filter: blur(8px);
                }
                .rm-chip {
                    font-family: 'JetBrains Mono', 'Fira Code', monospace;
                    background: #f1f5f9;
                    color: #475569;
                    padding: 4px 8px;
                    border-radius: 6px;
                    font-size: 0.75rem;
                    border: 1px solid #e2e8f0;
                }
                .pasien-row:hover {
                    background-color: #d8dce1ff !important;
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
                .gender-pill {
                    padding: 2px 8px;
                    border-radius: 9999px;
                    font-size: 10px;
                    font-weight: 700;
                    text-transform: uppercase;
                    letter-spacing: 0.025em;
                }
                .status-badge-modern {
                    display: inline-flex;
                    align-items: center;
                    gap: 0.375rem;
                    padding: 0.25rem 0.625rem;
                    border-radius: 0.5rem;
                    font-size: 0.75rem;
                    font-weight: 600;
                }
                .search-focus-glow:focus {
                    box-shadow: 0 0 0 4px rgba(64, 81, 137, 0.15);
                    border-color: #f6f7fbff;
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
                    border: 1px solid #767070ff !important;
                    font-size: 13px !important;
                    font-weight: 700 !important;
                    transition: all 0.2s ease-in-out !important;
                    background-color: #ffffff !important;
                    color: #475569 !important;
                    text-decoration: none !important;
                }
                .pagination-custom nav a:hover {
                    background-color: #f1f5f9 !important;
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
            </style>

            <div class="page-header">
                <div class="page-header-title">
                    <div class="page-header-icon bg-gradient-to-br from-[#405189] to-[#2a3a6a] text-white shadow-lg animate-pulse" style="animation-duration: 3s;">
                        <i class="ri-user-heart-line"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold tracking-tight text-[#2c3e50]">Master Data Pasien</h1>
                        <p class="text-xs text-[#878a99] font-medium mt-0.5">Kelola data rekam medis dan informasi pasien.</p>
                    </div>
                </div>
                <div class="page-header-breadcrumb">
                    <a href="/dashboard" wire:navigate class="hover:text-[#405189] transition-colors"><i class="ri-home-4-line"></i></a>
                    <span class="sep text-gray-300">/</span>
                    <span class="text-gray-400 font-medium">Master</span>
                    <span class="sep text-gray-300">/</span>
                    <span class="text-[#405189] font-bold">Pasien</span>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 mb-8">
                <div class="group relative overflow-hidden bg-white rounded-2xl p-5 shadow-sm hover:shadow-xl transition-all duration-500 border-l-4 border-[#405189]">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-[#405189] group-hover:bg-indigo-500 group-hover:text-white transition-all duration-300 shadow-inner">
                            <i class="ri-user-line text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-[#878a99] font-bold text-[10px] uppercase tracking-[0.1em]">Total Pasien</p>
                            <h4 class="text-2xl font-black text-[#2c3e50] leading-none mt-1">{{ number_format($totalPasien) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="group relative overflow-hidden bg-white rounded-2xl p-5 shadow-sm hover:shadow-xl transition-all duration-500 border-l-4 border-[#0ab39c]">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-[#0ab39c] group-hover:bg-emerald-500 group-hover:text-white transition-all duration-300 shadow-inner">
                            <i class="ri-user-add-line text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-[#878a99] font-bold text-[10px] uppercase tracking-[0.1em]">Pasien Baru (30 Hari)</p>
                            <h4 class="text-2xl font-black text-[#2c3e50] leading-none mt-1 text-[#0ab39c]">{{ number_format($pasienBaru) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="group relative overflow-hidden bg-white rounded-2xl p-5 shadow-sm hover:shadow-xl transition-all duration-500 border-l-4 border-[#f06548]">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-rose-50 text-[#f06548] group-hover:bg-rose-500 group-hover:text-white transition-all duration-300 shadow-inner">
                            <i class="ri-user-unfollow-line text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-[#878a99] font-bold text-[10px] uppercase tracking-[0.1em]">Tidak Aktif</p>
                            <h4 class="text-2xl font-black text-[#2c3e50] leading-none mt-1 text-[#f06548]">{{ number_format($takAktif) }}</h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden mb-12">
                <div class="p-6 border-b border-gray-50 flex flex-col lg:flex-row justify-between items-center gap-6 glass-header sticky top-0 z-20">
                    <div class="flex overflow-x-auto scrollbar-hide -mx-2 px-2 lg:mx-0 lg:px-0">
                        <ul class="nav-pills-custom">
                            <li class="nav-item">
                                <a class="nav-link {{ $selectedStatus === 'all' ? 'active active-pill-primary' : '' }}" 
                                   wire:click="setStatus('all')" role="button">
                                    <i class="ri-layout-grid-line"></i><span>Semua Data</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $selectedStatus === 'Aktif' ? 'active active-pill-success' : '' }}" 
                                   wire:click="setStatus('Aktif')" role="button">
                                    <i class="ri-user-follow-line"></i><span>Aktif</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $selectedStatus === 'Tidak Aktif' ? 'active active-pill-danger' : '' }}" 
                                   wire:click="setStatus('Tidak Aktif')" role="button">
                                    <i class="ri-user-unfollow-line"></i><span>Tidak Aktif</span>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div class="flex flex-wrap items-center gap-4 w-full lg:w-auto">
                        <div class="relative flex-grow min-w-[280px]">
                            <i class="ri-search-2-line absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-lg group-focus-within:text-[#405189]"></i>
                            <input type="text" wire:model.live.debounce.300ms="search" 
                                   class="w-full bg-gray-50/50 border border-gray-200 rounded-2xl py-2.5 pl-12 pr-4 text-sm font-medium outline-none transition-all search-focus-glow placeholder:text-gray-300" 
                                   placeholder="Cari RM, nama, atau NIK pasien...">
                        </div>
                        
                        <div class="grid grid-cols-2 gap-3 w-full lg:flex lg:w-auto lg:items-center lg:gap-1.5 lg:p-1 lg:rounded-lg lg:border lg:border-[#e2e8f0] lg:bg-white">
                            <a href="{{ route('master.pasien.print', ['status' => $selectedStatus]) }}" target="_blank" 
                               class="flex flex-col lg:flex-row items-center justify-center gap-2 p-4 lg:p-0 lg:h-8 lg:w-8 rounded-2xl lg:rounded-md bg-white border border-gray-100 lg:border-none shadow-sm lg:shadow-none hover:bg-indigo-50 transition-all group/print" title="Cetak PDF">
                                <i class="ri-printer-line text-2xl lg:text-lg text-indigo-500 group-hover/print:scale-110 transition-transform"></i>
                                <span class="lg:hidden text-[10px] font-black text-gray-400 uppercase tracking-widest">Cetak PDF</span>
                            </a>
                            <div class="hidden lg:block w-[1px] h-4 bg-[#e2e8f0]"></div>
                            <a href="{{ route('master.pasien.export', ['status' => $selectedStatus]) }}" target="_blank" 
                               class="flex flex-col lg:flex-row items-center justify-center gap-2 p-4 lg:p-0 lg:h-8 lg:w-8 rounded-2xl lg:rounded-md bg-white border border-gray-100 lg:border-none shadow-sm lg:shadow-none hover:bg-emerald-50 transition-all group/export" title="Unduh Excel">
                                <i class="ri-file-excel-2-line text-2xl lg:text-lg text-emerald-500 group-hover/export:scale-110 transition-transform"></i>
                                <span class="lg:hidden text-[10px] font-black text-gray-400 uppercase tracking-widest">Ekspor Excel</span>
                            </a>
                        </div>

                        <button @click="$wire.create()" class="btn btn-primary h-10 px-6 shadow-sm flex items-center justify-center gap-2 transition-all hover:translate-y-[-2px] hover:shadow-lg active:scale-95 w-full lg:w-auto">
                            <i class="ri-add-line text-xl"></i>
                            <span class="font-bold text-xs uppercase tracking-wider">Tambah Pasien</span>
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50">
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">No. RM</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Nama Pasien</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">NIK</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Jenis Kelamin</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">No. Telepon</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Status</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($this->pasiens as $pasien)
                            <tr wire:key="pasien-{{ $pasien->id }}" class="pasien-row transition-all duration-200">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="rm-chip shadow-sm">{{ $pasien->no_rm }}</span>
                                </td>
                                <td class="px-6 py-4 min-w-[250px]">
                                    <div class="group relative">
                                        <div class="font-bold text-[#2c3e50] text-sm group-hover:text-[#405189] transition-colors line-clamp-1">{{ $pasien->nama_pasien }}</div>
                                        <div class="text-[11px] text-gray-400 font-medium italic mt-1 leading-relaxed line-clamp-1 group-hover:line-clamp-none transition-all duration-300">
                                            {{ $pasien->alergi ?: 'Tidak ada catatan alergi.' }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-600">{{ $pasien->nik ?: '-' }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $genderColor = $pasien->jenis_kelamin === 'Laki-laki' ? 'bg-blue-50 text-blue-600' : 'bg-pink-50 text-pink-600';
                                    @endphp
                                    <span class="gender-pill {{ $genderColor }}">
                                        {{ $pasien->jenis_kelamin ?: '-' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-600">{{ $pasien->no_telepon ?: '-' }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($pasien->status == 'Aktif')
                                    <span class="status-badge-modern bg-emerald-50 text-emerald-600 border border-emerald-100">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                                        Aktif
                                    </span>
                                    @else
                                    <span class="status-badge-modern bg-rose-50 text-rose-600 border border-rose-100">
                                        <span class="h-1.5 w-1.5 rounded-full bg-rose-400"></span>
                                        Non-Aktif
                                    </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        @if(empty($pasien->satusehat_uuid))
                                        <button wire:click="syncSatuSehat({{ $pasien->id }})" class="action-btn-soft bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white shadow-sm" title="Sync SatuSehat">
                                            <i class="ri-cloud-line text-sm"></i>
                                        </button>
                                        @else
                                        <button class="action-btn-soft bg-emerald-50 text-emerald-600 cursor-default shadow-sm" title="Synced with SatuSehat">
                                            <i class="ri-checkbox-circle-line text-sm"></i>
                                        </button>
                                        @endif
                                        <button wire:click="history({{ $pasien->id }})" class="action-btn-soft bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white shadow-sm" title="Riwayat">
                                            <i class="ri-history-line text-sm"></i>
                                        </button>
                                        <button wire:click="edit({{ $pasien->id }})" class="action-btn-soft bg-indigo-50 text-[#405189] hover:bg-[#405189] hover:text-white shadow-sm" title="Edit Data">
                                            <i class="ri-pencil-fill text-sm"></i>
                                        </button>
                                        <button @click="if('{{ $pasien->status }}'==='Tidak Aktif'){Swal.fire({title:'Informasi',text:'Data yang tidak aktif tidak dapat dihapus lagi.',icon:'info',confirmButtonColor:'#405189'})}else{Swal.fire({title:'Konfirmasi Nonaktif',text:'Apakah Anda yakin ingin menonaktifkan pasien {{ $pasien->nama_pasien }}?',icon:'warning',showCancelButton:true,confirmButtonColor:'#f06548',cancelButtonColor:'#6c757d',confirmButtonText:'Ya, Nonaktifkan',cancelButtonText:'Batal',reverseButtons:true}).then((r)=>{if(r.isConfirmed){$wire.delete({{ $pasien->id }})}})}" 
                                                class="action-btn-soft bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white shadow-sm" title="Hapus/Nonaktif">
                                            <i class="ri-delete-bin-line text-sm"></i>
                                        </button>
                                    </div>

                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="py-20 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-32 h-32 bg-gray-50 rounded-full flex items-center justify-center mb-6 animate-bounce" style="animation-duration: 4s;">
                                            <i class="ri-file-search-line text-6xl text-gray-200"></i>
                                        </div>
                                        <p class="text-xl font-black text-gray-400">Data Tidak Ditemukan</p>
                                        <p class="text-xs text-gray-300 mt-1 uppercase tracking-widest font-bold">Cobalah menyesuaikan filter atau kata kunci pencarian Anda</p>
                                        <button @click="$wire.set('search', '')" class="mt-6 text-[#405189] font-bold text-xs uppercase tracking-wider hover:underline">
                                            <i class="ri-refresh-line"></i> Reset Pencarian
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($this->pasiens->hasPages())
                <div class="px-6 py-5 sm:px-8 sm:py-6 bg-gray-50/50 border-t border-gray-100 pagination-custom">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-5">
                        <div class="text-[11px] font-bold text-[#878a99] tracking-tight text-center sm:text-left">
                            <i class="ri-list-check-2 text-[#405189] mr-1 hidden sm:inline"></i>
                            <span class="hidden sm:inline">Menampilkan</span> 
                            <span class="text-[#405189] font-black">{{ $this->pasiens->firstItem() }} - {{ $this->pasiens->lastItem() }}</span> 
                            dari <span class="text-[#405189] font-black">{{ number_format($this->pasiens->total()) }}</span> 
                            <span class="hidden sm:inline">pasien terdaftar</span>
                            <span class="sm:hidden">total data</span>
                        </div>
                        {{ $this->pasiens->links() }}
                    </div>
                </div>
                @endif
            </div>

            <!-- Enhanced Modal Design -->
            <div x-show="showModal" class="fixed inset-0 z-[1050] flex items-center justify-center p-4" x-transition.opacity style="display: none;">
                <div class="absolute inset-0 bg-[#0a192f]/60 backdrop-blur-md"></div>
                <div x-show="showModal" x-transition.scale.95 
                     class="relative w-full max-w-xl bg-white rounded-2xl sm:rounded-3xl shadow-2xl overflow-hidden border border-white/20 animate-in fade-in zoom-in duration-300 mx-2 sm:mx-0">
                    
                    <div class="px-5 py-4 sm:px-8 sm:py-6 bg-gradient-to-r from-gray-50 to-white border-b border-gray-100 flex items-center justify-between">
                        <div class="flex items-center gap-2 sm:gap-3">
                            <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-indigo-50 text-[#405189] flex items-center justify-center shadow-inner">
                                <i class="ri-user-heart-line text-lg sm:text-xl"></i>
                            </div>
                            <div>
                                <h5 class="text-sm sm:text-base font-black text-[#2c3e50] tracking-tight">{{ $isEdit ? 'Update Data Pasien' : 'Pasien Baru' }}</h5>
                                <p class="text-[9px] sm:text-[10px] text-gray-400 font-bold uppercase tracking-widest hidden sm:block">Lengkapi informasi pasien di bawah</p>
                            </div>
                        </div>
                        <button @click="showModal = false" class="w-8 h-8 rounded-full flex items-center justify-center text-gray-400 hover:bg-gray-100 transition-all"><i class="ri-close-line text-lg"></i></button>
                    </div>

                    <div class="px-5 py-6 sm:px-8 sm:py-8 max-h-[70vh] overflow-y-auto scrollbar-hide">
                        <form wire:submit.prevent="save" class="space-y-5 sm:space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 sm:gap-6">
                                <div class="space-y-1.5">
                                    <label class="text-[9px] sm:text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">Nomor RM <span class="text-rose-500">*</span></label>
                                    <input type="text" wire:model="no_rm" x-ref="firstInput" 
                                           class="w-full bg-gray-50 border border-gray-100 rounded-xl sm:rounded-2xl py-2.5 sm:py-3 px-4 text-sm font-black text-[#405189] uppercase tracking-wider focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-[#405189] transition-all outline-none @error('no_rm') border-rose-300 bg-rose-50/30 @enderror {{ ($isEdit || $kodeReadonly) ? 'bg-gray-100 cursor-not-allowed' : '' }}" 
                                           placeholder="P00001" {{ ($isEdit || $kodeReadonly) ? 'readonly' : '' }}>
                                    @error('no_rm') <span class="text-[10px] text-rose-500 font-bold px-1">{{ $message }}</span> @enderror
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[9px] sm:text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">NIK</label>
                                    <input type="text" wire:model="nik" 
                                           class="w-full bg-gray-50 border border-gray-100 rounded-xl sm:rounded-2xl py-2.5 sm:py-3 px-4 text-sm font-bold text-[#2c3e50] focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-[#405189] transition-all outline-none @error('nik') border-rose-300 bg-rose-50/30 @enderror" 
                                           placeholder="16 Digit Nomor KTP">
                                    @error('nik') <span class="text-[10px] text-rose-500 font-bold px-1">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <label class="text-[9px] sm:text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">Nama Lengkap <span class="text-rose-500">*</span></label>
                                <input type="text" wire:model="nama_pasien" 
                                       class="w-full bg-gray-50 border border-gray-100 rounded-xl sm:rounded-2xl py-2.5 sm:py-3 px-4 text-sm font-bold text-[#2c3e50] focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-[#405189] transition-all outline-none @error('nama_pasien') border-rose-300 bg-rose-50/30 @enderror" 
                                       placeholder="Contoh: Budi Santoso">
                                @error('nama_pasien') <span class="text-[10px] text-rose-500 font-bold px-1">{{ $message }}</span> @enderror
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 sm:gap-6">
                                <div class="space-y-1.5">
                                    <label class="text-[9px] sm:text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">Jenis Kelamin <span class="text-rose-500">*</span></label>
                                    <x-custom-dropdown 
                                        model="jenis_kelamin" 
                                        :options="[
                                            ['value' => 'Laki-laki', 'label' => 'Laki-laki', 'icon' => 'ri-men-line text-blue-500'],
                                            ['value' => 'Perempuan', 'label' => 'Perempuan', 'icon' => 'ri-women-line text-pink-500']
                                        ]"
                                        placeholder="Pilih Jenis Kelamin"
                                    />
                                    @error('jenis_kelamin') <span class="text-[10px] text-rose-500 font-bold px-1">{{ $message }}</span> @enderror
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[9px] sm:text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">Gol. Darah</label>
                                    <x-custom-dropdown 
                                        model="golongan_darah" 
                                        :options="[
                                            ['value' => 'A', 'label' => 'A'],
                                            ['value' => 'B', 'label' => 'B'],
                                            ['value' => 'AB', 'label' => 'AB'],
                                            ['value' => 'O', 'label' => 'O']
                                        ]"
                                        placeholder="Golongan Darah"
                                        icon="ri-drop-line"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 sm:gap-6">
                                <div class="space-y-1.5">
                                    <label class="text-[9px] sm:text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">Tempat Lahir</label>
                                    <input type="text" wire:model="tempat_lahir" 
                                           class="w-full bg-gray-50 border border-gray-100 rounded-xl sm:rounded-2xl py-2.5 sm:py-3 px-4 text-sm font-bold text-[#2c3e50] focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-[#405189] transition-all outline-none @error('tempat_lahir') border-rose-300 bg-rose-50/30 @enderror" 
                                           placeholder="Kota Lahir">
                                    @error('tempat_lahir') <span class="text-[10px] text-rose-500 font-bold px-1">{{ $message }}</span> @enderror
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[9px] sm:text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">Tgl. Lahir</label>
                                    <input type="date" wire:model="tanggal_lahir" 
                                           class="w-full bg-gray-50 border border-gray-100 rounded-xl sm:rounded-2xl py-2.5 sm:py-3 px-4 text-sm font-bold text-[#2c3e50] focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-[#405189] transition-all outline-none @error('tanggal_lahir') border-rose-300 bg-rose-50/30 @enderror">
                                    @error('tanggal_lahir') <span class="text-[10px] text-rose-500 font-bold px-1">{{ $message }}</span> @enderror
                                </div>
                            </div>

                             <div class="grid grid-cols-1 md:grid-cols-2 gap-5 sm:gap-6">
                                <div class="space-y-1.5">
                                    <label class="text-[9px] sm:text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">No. Telepon</label>
                                    <input type="text" wire:model="no_telepon" 
                                           class="w-full bg-gray-50 border border-gray-100 rounded-xl sm:rounded-2xl py-2.5 sm:py-3 px-4 text-sm font-bold text-[#2c3e50] focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-[#405189] transition-all outline-none @error('no_telepon') border-rose-300 bg-rose-50/30 @enderror" 
                                           placeholder="08xxxx">
                                    @error('no_telepon') <span class="text-[10px] text-rose-500 font-bold px-1">{{ $message }}</span> @enderror
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[9px] sm:text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">Marital Status</label>
                                    <x-custom-dropdown 
                                        model="marital_status" 
                                        :options="[
                                            ['value' => 'Single', 'label' => 'Single (Belum Menikah)'],
                                            ['value' => 'Married', 'label' => 'Married (Menikah)'],
                                            ['value' => 'Divorced', 'label' => 'Divorced (Cerai Hidup)'],
                                            ['value' => 'Widowed', 'label' => 'Widowed (Cerai Mati)'],
                                            ['value' => 'Never Married', 'label' => 'Never Married']
                                        ]"
                                        placeholder="Pilih Status"
                                        icon="ri-group-line"
                                    />
                                </div>
                            </div>

                            <div class="bg-indigo-50/30 p-4 rounded-2xl border border-indigo-100 space-y-4">
                                <h6 class="text-[10px] font-black text-indigo-400 uppercase tracking-[0.2em] mb-3"><i class="ri-map-pin-line mr-1"></i> Data Alamat Administrasi (BPS)</h6>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-1">
                                        <label class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Provinsi</label>
                                        <x-custom-dropdown 
                                            model="provinsi_id" 
                                            :options="$this->provinsiOptions->map(fn($v) => ['value' => $v->kode, 'label' => $v->nama])->toArray()"
                                            placeholder="Pilih Provinsi"
                                            searchable="true"
                                            live="true"
                                        />

                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Kabupaten/Kota</label>
                                        <x-custom-dropdown 
                                            model="kabupaten_id" 
                                            :options="$this->kabupatenOptions->map(fn($v) => ['value' => $v->kode, 'label' => $v->nama])->toArray()"
                                            placeholder="Pilih Kabupaten"
                                            searchable="true"
                                            live="true"
                                        />

                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-1">
                                        <label class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Kecamatan</label>
                                        <x-custom-dropdown 
                                            model="kecamatan_id" 
                                            :options="$this->kecamatanOptions->map(fn($v) => ['value' => $v->kode, 'label' => $v->nama])->toArray()"
                                            placeholder="Pilih Kecamatan"
                                            searchable="true"
                                            live="true"
                                        />

                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Kelurahan/Desa</label>
                                        <x-custom-dropdown 
                                            model="kelurahan_id" 
                                            :options="$this->kelurahanOptions->map(fn($v) => ['value' => $v->kode, 'label' => $v->nama])->toArray()"
                                            placeholder="Pilih Kelurahan"
                                            searchable="true"
                                        />
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-1">
                                        <label class="text-[9px] font-black text-gray-400 uppercase tracking-widest px-1">Kode Pos</label>
                                        <input type="text" wire:model="kode_pos" 
                                               class="w-full bg-white border border-gray-100 rounded-xl py-2 px-4 text-sm font-bold text-[#2c3e50] focus:ring-4 focus:ring-indigo-100 focus:border-[#405189] transition-all outline-none" 
                                               placeholder="Kode Pos">
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Agama</label>
                                        <x-custom-dropdown 
                                            model="agama" 
                                            :options="[
                                                ['value' => 'Islam', 'label' => 'Islam'],
                                                ['value' => 'Kristen', 'label' => 'Kristen'],
                                                ['value' => 'Katolik', 'label' => 'Katolik'],
                                                ['value' => 'Hindu', 'label' => 'Hindu'],
                                                ['value' => 'Budha', 'label' => 'Budha'],
                                                ['value' => 'Konghucu', 'label' => 'Konghucu'],
                                                ['value' => 'Lainnya', 'label' => 'Lainnya']
                                            ]"
                                            placeholder="Pilih Agama"
                                            searchable="true"
                                        />
                                    </div>
                                </div>
                            </div>


                            <div class="space-y-1.5">
                                <label class="text-[9px] sm:text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">Alamat</label>
                                <textarea wire:model="alamat" rows="2" 
                                          class="w-full bg-gray-50 border border-gray-100 rounded-xl sm:rounded-2xl py-2.5 sm:py-3 px-4 text-sm font-medium text-gray-600 focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-[#405189] transition-all outline-none resize-none @error('alamat') border-rose-300 bg-rose-50/30 @enderror" 
                                          placeholder="Alamat lengkap..."></textarea>
                                @error('alamat') <span class="text-[10px] text-rose-500 font-bold px-1">{{ $message }}</span> @enderror
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 sm:gap-6">
                                <div class="space-y-1.5">
                                    <label class="text-[9px] sm:text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">Pekerjaan</label>
                                    <input type="text" wire:model="pekerjaan" 
                                           class="w-full bg-gray-50 border border-gray-100 rounded-xl sm:rounded-2xl py-2.5 sm:py-3 px-4 text-sm font-bold text-[#2c3e50] focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-[#405189] transition-all outline-none" 
                                           placeholder="Pekerjaan">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[9px] sm:text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">No. Penjamin</label>
                                    <input type="text" wire:model="no_penjamin" 
                                           class="w-full bg-gray-50 border border-gray-100 rounded-xl sm:rounded-2xl py-2.5 sm:py-3 px-4 text-sm font-bold text-[#2c3e50] focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-[#405189] transition-all outline-none" 
                                           placeholder="Nomor Kartu BPJS/Asuransi">
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <label class="text-[9px] sm:text-[10px] font-black text-rose-500 uppercase tracking-widest px-1">Alergi</label>
                                <textarea wire:model="alergi" rows="2" 
                                          class="w-full bg-red-50/30 border border-red-100 rounded-xl sm:rounded-2xl py-2.5 sm:py-3 px-4 text-sm font-medium text-red-600 focus:bg-white focus:ring-4 focus:ring-red-100 focus:border-red-400 transition-all outline-none resize-none placeholder:text-red-300" 
                                          placeholder="Sebutkan alergi jika ada..."></textarea>
                            </div>

                            <div class="flex items-center justify-between p-3 sm:p-4 bg-gray-50 rounded-xl sm:rounded-2xl border border-dashed border-gray-200">
                                <div class="flex items-center gap-2 sm:gap-3">
                                    <div class="flex h-7 w-7 sm:h-8 sm:w-8 items-center justify-center rounded-lg {{ $status === 'Aktif' ? 'bg-emerald-100 text-emerald-600 uppercase' : 'bg-rose-100 text-rose-600' }} shadow-sm">
                                        <i class="ri-{{ $status === 'Aktif' ? 'check-line' : 'close-line' }} text-base sm:text-lg font-bold"></i>
                                    </div>
                                    <div>
                                        <p class="text-[8px] sm:text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none">Status Pasien</p>
                                        <p class="text-[11px] sm:text-xs font-black {{ $status === 'Aktif' ? 'text-emerald-600' : 'text-rose-600' }} mt-1">{{ strtoupper($status) }}</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer scale-90 sm:scale-100">
                                    <input type="checkbox" class="sr-only peer" {{ $status === 'Aktif' ? 'checked' : '' }} @click="$wire.set('status', '{{ $status === 'Aktif' ? 'Tidak Aktif' : 'Aktif' }}')">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#0ab39c]"></div>
                                </label>
                            </div>
                        </form>
                    </div>

                    <div class="px-5 py-4 sm:px-8 sm:py-6 bg-gray-50/80 border-t border-gray-100 flex flex-col-reverse sm:flex-row justify-end gap-3 lg:gap-3">
                        <button type="button" @click="showModal = false" class="btn bg-orange-500 text-white w-full sm:w-auto px-6 h-10 flex items-center justify-center gap-2 transition-all hover:bg-orange-600 rounded-xl sm:rounded-2xl font-bold">
                            <i class="ri-arrow-go-back-line"></i> Batal
                        </button>
                        <button type="button" wire:click="save" wire:loading.attr="disabled" 
                                class="btn bg-[#0d6efd] text-white w-full sm:w-auto px-8 h-10 shadow-md flex items-center justify-center gap-2 rounded-xl sm:rounded-2xl font-black text-xs uppercase tracking-widest shadow-xl shadow-blue-500/10 hover:shadow-blue-500/20 hover:-translate-y-0.5 active:translate-y-0 transition-all group">
                            <svg wire:loading wire:target="save" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span wire:loading.remove wire:target="save" class="flex items-center gap-2">
                                <i class="ri-save-3-fill text-lg"></i>
                                {{ $isEdit ? 'Simpan Perubahan' : 'Simpan Data' }}
                            </span>
                            <span wire:loading wire:target="save" class="animate-pulse">Memproses...</span>
                        </button>
                    </div>
                </div>
            </div>
            <!-- Modal Riwayat Pasien -->
            <x-patient-history-modal 
                wire:model="showRiwayatModal"
                :show="$showRiwayatModal"
                :currentPasien="$currentPasien"
                :pasienHistoryData="$pasienHistoryData"
                :latestOdontogramState="$latestOdontogramState"
                :dentalCategories="$dentalCategories"
                :selectedPasienId="$selectedPasienId"
            />
        </div>
        HTML;
    }
}
