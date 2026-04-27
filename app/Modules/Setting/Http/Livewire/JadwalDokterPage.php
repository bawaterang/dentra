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

        return <<<'HTML'
        <div x-data="{ showModal: false, init(){this.$watch('showModal',v=>{if(v){$nextTick(()=>{this.$refs.firstInput&&this.$refs.firstInput.focus()})}})} }" @open-jadwal-modal.window="showModal=true" @close-jadwal-modal.window="showModal=false" x-init="init()">
            <style>
                .glass-header {
                    background: rgba(255, 255, 255, 0.8) !important;
                    backdrop-filter: blur(8px);
                    -webkit-backdrop-filter: blur(8px);
                }
                .jadwal-row:hover {
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
                        <i class="ri-calendar-todo-line"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold tracking-tight text-[#2c3e50]">Jadwal Dokter</h1>
                        <p class="text-xs text-[#878a99] font-medium mt-0.5">Atur jadwal praktek harian dokter di klinik.</p>
                    </div>
                </div>
                <div class="page-header-breadcrumb">
                    <a href="/dashboard" wire:navigate class="hover:text-[#405189] transition-colors"><i class="ri-home-4-line"></i></a>
                    <span class="sep text-gray-300">/</span>
                    <span class="text-gray-400 font-medium">Pengaturan</span>
                    <span class="sep text-gray-300">/</span>
                    <span class="text-[#405189] font-bold">Jadwal Dokter</span>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-3 mb-8">
                <div class="group relative overflow-hidden bg-white rounded-2xl p-5 shadow-sm hover:shadow-xl transition-all duration-500 border-l-4 border-[#405189]">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-[#405189] group-hover:bg-indigo-500 group-hover:text-white transition-all duration-300 shadow-inner">
                            <i class="ri-calendar-todo-line text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-[#878a99] font-bold text-[10px] uppercase tracking-[0.1em]">Total Jadwal</p>
                            <h4 class="text-2xl font-black text-[#2c3e50] leading-none mt-1">{{ number_format($totalJadwal) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="group relative overflow-hidden bg-white rounded-2xl p-5 shadow-sm hover:shadow-xl transition-all duration-500 border-l-4 border-[#0ab39c]">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-[#0ab39c] group-hover:bg-emerald-500 group-hover:text-white transition-all duration-300 shadow-inner">
                            <i class="ri-checkbox-circle-line text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-[#878a99] font-bold text-[10px] uppercase tracking-[0.1em]">Jadwal Aktif Terjadwal</p>
                            <h4 class="text-2xl font-black text-[#2c3e50] leading-none mt-1 text-[#0ab39c]">{{ number_format($totalAktif) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="group relative overflow-hidden bg-white rounded-2xl p-5 shadow-sm hover:shadow-xl transition-all duration-500 border-l-4 border-[#f7b84b]">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 text-[#f7b84b] group-hover:bg-amber-500 group-hover:text-white transition-all duration-300 shadow-inner">
                            <i class="ri-alarm-warning-line text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-[#878a99] font-bold text-[10px] uppercase tracking-[0.1em]">Jadwal Cuti/Libur</p>
                            <h4 class="text-2xl font-black text-[#2c3e50] leading-none mt-1 text-[#f7b84b]">{{ number_format($totalLiburCuti) }}</h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card overflow-hidden border-t-2 border-[#405189]">
                <div class="p-4 border-b border-[#eff2f7]">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                        <div class="flex overflow-x-auto scrollbar-hide -mx-2 px-2 lg:mx-0 lg:px-0">
                            <ul class="nav-pills-custom flex space-x-1">
                                <li class="nav-item">
                                    <a class="nav-link {{ $selectedHari === 'all' ? 'active active-pill-primary' : '' }}" wire:click="setHari('all')" role="button">
                                        <i class="ri-layout-grid-line"></i><span>Semua Hari</span>
                                    </a>
                                </li>
                                @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'] as $h)
                                <li class="nav-item">
                                    <a class="nav-link {{ $selectedHari === $h ? 'active active-pill-primary' : '' }}" wire:click="setHari('{{ $h }}')" role="button"><span>{{ $h }}</span></a>
                                </li>
                                @endforeach
                            </ul>
                        </div>

                        <div class="flex flex-wrap items-center gap-4 w-full lg:w-auto">
                            <div class="relative flex-grow min-w-[280px]">
                                <i class="ri-search-2-line absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-lg group-focus-within:text-[#405189]"></i>
                                <input type="text" wire:model.live.debounce.300ms="search" 
                                       class="w-full bg-gray-50/50 border border-gray-200 rounded-2xl py-2.5 pl-12 pr-4 text-sm font-medium outline-none transition-all search-focus-glow placeholder:text-gray-300" 
                                       placeholder="Cari dokter, spesialisasi, atau hari...">
                            </div>

                            <button @click="$wire.create()" class="btn btn-primary h-10 px-6 shadow-sm flex items-center justify-center gap-2 transition-all hover:translate-y-[-2px] hover:shadow-lg active:scale-95 w-full lg:w-auto">
                                <i class="ri-add-line text-xl"></i>
                                <span class="font-bold text-xs uppercase tracking-wider">Tambah Jadwal</span>
                            </button>
                        </div>

                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50">
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Hari</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Nama Dokter</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Spesialisasi</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Jam Praktik</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Status/Kehadiran</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($this->jadwals as $jadwal)
                            <tr wire:key="jadwal-{{ $jadwal->id }}" class="jadwal-row transition-all duration-200">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-bold text-[#405189] text-sm">{{ $jadwal->hari }}</span>
                                </td>
                                <td class="px-6 py-4 min-w-[200px]">
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 rounded-xl bg-indigo-50 text-[#405189] flex items-center justify-center font-black text-xs shadow-inner">
                                            {{ substr($jadwal->dokter?->nama_dokter ?? '?', 0, 1) }}
                                        </div>
                                        <div>
                                            <h6 class="text-sm font-bold text-[#2c3e50] mb-0">{{ $jadwal->dokter?->nama_dokter ?? '-' }}</h6>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-600">{{ $jadwal->dokter?->spesialisasi ?? '-' }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($jadwal->jam_mulai && $jadwal->jam_selesai)
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-50 text-blue-700 text-[11px] font-black border border-blue-100 uppercase tracking-tight">
                                            <i class="ri-time-line"></i> {{ \Carbon\Carbon::parse($jadwal->jam_mulai)->format('H:i') }} - {{ \Carbon\Carbon::parse($jadwal->jam_selesai)->format('H:i') }}
                                        </span>
                                    @else
                                        <span class="text-[10px] text-gray-400 font-bold uppercase tracking-widest italic">Tidak terjadwal</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col gap-1.5">
                                        @if($jadwal->is_active)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-600 text-[9px] font-black uppercase tracking-widest border border-emerald-100 w-fit">
                                                <span class="h-1 w-1 rounded-full bg-emerald-500"></span> Aktif
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-rose-50 text-rose-600 text-[9px] font-black uppercase tracking-widest border border-rose-100 w-fit">
                                                <span class="h-1 w-1 rounded-full bg-rose-500"></span> Nonaktif
                                            </span>
                                        @endif
                                        
                                        @php
                                            $statusColors = [
                                                'Hadir' => 'bg-blue-50 text-blue-600 border-blue-100',
                                                'Libur' => 'bg-gray-50 text-gray-600 border-gray-100',
                                                'Cuti' => 'bg-amber-50 text-amber-600 border-amber-100',
                                            ];
                                            $colorClass = $statusColors[$jadwal->status_kehadiran] ?? 'bg-gray-50 text-gray-600';
                                        @endphp
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md {{ $colorClass }} text-[9px] font-black uppercase tracking-widest border w-fit">
                                            {{ $jadwal->status_kehadiran }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <button wire:click="edit({{ $jadwal->id }})" class="action-btn-soft bg-indigo-50 text-[#405189] hover:bg-[#405189] hover:text-white shadow-sm" title="Edit Data">
                                            <i class="ri-pencil-fill text-sm"></i>
                                        </button>
                                        <button @click="Swal.fire({title:'Hapus Jadwal?',text:'Tindakan ini akan menghapus jadwal secara permanen.',icon:'warning',showCancelButton:true,confirmButtonColor:'#f06548',cancelButtonColor:'#6c757d',confirmButtonText:'Ya, Hapus!',cancelButtonText:'Batal',reverseButtons:true}).then((r)=>{if(r.isConfirmed){$wire.delete({{ $jadwal->id }})}})" 
                                                class="action-btn-soft bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white shadow-sm" title="Hapus Jadwal">
                                            <i class="ri-delete-bin-line text-sm"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="py-20 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-32 h-32 bg-gray-50 rounded-full flex items-center justify-center mb-6 animate-bounce" style="animation-duration: 4s;">
                                            <i class="ri-calendar-todo-line text-6xl text-gray-200"></i>
                                        </div>
                                        <p class="text-xl font-black text-gray-400">Jadwal Tidak Ditemukan</p>
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

                @if($this->jadwals->hasPages())
                <div class="px-6 py-5 sm:px-8 sm:py-6 bg-gray-50/50 border-t border-gray-100 pagination-custom">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-5">
                        <div class="text-[11px] font-bold text-[#878a99] tracking-tight text-center sm:text-left">
                            <i class="ri-list-check-2 text-[#405189] mr-1 hidden sm:inline"></i>
                            <span class="hidden sm:inline">Menampilkan</span> 
                            <span class="text-[#405189] font-black">{{ $this->jadwals->firstItem() }} - {{ $this->jadwals->lastItem() }}</span> 
                            dari <span class="text-[#405189] font-black">{{ number_format($this->jadwals->total()) }}</span> 
                            <span class="hidden sm:inline">jadwal ditemukan</span>
                            <span class="sm:hidden">total data</span>
                        </div>
                        {{ $this->jadwals->links() }}
                    </div>
                </div>
                @endif
            </div>


            <!-- Modal: Tambah/Edit Jadwal -->
            <div x-show="showModal" class="fixed inset-0 z-[1050] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" x-transition.opacity style="display: none;">
                <div x-show="showModal" x-transition.scale.95 class="w-full max-w-2xl bg-white rounded-xl shadow-2xl overflow-hidden">
                    <div class="px-5 py-4 sm:px-8 sm:py-6 bg-gradient-to-r from-gray-50 to-white border-b border-gray-100 flex items-center justify-between">
                        <div class="flex items-center gap-2 sm:gap-3">
                            <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-indigo-50 text-[#405189] flex items-center justify-center shadow-inner">
                                <i class="ri-calendar-todo-line text-lg sm:text-xl"></i>
                            </div>
                            <div>
                                <h5 class="text-sm sm:text-base font-black text-[#2c3e50] tracking-tight">{{ $isEdit ? 'Update Jadwal Dokter' : 'Tambah Jadwal Baru' }}</h5>
                                <p class="text-[9px] sm:text-[10px] text-gray-400 font-bold uppercase tracking-widest hidden sm:block">Lengkapi informasi jadwal di bawah</p>
                            </div>
                        </div>
                        <button @click="showModal = false" class="text-gray-400 hover:text-gray-600"><i class="ri-close-line text-2xl"></i></button>
                    </div>

                    <div class="px-5 py-6 sm:px-8 sm:py-8 max-h-[70vh] overflow-y-auto scrollbar-hide">
                        <form wire:submit.prevent="save" class="space-y-5 sm:space-y-6">
                            <div class="space-y-1.5">
                                <label class="text-[9px] sm:text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">Dokter <span class="text-rose-500">*</span></label>
                                <x-custom-dropdown 
                                    model="kode_dokter" 
                                    :options="$dokterOptions" 
                                    searchable="true" 
                                    placeholder="Pilih Dokter" 
                                    ref="firstInput"
                                />
                                @error('kode_dokter') <span class="text-[10px] text-rose-500 font-bold px-1">{{ $message }}</span> @enderror
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 sm:gap-6">
                                <div class="space-y-1.5">
                                    <label class="text-[9px] sm:text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">Hari Praktik <span class="text-rose-500">*</span></label>
                                    <x-custom-dropdown 
                                        model="hari" 
                                        :options="$hariOptions" 
                                        placeholder="Pilih Hari" 
                                        icon="ri-calendar-event-line" 
                                    />
                                    @error('hari') <span class="text-[10px] text-rose-500 font-bold px-1">{{ $message }}</span> @enderror
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[9px] sm:text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">Status Kehadiran <span class="text-rose-500">*</span></label>
                                    <x-custom-dropdown 
                                        model="status_kehadiran" 
                                        :options="$statusOptions" 
                                        icon="ri-checkbox-circle-line" 
                                    />
                                    @error('status_kehadiran') <span class="text-[10px] text-rose-500 font-bold px-1">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 sm:gap-6">
                                <div class="space-y-1.5">
                                    <label class="text-[9px] sm:text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">Jam Mulai</label>
                                    <input type="time" wire:model="jam_mulai" 
                                           class="w-full bg-gray-50 border border-gray-100 rounded-xl sm:rounded-2xl py-2.5 sm:py-3 px-4 text-sm font-bold text-[#2c3e50] focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-[#405189] transition-all outline-none">
                                    @error('jam_mulai') <span class="text-[10px] text-rose-500 font-bold px-1">{{ $message }}</span> @enderror
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[9px] sm:text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">Jam Selesai</label>
                                    <input type="time" wire:model="jam_selesai" 
                                           class="w-full bg-gray-50 border border-gray-100 rounded-xl sm:rounded-2xl py-2.5 sm:py-3 px-4 text-sm font-bold text-[#2c3e50] focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-[#405189] transition-all outline-none">
                                    @error('jam_selesai') <span class="text-[10px] text-rose-500 font-bold px-1">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-3 p-4 bg-gray-50/50 rounded-2xl border border-dashed border-gray-200">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" wire:model="is_active" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#405189]"></div>
                                </label>
                                <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Jadwal Aktif (Ditampilkan di Sistem)</span>
                            </div>
                        </form>
                    </div>

                    <div class="px-5 py-4 sm:px-8 sm:py-6 bg-gray-50/80 flex justify-end gap-3 border-t border-gray-100">
                        <button type="button" @click="showModal = false" class="btn bg-rose-50 text-rose-600 px-6 h-10 flex items-center gap-2 transition-all hover:bg-rose-600 hover:text-white font-bold text-xs uppercase tracking-widest">Batal</button>
                        <button type="button" wire:click="save" wire:loading.attr="disabled" class="btn bg-[#405189] text-white px-8 h-10 shadow-md flex items-center justify-center gap-2 transition-all hover:translate-y-[-2px] disabled:opacity-70 font-bold text-xs uppercase tracking-widest">
                            <i wire:loading.remove wire:target="save" class="ri-save-line"></i>
                            <span wire:loading.remove wire:target="save">Simpan Jadwal</span>
                            <span wire:loading wire:target="save">Memproses...</span>
                        </button>
                    </div>

                </div>
            </div>
        </div>
        HTML;
    }
}
