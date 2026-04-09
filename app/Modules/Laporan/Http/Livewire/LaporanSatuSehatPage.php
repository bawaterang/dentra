<?php

namespace App\Modules\Laporan\Http\Livewire;

use App\Models\TrxPendaftaran;
use App\Models\TrxSatusehatStatus;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class LaporanSatuSehatPage extends Component
{
    use WithPagination;

    public $periodType = 'DAILY'; // DAILY, MONTHLY, YEARLY

    public $selectedDate;
    public $selectedBulan;
    public $selectedTahun;
    public $search = '';

    public $availableYears = [];
    public $listBulan = [];

    public $listPeriodType = [
        ['value' => 'DAILY', 'label' => 'HARIAN', 'icon' => 'ri-calendar-event-line text-blue-500'],
        ['value' => 'MONTHLY', 'label' => 'BULANAN', 'icon' => 'ri-calendar-2-line text-indigo-500'],
        ['value' => 'YEARLY', 'label' => 'TAHUNAN', 'icon' => 'ri-calendar-todo-line text-purple-500'],
    ];

    public $selectedKunjungan = [];
    public $selectAll = false;

    protected $queryString = ['periodType', 'selectedDate', 'selectedBulan', 'selectedTahun', 'search'];

    public function mount()
    {
        $this->selectedDate = date('Y-m-d');
        $this->selectedBulan = (int) date('n');
        $this->selectedTahun = (int) date('Y');
        $this->loadAvailableYears();
        $this->loadListBulan();
    }

    public function loadListBulan()
    {
        $this->listBulan = [
            ['value' => 1, 'label' => 'Januari', 'icon' => 'ri-calendar-line text-blue-500'],
            ['value' => 2, 'label' => 'Februari', 'icon' => 'ri-calendar-line text-indigo-500'],
            ['value' => 3, 'label' => 'Maret', 'icon' => 'ri-calendar-line text-purple-500'],
            ['value' => 4, 'label' => 'April', 'icon' => 'ri-calendar-line text-pink-500'],
            ['value' => 5, 'label' => 'Mei', 'icon' => 'ri-calendar-line text-cyan-500'],
            ['value' => 6, 'label' => 'Juni', 'icon' => 'ri-calendar-line text-teal-500'],
            ['value' => 7, 'label' => 'Juli', 'icon' => 'ri-calendar-line text-green-500'],
            ['value' => 8, 'label' => 'Agustus', 'icon' => 'ri-calendar-line text-lime-500'],
            ['value' => 9, 'label' => 'September', 'icon' => 'ri-calendar-line text-yellow-500'],
            ['value' => 10, 'label' => 'Oktober', 'icon' => 'ri-calendar-line text-orange-500'],
            ['value' => 11, 'label' => 'November', 'icon' => 'ri-calendar-line text-red-500'],
            ['value' => 12, 'label' => 'Desember', 'icon' => 'ri-calendar-line text-rose-500'],
        ];
    }

    public function loadAvailableYears()
    {
        // User requested year fetched from trx_diagnosis
        $years = DB::table('trx_diagnosis')
            ->selectRaw('YEAR(created_at) as year')
            ->whereNotNull('created_at')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        if (empty($years)) {
            $years = [(int) date('Y')];
        }

        $this->availableYears = $years;
    }

    #[Computed]
    public function laporanSatuSehat()
    {
        $query = TrxPendaftaran::with(['pasien', 'poli', 'asuransi'])
            ->leftJoin('trx_satusehat_status', 'trx_pendaftaran.nomor_kunjungan', '=', 'trx_satusehat_status.nomor_kunjungan')
            ->select('trx_pendaftaran.*', 'trx_satusehat_status.status_bundle')
            ->whereNotNull('trx_pendaftaran.created_at');

        if ($this->periodType === 'DAILY') {
            $query->whereDate('trx_pendaftaran.created_at', $this->selectedDate);
        } elseif ($this->periodType === 'MONTHLY') {
            $query->whereMonth('trx_pendaftaran.created_at', $this->selectedBulan)
                ->whereYear('trx_pendaftaran.created_at', $this->selectedTahun);
        } elseif ($this->periodType === 'YEARLY') {
            $query->whereYear('trx_pendaftaran.created_at', $this->selectedTahun);
        }

        if (!empty($this->search)) {
            $query->whereHas('pasien', function ($q) {
                $q->where('nama_pasien', 'like', '%' . $this->search . '%')
                  ->orWhere('no_rm', 'like', '%' . $this->search . '%')
                  ->orWhere('nik', 'like', '%' . $this->search . '%');
            });
        }

        return $query->orderBy('trx_pendaftaran.created_at', 'desc')->paginate(20);
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedKunjungan = $this->laporanSatuSehat->pluck('nomor_kunjungan')->map(fn($id) => (string) $id)->toArray();
        } else {
            $this->selectedKunjungan = [];
        }
    }

    public function updatedSelectedKunjungan()
    {
        $this->selectAll = count($this->selectedKunjungan) === $this->laporanSatuSehat->count();
    }

    public function updatedPeriodType()
    {
        $this->resetPage();
        $this->resetCheckboxes();
    }

    public function updatedSelectedDate()
    {
        $this->resetPage();
        $this->resetCheckboxes();
    }

    public function updatedSelectedBulan()
    {
        $this->resetPage();
        $this->resetCheckboxes();
    }

    public function updatedSelectedTahun()
    {
        $this->resetPage();
        $this->resetCheckboxes();
    }

    public function updatedSearch()
    {
        $this->resetPage();
        $this->resetCheckboxes();
    }

    private function resetCheckboxes()
    {
        $this->selectedKunjungan = [];
        $this->selectAll = false;
    }

    public function kirim($nomor_kunjungan)
    {
        // Dummy function as requested
        $this->dispatch('refresh-component');
        session()->flash('message', 'Fitur kirim belum diaktifkan.');
    }

    public function kirimSemua()
    {
        // Dummy function as requested
        if (count($this->selectedKunjungan) > 0) {
            session()->flash('message', 'Fitur kirim batch belum diaktifkan (' . count($this->selectedKunjungan) . ' draft).');
            $this->resetCheckboxes();
        }
    }

    public function render()
    {
        return <<<'HTML'
        <div>
            <style>
                .glass-header {
                    background: rgba(255, 255, 255, 0.8) !important;
                    backdrop-filter: blur(8px);
                    -webkit-backdrop-filter: blur(8px);
                }
                .laporan-row:hover {
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
                    background-color: #767070ff !important;
                    color: #eaecefff !important;
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
                    box-shadow: 0 4px 10px rgba(64, 81, 137, 0.3) !important;
                    z-index: 10 !important;
                }
                
                .checkbox-custom {
                    appearance: none;
                    background-color: #fff;
                    margin: 0;
                    font: inherit;
                    color: currentColor;
                    width: 1.15em;
                    height: 1.15em;
                    border: 1.5px solid #cbd5e1;
                    border-radius: 0.25em;
                    display: grid;
                    place-content: center;
                    transition: all 0.2s;
                    cursor: pointer;
                }

                .checkbox-custom::before {
                    content: "";
                    width: 0.65em;
                    height: 0.65em;
                    transform: scale(0);
                    transition: 120ms transform ease-in-out;
                    box-shadow: inset 1em 1em white;
                    transform-origin: center;
                    clip-path: polygon(14% 44%, 0 65%, 50% 100%, 100% 16%, 80% 0%, 43% 62%);
                }

                .checkbox-custom:checked {
                    background-color: #405189;
                    border-color: #405189;
                }

                .checkbox-custom:checked::before {
                    transform: scale(1);
                }
            </style>

            <div class="page-header">
                <div class="page-header-title">
                    <div class="page-header-icon bg-gradient-to-br from-[#10b981] to-[#047857] text-white shadow-lg animate-pulse" style="animation-duration: 3s;">
                        <i class="ri-heart-pulse-fill"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold tracking-tight text-[#2c3e50]">Laporan Satu Sehat</h1>
                        <p class="text-xs text-[#878a99] font-medium mt-0.5">Rekapitulasi status bridging dan pengiriman pasien ke portal Satu Sehat.</p>
                    </div>
                </div>
                <div class="page-header-breadcrumb">
                    <a href="/dashboard" wire:navigate class="hover:text-[#405189] transition-colors"><i class="ri-home-4-line"></i></a>
                    <span class="sep text-gray-300">/</span>
                    <span class="text-gray-400 font-medium">Laporan</span>
                    <span class="sep text-gray-300">/</span>
                    <span class="text-[#10b981] font-bold">Satu Sehat</span>
                </div>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 mb-12 relative">
                @if (session()->has('message'))
                    <div class="bg-amber-50 text-amber-600 border border-amber-200 px-4 py-3 rounded-t-3xl sm:px-6 relative text-sm font-bold flex items-center gap-2">
                        <i class="ri-information-line text-lg"></i> {{ session('message') }}
                    </div>
                @endif
                <div class="p-4 sm:p-6 border-b border-gray-50 flex flex-col lg:flex-row justify-between items-stretch lg:items-center gap-4 sm:gap-6 glass-header sticky top-0 z-20 rounded-t-3xl">
                    <div class="grid grid-cols-2 lg:flex lg:items-end gap-3 sm:gap-4 w-full lg:w-auto">
                        <div class="space-y-1 col-span-2 lg:w-40 min-w-0">
                            <label class="text-[9px] font-black text-gray-400 uppercase tracking-widest px-1">Periode</label>
                            <x-custom-dropdown 
                                model="periodType" 
                                :options="$listPeriodType"
                                placeholder="Pilih Periode"
                                live="true"
                            />
                        </div>

                        @if($periodType === 'DAILY')
                        <div class="space-y-1 col-span-2 lg:w-44 min-w-0">
                            <label class="text-[9px] font-black text-gray-400 uppercase tracking-widest px-1">Tanggal</label>
                            <input type="date" wire:model.live="selectedDate" class="w-full bg-gray-50 border border-gray-100 rounded-xl py-2 px-4 text-sm font-bold text-[#2c3e50] focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-[#405189] transition-all outline-none">
                        </div>
                        @elseif($periodType === 'MONTHLY')
                        <div class="space-y-1 col-span-1 lg:w-40 min-w-0">
                            <label class="text-[9px] font-black text-gray-400 uppercase tracking-widest px-1">Bulan</label>
                            <x-custom-dropdown 
                                model="selectedBulan" 
                                :options="$listBulan"
                                placeholder="Pilih Bulan"
                                live="true"
                            />
                        </div>
                        <div class="space-y-1 col-span-1 lg:w-32 min-w-0">
                            <label class="text-[9px] font-black text-gray-400 uppercase tracking-widest px-1">Tahun</label>
                            <select wire:model.live="selectedTahun" class="w-full bg-gray-50 border border-gray-100 rounded-xl py-2.5 px-4 text-sm font-bold text-[#2c3e50] focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-[#405189] transition-all outline-none">
                                @foreach($availableYears as $year)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>
                        @elseif($periodType === 'YEARLY')
                        <div class="space-y-1 col-span-2 lg:w-32 min-w-0">
                            <label class="text-[9px] font-black text-gray-400 uppercase tracking-widest px-1">Tahun</label>
                            <select wire:model.live="selectedTahun" class="w-full bg-gray-50 border border-gray-100 rounded-xl py-2.5 px-4 text-sm font-bold text-[#2c3e50] focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-[#405189] transition-all outline-none">
                                @foreach($availableYears as $year)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                    </div>

                    <div class="flex flex-col sm:flex-row lg:items-end gap-4 w-full lg:w-auto">
                        <div class="relative flex-grow lg:min-w-[280px]">
                            <label class="lg:hidden text-[9px] font-black text-gray-400 uppercase tracking-widest px-1 block mb-1">Cari Pasien</label>
                            <div class="relative">
                                <i class="ri-search-2-line absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                                <input type="text" wire:model.live.debounce.300ms="search" 
                                       class="w-full bg-gray-50/50 border border-gray-200 rounded-2xl py-2.5 pl-12 pr-4 text-sm font-medium outline-none transition-all search-focus-glow placeholder:text-gray-300" 
                                       placeholder="Cari nama pasien, no rm, NIK...">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 sm:flex sm:items-center gap-3 w-full lg:w-auto lg:p-1 lg:rounded-lg lg:border lg:border-[#e2e8f0] lg:bg-white">
                            <a href="{{ route('laporan.satu-sehat.print', ['periodType' => $periodType, 'selectedDate' => $selectedDate, 'selectedBulan' => $selectedBulan, 'selectedTahun' => $selectedTahun, 'search' => $search]) }}" target="_blank" 
                               class="flex flex-row items-center justify-center gap-2 p-3 lg:p-0 lg:h-8 lg:w-8 rounded-xl lg:rounded-md bg-white border border-gray-100 lg:border-none shadow-sm lg:shadow-none hover:bg-indigo-50 transition-all group/print" title="Cetak PDF">
                                <i class="ri-printer-line text-lg text-indigo-500 group-hover/print:scale-110 transition-transform"></i>
                                <span class="lg:hidden text-[11px] font-bold text-gray-600">Cetak PDF</span>
                            </a>
                            <div class="hidden lg:block w-[1px] h-4 bg-[#e2e8f0]"></div>
                            <a href="{{ route('laporan.satu-sehat.export', ['periodType' => $periodType, 'selectedDate' => $selectedDate, 'selectedBulan' => $selectedBulan, 'selectedTahun' => $selectedTahun, 'search' => $search]) }}" target="_blank" 
                               class="flex flex-row items-center justify-center gap-2 p-3 lg:p-0 lg:h-8 lg:w-8 rounded-xl lg:rounded-md bg-white border border-gray-100 lg:border-none shadow-sm lg:shadow-none hover:bg-emerald-50 transition-all group/export" title="Unduh Excel">
                                <i class="ri-file-excel-2-line text-lg text-emerald-500 group-hover/export:scale-110 transition-transform"></i>
                                <span class="lg:hidden text-[11px] font-bold text-gray-600">Excel</span>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50">
                                <th class="px-6 py-4 border-b border-gray-50 w-[50px] text-center">
                                    <input type="checkbox" wire:model.live="selectAll" class="checkbox-custom mx-auto">
                                </th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">No</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Kunjungan & Tgl Periksa</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Nama Pasien & Info</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">NIK</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Status Bridging</th>
                                <th class="px-6 py-4 border-b border-gray-50 w-[120px]">
                                    <div class="flex flex-col items-center gap-1.5">
                                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">AKSI</span>
                                        <button wire:click="kirimSemua" class="px-3 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 border border-emerald-200 rounded-lg text-[9px] font-bold uppercase transition-all shadow-sm group">
                                            <i class="ri-send-plane-fill mr-1 group-hover:translate-x-0.5 transition-transform"></i> Kirim Semua
                                        </button>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($this->laporanSatuSehat as $index => $item)
                            <tr wire:key="kunjungan-{{ $item->nomor_kunjungan }}" class="laporan-row transition-all duration-200">
                                <td class="px-6 py-4 text-center">
                                    <input type="checkbox" wire:model.live="selectedKunjungan" value="{{ $item->nomor_kunjungan }}" class="checkbox-custom mx-auto">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-bold text-gray-400">{{ $this->laporanSatuSehat->firstItem() + $index }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-[#405189] text-xs">
                                        {{ $item->nomor_kunjungan }}
                                    </div>
                                    <div class="text-[10px] font-medium text-gray-500 mt-1">
                                        <i class="ri-calendar-event-line"></i> {{ $item->created_at ? $item->created_at->format('d/m/Y H:i') : '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-[#2c3e50] text-sm group-hover:text-[#405189] transition-colors">
                                        {{ $item->pasien ? $item->pasien->nama_pasien : '-' }}
                                    </div>
                                    <div class="mt-1 flex flex-wrap gap-1.5">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-gray-100 text-gray-600 border border-gray-200 uppercase">
                                            RM: {{ $item->pasien ? $item->pasien->no_rm : '-' }}
                                        </span>
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-indigo-50 text-indigo-600 border border-indigo-100 uppercase">
                                            {{ $item->poli ? $item->poli->nama_poli : '-' }}
                                        </span>
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-rose-50 text-rose-600 border border-rose-100 uppercase">
                                            {{ $item->asuransi ? $item->asuransi->nama_asuransi : 'PRIBADI' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-mono text-xs text-gray-600 font-bold">
                                        {{ $item->pasien && $item->pasien->nik ? $item->pasien->nik : '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    @php
                                        $status = $item->status_bundle ?? 'Pending';
                                    @endphp
                                    @if($status === 'Success' || $status === 'Berhasil')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-600 border border-emerald-100 text-[10px] font-bold uppercase tracking-wider">
                                            <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></div> {{ $status }}
                                        </span>
                                    @elseif($status === 'Pending')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-50 text-amber-600 border border-amber-100 text-[10px] font-bold uppercase tracking-wider">
                                            <div class="w-1.5 h-1.5 rounded-full bg-amber-500"></div> {{ $status }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-red-50 text-red-600 border border-red-100 text-[10px] font-bold uppercase tracking-wider">
                                            <div class="w-1.5 h-1.5 rounded-full bg-red-500"></div> {{ $status }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center">
                                        <button wire:click="kirim('{{ $item->nomor_kunjungan }}')" class="action-btn-soft bg-emerald-50 text-emerald-600 hover:bg-emerald-100 shadow-sm" title="Kirim ke Satu Sehat">
                                            <i class="ri-send-plane-fill text-sm"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="py-20 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center mb-6 animate-bounce" style="animation-duration: 4s;">
                                            <i class="ri-heart-pulse-line text-5xl text-gray-200"></i>
                                        </div>
                                        <p class="text-lg font-black text-gray-400">Belum Ada Riwayat Bridging</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($this->laporanSatuSehat->hasPages())
                <div class="px-6 py-5 sm:px-8 sm:py-6 bg-gray-50/50 border-t border-gray-100 pagination-custom rounded-b-3xl">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-5">
                        <div class="text-[11px] font-bold text-[#878a99] tracking-tight text-center sm:text-left">
                            <i class="ri-list-check-2 text-[#405189] mr-1 hidden sm:inline"></i>
                            <span class="hidden sm:inline">Menampilkan</span> 
                            <span class="text-[#405189] font-black">{{ $this->laporanSatuSehat->firstItem() ?: 0 }} - {{ $this->laporanSatuSehat->lastItem() ?: 0 }}</span> 
                            dari <span class="text-[#405189] font-black">{{ number_format($this->laporanSatuSehat->total()) }}</span> 
                            <span class="hidden sm:inline">data</span>
                            <span class="sm:hidden">total data</span>
                        </div>
                        {{ $this->laporanSatuSehat->links() }}
                    </div>
                </div>
                @endif
            </div>
        </div>
        HTML;
    }
}
