<?php

namespace App\Modules\Setting\Http\Livewire;

use App\Models\TrxInformasi;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class InformasiPage extends Component
{
    use WithPagination;

    public $informasiId;
    public $description;
    public $date_start;
    public $date_expired;

    public $totalInformasi = 0;
    public $aktif = 0;
    public $expired = 0;
    public $selectedStatus = 'all';
    public $search = '';
    public $isEdit = false;

    protected $queryString = ['search', 'selectedStatus'];

    #[Computed]
    public function informations()
    {
        // Using withTrashed to show all info including soft deleted if needed,
        // but typically we only want active ones. The user asked for soft delete 
        // to be implemented, meaning we might not query withTrashed() by default
        // unless requested. We'll stick to non-deleted for standard view.
        $query = TrxInformasi::query();
        
        $today = Carbon::today()->format('Y-m-d');

        if ($this->selectedStatus === 'Aktif') {
            $query->where('date_start', '<=', $today)
                  ->where('date_expired', '>=', $today);
        } elseif ($this->selectedStatus === 'Expired') {
            $query->where(function ($q) use ($today) {
                $q->where('date_expired', '<', $today)
                  ->orWhere('date_start', '>', $today);
            });
        }

        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('description', 'like', '%'.$this->search.'%')
                  ->orWhere('created_by', 'like', '%'.$this->search.'%');
            });
        }

        return $query->orderBy('date_start', 'desc')->paginate(10);
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
            'description' => 'required|string',
            'date_start' => 'required|date',
            'date_expired' => 'required|date|after_or_equal:date_start',
        ];
    }

    public function resetForm()
    {
        $this->reset([
            'informasiId', 'description', 'date_start', 'date_expired', 'isEdit'
        ]);

        $this->resetErrorBag();
    }

    public function create()
    {
        $this->resetForm();
        $this->dispatch('open-modal');
    }

    public function edit($id)
    {
        $this->resetForm();
        $info = TrxInformasi::findOrFail($id);

        $this->informasiId = $info->id;
        $this->description = $info->description;
        $this->date_start = $info->date_start ? $info->date_start->format('Y-m-d') : null;
        $this->date_expired = $info->date_expired ? $info->date_expired->format('Y-m-d') : null;

        $this->isEdit = true;
        $this->dispatch('open-modal');
    }

    public function save()
    {
        try {
            $this->validate($this->rules());

            $info = $this->informasiId
                ? TrxInformasi::findOrFail($this->informasiId)
                : new TrxInformasi;

            $info->fill([
                'description' => $this->description,
                'date_start' => $this->date_start,
                'date_expired' => $this->date_expired,
            ]);

            if (! $this->informasiId) {
                $info->created_by = Auth::user()->username ?? 'System';
            }

            $info->save();

            $this->dispatch('close-modal');
            $this->dispatch('alert', ['type' => 'success', 'message' => $this->isEdit ? 'Data informasi berhasil diperbarui!' : 'Informasi baru berhasil ditambahkan!']);
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
        try {
            $info = TrxInformasi::findOrFail($id);
            $info->delete();
            $this->dispatch('alert', ['type' => 'success', 'message' => 'Data informasi berhasil dihapus!']);
        } catch (\Exception $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Hapus Gagal: '.$e->getMessage()]);
        }
    }

    public function render()
    {
        $this->totalInformasi = TrxInformasi::count();
        $today = Carbon::today()->format('Y-m-d');
        
        $this->aktif = TrxInformasi::where('date_start', '<=', $today)
                                   ->where('date_expired', '>=', $today)
                                   ->count();
                                   
        $this->expired = TrxInformasi::where(function($q) use ($today) {
                                       $q->where('date_expired', '<', $today)
                                         ->orWhere('date_start', '>', $today);
                                   })->count();

        return <<<'HTML'
        <div x-data="{ showModal: false, init(){this.$watch('showModal',v=>{if(v){$nextTick(()=>{this.$refs.firstInput&&this.$refs.firstInput.focus()})}})} }" @open-modal.window="showModal=true" @close-modal.window="showModal=false" x-init="init()">
            <style>
                .glass-header {
                    background: rgba(255, 255, 255, 0.8) !important;
                    backdrop-filter: blur(8px);
                    -webkit-backdrop-filter: blur(8px);
                }
                .info-row:hover {
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
                        <i class="ri-information-line"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold tracking-tight text-[#2c3e50]">Manajemen Informasi</h1>
                        <p class="text-xs text-[#878a99] font-medium mt-0.5">Kelola informasi yang akan ditampilkan di web profile klinik.</p>
                    </div>
                </div>
                <div class="page-header-breadcrumb">
                    <a href="/dashboard" wire:navigate class="hover:text-[#405189] transition-colors"><i class="ri-home-4-line"></i></a>
                    <span class="sep text-gray-300">/</span>
                    <span class="text-gray-400 font-medium">Setting</span>
                    <span class="sep text-gray-300">/</span>
                    <span class="text-[#405189] font-bold">Informasi</span>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 mb-8">
                <div class="group relative overflow-hidden bg-white rounded-2xl p-5 shadow-sm hover:shadow-xl transition-all duration-500 border-l-4 border-[#405189]">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-[#405189] group-hover:bg-indigo-500 group-hover:text-white transition-all duration-300 shadow-inner">
                            <i class="ri-file-list-3-line text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-[#878a99] font-bold text-[10px] uppercase tracking-[0.1em]">Total Informasi</p>
                            <h4 class="text-2xl font-black text-[#2c3e50] leading-none mt-1">{{ number_format($totalInformasi) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="group relative overflow-hidden bg-white rounded-2xl p-5 shadow-sm hover:shadow-xl transition-all duration-500 border-l-4 border-[#0ab39c]">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-[#0ab39c] group-hover:bg-emerald-500 group-hover:text-white transition-all duration-300 shadow-inner">
                            <i class="ri-checkbox-circle-line text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-[#878a99] font-bold text-[10px] uppercase tracking-[0.1em]">Informasi Aktif</p>
                            <h4 class="text-2xl font-black text-[#2c3e50] leading-none mt-1 text-[#0ab39c]">{{ number_format($aktif) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="group relative overflow-hidden bg-white rounded-2xl p-5 shadow-sm hover:shadow-xl transition-all duration-500 border-l-4 border-[#f06548]">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-rose-50 text-[#f06548] group-hover:bg-rose-500 group-hover:text-white transition-all duration-300 shadow-inner">
                            <i class="ri-close-circle-line text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-[#878a99] font-bold text-[10px] uppercase tracking-[0.1em]">Expired</p>
                            <h4 class="text-2xl font-black text-[#2c3e50] leading-none mt-1 text-[#f06548]">{{ number_format($expired) }}</h4>
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
                                    <i class="ri-check-line"></i><span>Aktif</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $selectedStatus === 'Expired' ? 'active active-pill-danger' : '' }}" 
                                   wire:click="setStatus('Expired')" role="button">
                                    <i class="ri-close-line"></i><span>Expired</span>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div class="flex flex-wrap items-center gap-4 w-full lg:w-auto">
                        <div class="relative flex-grow min-w-[280px]">
                            <i class="ri-search-2-line absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-lg group-focus-within:text-[#405189]"></i>
                            <input type="text" wire:model.live.debounce.300ms="search" 
                                   class="w-full bg-gray-50/50 border border-gray-200 rounded-2xl py-2.5 pl-12 pr-4 text-sm font-medium outline-none transition-all search-focus-glow placeholder:text-gray-300" 
                                   placeholder="Cari deskripsi atau pembuat...">
                        </div>
                        
                        <div class="grid grid-cols-2 gap-3 w-full lg:flex lg:w-auto lg:items-center lg:gap-1.5 lg:p-1 lg:rounded-lg lg:border lg:border-[#e2e8f0] lg:bg-white">
                            <a href="{{ route('setting.informasi.print', ['status' => $selectedStatus]) }}" target="_blank" 
                               class="flex flex-col lg:flex-row items-center justify-center gap-2 p-4 lg:p-0 lg:h-8 lg:w-8 rounded-2xl lg:rounded-md bg-white border border-gray-100 lg:border-none shadow-sm lg:shadow-none hover:bg-indigo-50 transition-all group/print" title="Cetak PDF">
                                <i class="ri-printer-line text-2xl lg:text-lg text-indigo-500 group-hover/print:scale-110 transition-transform"></i>
                                <span class="lg:hidden text-[10px] font-black text-gray-400 uppercase tracking-widest">Cetak PDF</span>
                            </a>
                            <div class="hidden lg:block w-[1px] h-4 bg-[#e2e8f0]"></div>
                            <a href="{{ route('setting.informasi.export', ['status' => $selectedStatus]) }}" target="_blank" 
                               class="flex flex-col lg:flex-row items-center justify-center gap-2 p-4 lg:p-0 lg:h-8 lg:w-8 rounded-2xl lg:rounded-md bg-white border border-gray-100 lg:border-none shadow-sm lg:shadow-none hover:bg-emerald-50 transition-all group/export" title="Unduh Excel">
                                <i class="ri-file-excel-2-line text-2xl lg:text-lg text-emerald-500 group-hover/export:scale-110 transition-transform"></i>
                                <span class="lg:hidden text-[10px] font-black text-gray-400 uppercase tracking-widest">Ekspor Excel</span>
                            </a>
                        </div>

                        <button @click="$wire.create()" class="btn btn-primary h-10 px-6 shadow-sm flex items-center justify-center gap-2 transition-all hover:translate-y-[-2px] hover:shadow-lg active:scale-95 w-full lg:w-auto">
                            <i class="ri-add-line text-xl"></i>
                            <span class="font-bold text-xs uppercase tracking-wider">Tambah Data</span>
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50">
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 w-16 text-center">No</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 w-1/3">Deskripsi Informasi</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Periode Tayang</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Pembuat</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 text-center">Status</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @php 
                                $todayDate = \Carbon\Carbon::today()->format('Y-m-d');
                                $startNo = ($this->informations->currentPage() - 1) * $this->informations->perPage() + 1;
                            @endphp
                            @forelse($this->informations as $index => $info)
                            <tr wire:key="info-{{ $info->id }}" class="info-row transition-all duration-200">
                                <td class="px-6 py-4 text-center text-sm font-semibold text-gray-500">
                                    {{ $startNo++ }}
                                </td>
                                <td class="px-6 py-4 min-w-[250px]">
                                    <div class="text-[#2c3e50] text-sm leading-relaxed">{{ $info->description }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="bg-gray-50 border border-gray-100 rounded-md px-2 py-1 text-xs text-gray-600 font-medium">
                                            {{ \Carbon\Carbon::parse($info->date_start)->format('d M Y') }}
                                        </div>
                                        <i class="ri-arrow-right-line text-gray-300 text-xs"></i>
                                        <div class="bg-gray-50 border border-gray-100 rounded-md px-2 py-1 text-xs text-gray-600 font-medium">
                                            {{ \Carbon\Carbon::parse($info->date_expired)->format('d M Y') }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="h-6 w-6 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600">
                                            <i class="ri-user-smile-line text-xs"></i>
                                        </div>
                                        <span class="text-sm font-medium text-gray-700">{{ $info->created_by ?? '-' }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if($info->date_start->format('Y-m-d') <= $todayDate && $info->date_expired->format('Y-m-d') >= $todayDate)
                                    <span class="status-badge-modern bg-emerald-50 text-emerald-600 border border-emerald-100">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                                        Aktif
                                    </span>
                                    @else
                                    <span class="status-badge-modern bg-rose-50 text-rose-600 border border-rose-100">
                                        <span class="h-1.5 w-1.5 rounded-full bg-rose-400"></span>
                                        Expired
                                    </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <button wire:click="edit({{ $info->id }})" class="action-btn-soft bg-indigo-50 text-[#405189] hover:bg-[#405189] hover:text-white shadow-sm" title="Edit Data">
                                            <i class="ri-pencil-fill text-sm"></i>
                                        </button>
                                        <button @click="Swal.fire({title:'Konfirmasi Hapus',text:'Apakah Anda yakin ingin menghapus informasi ini?',icon:'warning',showCancelButton:true,confirmButtonColor:'#f06548',cancelButtonColor:'#6c757d',confirmButtonText:'Ya, Hapus',cancelButtonText:'Batal',reverseButtons:true}).then((r)=>{if(r.isConfirmed){$wire.delete({{ $info->id }})}})" 
                                                class="action-btn-soft bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white shadow-sm" title="Hapus Data">
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
                                            <i class="ri-file-search-line text-6xl text-gray-200"></i>
                                        </div>
                                        <p class="text-xl font-black text-gray-400">Data Tidak Ditemukan</p>
                                        <p class="text-xs text-gray-300 mt-1 uppercase tracking-widest font-bold">Belum ada data informasi web profile yang ditambahkan.</p>
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

                @if($this->informations->hasPages())
                <div class="px-6 py-5 sm:px-8 sm:py-6 bg-gray-50/50 border-t border-gray-100 pagination-custom">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-5">
                        <div class="text-[11px] font-bold text-[#878a99] tracking-tight text-center sm:text-left">
                            <i class="ri-list-check-2 text-[#405189] mr-1 hidden sm:inline"></i>
                            <span class="hidden sm:inline">Menampilkan</span> 
                            <span class="text-[#405189] font-black">{{ $this->informations->firstItem() }} - {{ $this->informations->lastItem() }}</span> 
                            dari <span class="text-[#405189] font-black">{{ number_format($this->informations->total()) }}</span> 
                            <span class="hidden sm:inline">data informasi</span>
                            <span class="sm:hidden">total data</span>
                        </div>
                        {{ $this->informations->links() }}
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
                                <i class="ri-information-line text-lg sm:text-xl"></i>
                            </div>
                            <div>
                                <h5 class="text-sm sm:text-base font-black text-[#2c3e50] tracking-tight">{{ $isEdit ? 'Ubah Informasi' : 'Tambah Informasi Baru' }}</h5>
                                <p class="text-[9px] sm:text-[10px] text-gray-400 font-bold uppercase tracking-widest hidden sm:block">Atur konten informasi untuk web profile</p>
                            </div>
                        </div>
                        <button @click="showModal = false" class="w-8 h-8 rounded-full flex items-center justify-center text-gray-400 hover:bg-gray-100 transition-all"><i class="ri-close-line text-lg"></i></button>
                    </div>

                    <div class="px-5 py-6 sm:px-8 sm:py-8 max-h-[70vh] overflow-y-auto scrollbar-hide">
                        <form wire:submit.prevent="save" class="space-y-5 sm:space-y-6">
                            
                            <div class="space-y-1.5">
                                <label class="text-[9px] sm:text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">Deskripsi Informasi <span class="text-rose-500">*</span></label>
                                <textarea wire:model="description" x-ref="firstInput" rows="4"
                                       class="w-full bg-gray-50 border border-gray-100 rounded-xl sm:rounded-2xl py-2.5 sm:py-3 px-4 text-sm font-bold text-[#2c3e50] focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-[#405189] transition-all outline-none resize-none @error('description') border-rose-300 bg-rose-50/30 @enderror" 
                                       placeholder="Tuliskan informasi yang ingin disampaikan..."></textarea>
                                @error('description') <span class="text-[10px] text-rose-500 font-bold px-1">{{ $message }}</span> @enderror
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 sm:gap-6">
                                <div class="space-y-1.5">
                                    <label class="text-[9px] sm:text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">Tanggal Mulai Tayang <span class="text-rose-500">*</span></label>
                                    <input type="date" wire:model="date_start" 
                                           class="w-full bg-gray-50 border border-gray-100 rounded-xl sm:rounded-2xl py-2.5 sm:py-3 px-4 text-sm font-bold text-[#2c3e50] focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-[#405189] transition-all outline-none @error('date_start') border-rose-300 bg-rose-50/30 @enderror">
                                    @error('date_start') <span class="text-[10px] text-rose-500 font-bold px-1">{{ $message }}</span> @enderror
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[9px] sm:text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">Tanggal Akhir Tayang <span class="text-rose-500">*</span></label>
                                    <input type="date" wire:model="date_expired" 
                                           class="w-full bg-gray-50 border border-gray-100 rounded-xl sm:rounded-2xl py-2.5 sm:py-3 px-4 text-sm font-bold text-[#2c3e50] focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-[#405189] transition-all outline-none @error('date_expired') border-rose-300 bg-rose-50/30 @enderror">
                                    @error('date_expired') <span class="text-[10px] text-rose-500 font-bold px-1">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="pt-4 border-t border-gray-100 flex justify-end gap-3">
                                <button type="button" @click="showModal = false" class="btn bg-gray-100 text-gray-600 hover:bg-gray-200 font-bold text-xs uppercase tracking-wider px-6 h-11 rounded-xl transition-all">Batal</button>
                                <button type="submit" class="btn btn-primary font-bold text-xs uppercase tracking-wider px-6 h-11 rounded-xl shadow-lg shadow-indigo-200 hover:translate-y-[-2px] transition-all">
                                    <i class="ri-save-3-line mr-2"></i> Simpan Data
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        HTML;
    }
}
