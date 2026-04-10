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
        ];
    }

    public function resetForm()
    {
        $this->reset(['dokterId', 'kode_dokter', 'nama_dokter', 'nik', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'alamat', 'no_telepon', 'agama', 'spesialisasi', 'no_sip', 'no_str', 'isEdit', 'user_id']);
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

        return <<<'HTML'
        <div x-data="{ showModal: false, init(){this.$watch('showModal',v=>{if(v){$nextTick(()=>{this.$refs.firstInput&&this.$refs.firstInput.focus()})}})} }" @open-modal.window="showModal=true" @close-modal.window="showModal=false" x-init="init()">
            <style>
                .glass-header {
                    background: rgba(255, 255, 255, 0.8) !important;
                    backdrop-filter: blur(8px);
                    -webkit-backdrop-filter: blur(8px);
                }
                .dokter-code-chip {
                    font-family: 'JetBrains Mono', 'Fira Code', monospace;
                    background: #f1f5f9;
                    color: #475569;
                    padding: 4px 8px;
                    border-radius: 6px;
                    font-size: 0.75rem;
                    border: 1px solid #e2e8f0;
                }
                .dokter-row:hover {
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
                .spesialisasi-pill {
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
                    background-color: #ffffff !important;
                    color: #475569 !important;
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
                    box-shadow: 0 4px 10px rgba(64, 81, 137, 0.2) !important;
                    z-index: 10 !important;
                }
            </style>

            <div class="page-header">
                <div class="page-header-title">
                    <div class="page-header-icon bg-gradient-to-br from-[#405189] to-[#2a3a6a] text-white shadow-lg animate-pulse" style="animation-duration: 3s;">
                        <i class="ri-nurse-line"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold tracking-tight text-[#2c3e50]">Master Data Dokter</h1>
                        <p class="text-xs text-[#878a99] font-medium mt-0.5">Kelola data dokter dan informasi praktik medis.</p>
                    </div>
                </div>
                <div class="page-header-breadcrumb">
                    <a href="/dashboard" wire:navigate class="hover:text-[#405189] transition-colors"><i class="ri-home-4-line"></i></a>
                    <span class="sep text-gray-300">/</span>
                    <span class="text-gray-400 font-medium">Master</span>
                    <span class="sep text-gray-300">/</span>
                    <span class="text-[#405189] font-bold">Dokter</span>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-8">
                <div class="group relative overflow-hidden bg-white rounded-2xl p-5 shadow-sm hover:shadow-xl transition-all duration-500 border-l-4 border-[#405189]">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-[#405189] group-hover:bg-indigo-500 group-hover:text-white transition-all duration-300 shadow-inner">
                            <i class="ri-user-star-line text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-[#878a99] font-bold text-[10px] uppercase tracking-[0.1em]">Total Dokter</p>
                            <h4 class="text-2xl font-black text-[#2c3e50] leading-none mt-1">{{ number_format($totalDokter) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="group relative overflow-hidden bg-white rounded-2xl p-5 shadow-sm hover:shadow-xl transition-all duration-500 border-l-4 border-[#0ab39c]">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-[#0ab39c] group-hover:bg-emerald-500 group-hover:text-white transition-all duration-300 shadow-inner">
                            <i class="ri-medal-line text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-[#878a99] font-bold text-[10px] uppercase tracking-[0.1em]">Spesialis</p>
                            <h4 class="text-2xl font-black text-[#2c3e50] leading-none mt-1 text-[#0ab39c]">{{ number_format($totalSpesialis) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="group relative overflow-hidden bg-white rounded-2xl p-5 shadow-sm hover:shadow-xl transition-all duration-500 border-l-4 border-[#f7b84b]">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 text-[#f7b84b] group-hover:bg-amber-500 group-hover:text-white transition-all duration-300 shadow-inner">
                            <i class="ri-history-line text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-[#878a99] font-bold text-[10px] uppercase tracking-[0.1em]">Dokter Cuti</p>
                            <h4 class="text-2xl font-black text-[#2c3e50] leading-none mt-1 text-[#f7b84b]">{{ number_format($dokterCutiCount) }}</h4>
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
                            <li class="nav-item">
                                <a class="nav-link {{ $selectedStatus === 'Cuti' ? 'active active-pill-warning' : '' }}" 
                                   wire:click="setStatus('Cuti')" role="button">
                                    <i class="ri-calendar-todo-line"></i><span>Cuti</span>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div class="flex flex-wrap items-center gap-4 w-full lg:w-auto">
                        <div class="relative flex-grow min-w-[280px]">
                            <i class="ri-search-2-line absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-lg group-focus-within:text-[#405189]"></i>
                            <input type="text" wire:model.live.debounce.300ms="search" 
                                   class="w-full bg-gray-50/50 border border-gray-200 rounded-2xl py-2.5 pl-12 pr-4 text-sm font-medium outline-none transition-all search-focus-glow placeholder:text-gray-300" 
                                   placeholder="Cari kode, nama, atau spesialisasi...">
                        </div>
                        
                        <div class="grid grid-cols-2 gap-3 w-full lg:flex lg:w-auto lg:items-center lg:gap-1.5 lg:p-1 lg:rounded-lg lg:border lg:border-[#e2e8f0] lg:bg-white">
                            <a href="{{ route('master.dokter.print', ['status' => $selectedStatus]) }}" target="_blank" 
                               class="flex flex-col lg:flex-row items-center justify-center gap-2 p-4 lg:p-0 lg:h-8 lg:w-8 rounded-2xl lg:rounded-md bg-white border border-gray-100 lg:border-none shadow-sm lg:shadow-none hover:bg-indigo-50 transition-all group/print" title="Cetak PDF">
                                <i class="ri-printer-line text-2xl lg:text-lg text-indigo-500 group-hover/print:scale-110 transition-transform"></i>
                                <span class="lg:hidden text-[10px] font-black text-gray-400 uppercase tracking-widest">Cetak PDF</span>
                            </a>
                            <div class="hidden lg:block w-[1px] h-4 bg-[#e2e8f0]"></div>
                            <a href="{{ route('master.dokter.export', ['status' => $selectedStatus]) }}" target="_blank" 
                               class="flex flex-col lg:flex-row items-center justify-center gap-2 p-4 lg:p-0 lg:h-8 lg:w-8 rounded-2xl lg:rounded-md bg-white border border-gray-100 lg:border-none shadow-sm lg:shadow-none hover:bg-emerald-50 transition-all group/export" title="Unduh Excel">
                                <i class="ri-file-excel-2-line text-2xl lg:text-lg text-emerald-500 group-hover/export:scale-110 transition-transform"></i>
                                <span class="lg:hidden text-[10px] font-black text-gray-400 uppercase tracking-widest">Ekspor Excel</span>
                            </a>
                        </div>

                        <button @click="$wire.create()" class="btn btn-primary h-10 px-6 shadow-sm flex items-center justify-center gap-2 transition-all hover:translate-y-[-2px] hover:shadow-lg active:scale-95 w-full lg:w-auto">
                            <i class="ri-add-line text-xl"></i>
                            <span class="font-bold text-xs uppercase tracking-wider">Tambah Dokter</span>
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50">
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Kode Dokter</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Nama Dokter</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Spesialisasi</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">No. SIP</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">No. Telepon</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Status</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($this->dokters as $dokter)
                            <tr wire:key="dokter-{{ $dokter->id }}" class="dokter-row transition-all duration-200">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="dokter-code-chip shadow-sm">{{ $dokter->kode_dokter }}</span>
                                </td>
                                <td class="px-6 py-4 min-w-[250px]">
                                    <div class="group relative">
                                        <div class="font-bold text-[#2c3e50] text-sm group-hover:text-[#405189] transition-colors line-clamp-1">
                                            {{ $dokter->nama_dokter }}
                                            @if($dokter->user_id)
                                                <i class="ri-user-link-line text-xs text-blue-500 ml-1" title="Terhubung ke User: {{ $dokter->user?->username }}"></i>
                                            @endif
                                        </div>
                                        <div class="text-[11px] text-gray-400 font-medium italic mt-1 leading-relaxed line-clamp-1 group-hover:line-clamp-none transition-all duration-300">
                                            {{ $dokter->no_str ?: 'STR belum terdaftar.' }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $specColor = $dokter->spesialisasi ? 'bg-indigo-50 text-indigo-600' : 'bg-gray-50 text-gray-500';
                                    @endphp
                                    <span class="spesialisasi-pill {{ $specColor }}">
                                        {{ $dokter->spesialisasi ?: 'Umum' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-600">{{ $dokter->no_sip ?: '-' }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-600">{{ $dokter->no_telepon ?: '-' }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($dokter->status == 'Aktif')
                                    <span class="status-badge-modern bg-emerald-50 text-emerald-600 border border-emerald-100">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                                        Aktif
                                    </span>
                                    @elseif($dokter->status == 'Cuti')
                                    <span class="status-badge-modern bg-amber-50 text-amber-600 border border-amber-100">
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-ping"></span>
                                        Cuti
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
                                        <button wire:click="history({{ $dokter->id }})" class="action-btn-soft bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white shadow-sm" title="Riwayat">
                                            <i class="ri-history-line text-sm"></i>
                                        </button>
                                        <button wire:click="edit({{ $dokter->id }})" class="action-btn-soft bg-indigo-50 text-[#405189] hover:bg-[#405189] hover:text-white shadow-sm" title="Edit Data">
                                            <i class="ri-pencil-fill text-sm"></i>
                                        </button>
                                        <button @click="if('{{ $dokter->status }}'==='Cuti'||'{{ $dokter->status }}'==='Tidak Aktif'){Swal.fire({title:'Informasi',text:'Dokter dengan status {{ $dokter->status }} tidak dapat dihapus.',icon:'info',confirmButtonColor:'#405189'})}else{Swal.fire({title:'Konfirmasi Nonaktif',text:'Apakah Anda yakin ingin menonaktifkan dokter {{ $dokter->nama_dokter }}?',icon:'warning',showCancelButton:true,confirmButtonColor:'#f06548',cancelButtonColor:'#6c757d',confirmButtonText:'Ya, Nonaktifkan',cancelButtonText:'Batal',reverseButtons:true}).then((r)=>{if(r.isConfirmed){$wire.delete({{ $dokter->id }})}})}" 
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

                @if($this->dokters->hasPages())
                <div class="px-6 py-5 sm:px-8 sm:py-6 bg-gray-50/50 border-t border-gray-100 pagination-custom">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-5">
                        <div class="text-[11px] font-bold text-[#878a99] tracking-tight text-center sm:text-left">
                            <i class="ri-list-check-2 text-[#405189] mr-1 hidden sm:inline"></i>
                            <span class="hidden sm:inline">Menampilkan</span> 
                            <span class="text-[#405189] font-black">{{ $this->dokters->firstItem() }} - {{ $this->dokters->lastItem() }}</span> 
                            dari <span class="text-[#405189] font-black">{{ number_format($this->dokters->total()) }}</span> 
                            <span class="hidden sm:inline">dokter terdaftar</span>
                            <span class="sm:hidden">total data</span>
                        </div>
                        {{ $this->dokters->links() }}
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
                                <i class="ri-nurse-line text-lg sm:text-xl"></i>
                            </div>
                            <div>
                                <h5 class="text-sm sm:text-base font-black text-[#2c3e50] tracking-tight">{{ $isEdit ? 'Update Data Dokter' : 'Dokter Baru' }}</h5>
                                <p class="text-[9px] sm:text-[10px] text-gray-400 font-bold uppercase tracking-widest hidden sm:block">Lengkapi informasi dokter di bawah</p>
                            </div>
                        </div>
                        <button @click="showModal = false" class="w-8 h-8 rounded-full flex items-center justify-center text-gray-400 hover:bg-gray-100 transition-all"><i class="ri-close-line text-lg"></i></button>
                    </div>

                    <div class="px-5 py-6 sm:px-8 sm:py-8 max-h-[70vh] overflow-y-auto scrollbar-hide">
                        <form wire:submit.prevent="save" class="space-y-5 sm:space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 sm:gap-6">
                                <div class="space-y-1.5">
                                    <label class="text-[9px] sm:text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">Kode Dokter <span class="text-rose-500">*</span></label>
                                    <input type="text" wire:model="kode_dokter" x-ref="firstInput" 
                                           class="w-full bg-gray-50 border border-gray-100 rounded-xl sm:rounded-2xl py-2.5 sm:py-3 px-4 text-sm font-black text-[#405189] uppercase tracking-wider focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-[#405189] transition-all outline-none @error('kode_dokter') border-rose-300 bg-rose-50/30 @enderror {{ ($isEdit || $kodeReadonly) ? 'bg-gray-100 cursor-not-allowed' : '' }}" 
                                           placeholder="D00001" {{ ($isEdit || $kodeReadonly) ? 'readonly' : '' }}>
                                    @error('kode_dokter') <span class="text-[10px] text-rose-500 font-bold px-1">{{ $message }}</span> @enderror
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
                                <input type="text" wire:model="nama_dokter" 
                                       class="w-full bg-gray-50 border border-gray-100 rounded-xl sm:rounded-2xl py-2.5 sm:py-3 px-4 text-sm font-bold text-[#2c3e50] focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-[#405189] transition-all outline-none @error('nama_dokter') border-rose-300 bg-rose-50/30 @enderror" 
                                       placeholder="Contoh: drg. Ahmad Sulaiman">
                                @error('nama_dokter') <span class="text-[10px] text-rose-500 font-bold px-1">{{ $message }}</span> @enderror
                            </div>

                            <div class="space-y-1.5 p-4 bg-blue-50/50 rounded-2xl border border border-blue-100/50">
                                <label class="text-[9px] sm:text-[10px] font-black text-blue-600 uppercase tracking-widest px-1">Pilih Akun User <span class="text-gray-400 text-[8px] ml-1">(Untuk Akses Transaksi)</span></label>
                                <x-custom-dropdown 
                                    model="user_id" 
                                    :options="$userOptions"
                                    placeholder="Hubungkan ke Akun Login"
                                    searchable="true"
                                />
                                <p class="text-[9px] text-blue-400 mt-1 px-1">Menghubungkan dokter ke user agar dokter hanya melihat pasiennya sendiri di menu Transaksi.</p>
                                @error('user_id') <span class="text-[10px] text-rose-500 font-bold px-1">{{ $message }}</span> @enderror
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
                                    <label class="text-[9px] sm:text-[10px] font-black text-rose-500 uppercase tracking-widest px-1">Spesialisasi</label>
                                    <input type="text" wire:model="spesialisasi" 
                                           class="w-full bg-red-50/30 border border-red-100 rounded-xl sm:rounded-2xl py-2.5 sm:py-3 px-4 text-sm font-semibold text-red-600 focus:bg-white focus:ring-4 focus:ring-red-100 focus:border-red-400 transition-all outline-none" 
                                           placeholder="Contoh: Bedah Mulut">
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
                                    <label class="text-[9px] sm:text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">No. SIP</label>
                                    <input type="text" wire:model="no_sip" 
                                           class="w-full bg-gray-50 border border-gray-100 rounded-xl sm:rounded-2xl py-2.5 sm:py-3 px-4 text-sm font-bold text-[#2c3e50] focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-[#405189] transition-all outline-none" 
                                           placeholder="Surat Izin Praktik">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[9px] sm:text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">No. STR</label>
                                    <input type="text" wire:model="no_str" 
                                           class="w-full bg-gray-50 border border-gray-100 rounded-xl sm:rounded-2xl py-2.5 sm:py-3 px-4 text-sm font-bold text-[#2c3e50] focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-[#405189] transition-all outline-none" 
                                           placeholder="Surat Tanda Registrasi">
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <label class="text-[9px] sm:text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">No. Telepon</label>
                                <input type="text" wire:model="no_telepon" 
                                       class="w-full bg-gray-50 border border-gray-100 rounded-xl sm:rounded-2xl py-2.5 sm:py-3 px-4 text-sm font-bold text-[#2c3e50] focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-[#405189] transition-all outline-none @error('no_telepon') border-rose-300 bg-rose-50/30 @enderror" 
                                       placeholder="08xxxx">
                                @error('no_telepon') <span class="text-[10px] text-rose-500 font-bold px-1">{{ $message }}</span> @enderror
                            </div>

                            <div class="space-y-1.5">
                                <label class="text-[9px] sm:text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">Alamat</label>
                                <textarea wire:model="alamat" rows="2" 
                                          class="w-full bg-gray-50 border border-gray-100 rounded-xl sm:rounded-2xl py-2.5 sm:py-3 px-4 text-sm font-medium text-gray-600 focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-[#405189] transition-all outline-none resize-none @error('alamat') border-rose-300 bg-rose-50/30 @enderror" 
                                          placeholder="Alamat lengkap..."></textarea>
                                @error('alamat') <span class="text-[10px] text-rose-500 font-bold px-1">{{ $message }}</span> @enderror
                            </div>

                            <div class="space-y-1.5">
                                <label class="text-[9px] sm:text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">Warna Identitas</label>
                                <div class="flex flex-wrap gap-2">
                                    @foreach(['#405189', '#0ab39c', '#f7b84b', '#f06548', '#299cdb', '#878a99', '#6559cc', '#f672a7'] as $c)
                                        <button type="button" 
                                            wire:click="$set('color', '{{ $c }}')"
                                            class="w-8 h-8 rounded-full border-2 transition-all hover:scale-110 {{ $color === $c ? 'border-gray-800 ring-2 ring-gray-200' : 'border-transparent' }}"
                                            style="background-color: {{ $c }}">
                                        </button>
                                    @endforeach
                                    <input type="color" wire:model="color" class="w-8 h-8 rounded-full border-none p-0 cursor-pointer overflow-hidden bg-transparent">
                                </div>
                            </div>

                            <div class="space-y-1.5 p-3 sm:p-4 bg-gray-50 rounded-xl sm:rounded-2xl border border-dashed border-gray-200">
                                <label class="text-[9px] sm:text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">Status Praktik <span class="text-rose-500">*</span></label>
                                <div class="grid grid-cols-3 gap-2">
                                    <button type="button" wire:click="$set('status', 'Aktif')" 
                                            class="flex flex-col items-center justify-center p-2 rounded-xl border-2 transition-all {{ $status === 'Aktif' ? 'bg-emerald-50 border-emerald-500 text-emerald-700 shadow-sm' : 'bg-white border-transparent text-gray-400 hover:border-gray-200' }}">
                                        <i class="ri-checkbox-circle-fill text-lg"></i>
                                        <span class="text-[9px] font-black uppercase tracking-tighter">Aktif</span>
                                    </button>
                                    <button type="button" wire:click="$set('status', 'Cuti')" 
                                            class="flex flex-col items-center justify-center p-2 rounded-xl border-2 transition-all {{ $status === 'Cuti' ? 'bg-amber-50 border-amber-500 text-amber-700 shadow-sm' : 'bg-white border-transparent text-gray-400 hover:border-gray-200' }}">
                                        <i class="ri-calendar-event-fill text-lg"></i>
                                        <span class="text-[9px] font-black uppercase tracking-tighter">Cuti</span>
                                    </button>
                                    <button type="button" wire:click="$set('status', 'Tidak Aktif')" 
                                            class="flex flex-col items-center justify-center p-2 rounded-xl border-2 transition-all {{ $status === 'Tidak Aktif' ? 'bg-rose-50 border-rose-500 text-rose-700 shadow-sm' : 'bg-white border-transparent text-gray-400 hover:border-gray-200' }}">
                                        <i class="ri-close-circle-fill text-lg"></i>
                                        <span class="text-[9px] font-black uppercase tracking-tighter">Non-Aktif</span>
                                    </button>
                                </div>
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
        </div>
        HTML;
    }
}
