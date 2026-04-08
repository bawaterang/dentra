<?php

namespace App\Modules\Laporan\Http\Livewire;

use App\Models\MstDokter;
use App\Models\TrxBilling;
use App\Models\TrxPendaftaran;
use App\Models\TrxTindakan;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class LaporanJasaMedisPage extends Component
{
    use WithPagination;

    public $selectedBulan;

    public $selectedTahun;

    public $selectedDokter = 'all';

    public $search = '';

    public $expandedRows = [];

    public $availableYears = [];

    public $dokterList = [];

    public $listBulan = [];

    public $grandTotalBiaya = 0;

    public $grandTotalJasaMedis = 0;

    public $grandTotalBhp = 0;

    protected $queryString = ['selectedBulan', 'selectedTahun', 'selectedDokter', 'search'];

    public function mount()
    {
        $this->selectedBulan = (int) date('n');
        $this->selectedTahun = (int) date('Y');
        $this->loadAvailableYears();
        $this->loadDokterList();
        $this->loadListBulan();
    }

    public function loadListBulan()
    {
        $bulanList = [
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
        $this->listBulan = $bulanList;
    }

    public function loadAvailableYears()
    {
        $years = TrxPendaftaran::selectRaw('YEAR(created_at) as year')
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

    public function loadDokterList()
    {
        $dokters = MstDokter::withTrashed()
            ->orderBy('nama_dokter')
            ->get()
            ->map(function ($d) {
                return [
                    'value' => $d->id,
                    'label' => $d->nama_dokter,
                    'icon' => 'ri-user-star-line text-blue-500',
                ];
            })
            ->toArray();

        array_unshift($dokters, [
            'value' => 'all',
            'label' => 'Semua Dokter',
            'icon' => 'ri-group-line text-gray-500',
        ]);

        $this->dokterList = $dokters;
    }

    #[Computed]
    public function laporanJasaMedis()
    {
        $query = TrxPendaftaran::with([
            'pasien', 
            'dokter' => fn($q) => $q->withTrashed(), 
            'asuransi', 
            'billing'
        ])
            ->whereNotNull('created_at')
            ->whereMonth('created_at', $this->selectedBulan)
            ->whereYear('created_at', $this->selectedTahun);

        if ($this->selectedDokter !== 'all') {
            $query->where('dokter_id', $this->selectedDokter);
        }

        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('nomor_kunjungan', 'like', '%'.$this->search.'%')
                    ->orWhereHas('pasien', function ($pq) {
                        $pq->where('nama_pasien', 'like', '%'.$this->search.'%')
                            ->orWhere('no_rm', 'like', '%'.$this->search.'%');
                    });
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate(10);
    }

    public function getTindakanDetails($nomorKunjungan)
    {
        return TrxTindakan::withoutTrashed()->where('nomor_kunjungan', $nomorKunjungan)
            ->with('tindakan')
            ->get()
            ->map(function ($item) {
                $nominalJasaMedis = $this->calculateNominalJasaMedis($item->jasa_medis, $item->biaya, $item->satuan);

                return [
                    'nama_tindakan' => $item->tindakan ? $item->tindakan->nama_tindakan : $item->kode_tindakan,
                    'kode_tindakan' => $item->kode_tindakan,
                    'jasa_medis_nominal' => $nominalJasaMedis,
                    'jasa_medis_raw' => $item->jasa_medis,
                    'satuan' => $item->satuan,
                    'biaya' => $item->biaya,
                    'bhp' => $item->bhp,
                ];
            })
            ->toArray();
    }

    public function calculateNominalJasaMedis($jasaMedis, $biaya, $satuan)
    {
        $satuanLower = strtolower($satuan ?? '');
        if (in_array($satuanLower, ['rp', 'rupiah'])) {
            return (float) $jasaMedis;
        }

        return (float) ($jasaMedis * $biaya / 100);
    }

    public function getTotalsByKunjungan($nomorKunjungan)
    {
        $tindakans = TrxTindakan::withoutTrashed()->where('nomor_kunjungan', $nomorKunjungan)->get();

        $totalJasaMedisNominal = 0;
        $totalBhp = 0;

        foreach ($tindakans as $tindakan) {
            $totalJasaMedisNominal += $this->calculateNominalJasaMedis($tindakan->jasa_medis, $tindakan->biaya, $tindakan->satuan);
            $totalBhp += (float) $tindakan->bhp;
        }

        $billing = TrxBilling::withoutTrashed()->where('nomor_kunjungan', $nomorKunjungan)->first();
        $totalTagihan = $billing ? $billing->total_tagihan : 0;

        return [
            'jasa_medis' => $totalJasaMedisNominal,
            'bhp' => $totalBhp,
            'total_tagihan' => $totalTagihan,
        ];
    }

    public function toggleDetail($nomorKunjungan)
    {
        if (isset($this->expandedRows[$nomorKunjungan])) {
            unset($this->expandedRows[$nomorKunjungan]);
        } else {
            $this->expandedRows[$nomorKunjungan] = true;
        }
    }

    public function updatedSelectedBulan()
    {
        $this->resetPage();
    }

    public function updatedSelectedTahun()
    {
        $this->resetPage();
    }

    public function updatedSelectedDokter()
    {
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $this->grandTotalJasaMedis = 0;
        $this->grandTotalBhp = 0;
        $this->grandTotalBiaya = 0;

        foreach ($this->laporanJasaMedis as $item) {
            $totals = $this->getTotalsByKunjungan($item->nomor_kunjungan);
            $this->grandTotalJasaMedis += $totals['jasa_medis'];
            $this->grandTotalBhp += $totals['bhp'];
            $this->grandTotalBiaya += $totals['total_tagihan'];
        }

        return <<<'HTML'
        <div x-data="{ showModal: false, init(){this.$watch('showModal',v=>{if(v){$nextTick(()=>{this.$refs.firstInput&&this.$refs.firstInput.focus()})}})} }" @open-modal.window="showModal=true" @close-modal.window="showModal=false" x-init="init()">
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
                .detail-row {
                    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%) !important;
                }
                .tindakan-item {
                    border-left: 3px solid #405189;
                    transition: all 0.2s ease;
                }
                .tindakan-item:hover {
                    border-left-color: #0ab39c;
                }
                .grand-total-row {
                    background: linear-gradient(135deg, #405189 0%, #2a3a6a 100%) !important;
                }
                .grand-total-row td {
                    color: white !important;
                }
                .grand-total-row .font-bold {
                    color: white !important;
                }
            </style>

            <div class="page-header">
                <div class="page-header-title">
                    <div class="page-header-icon bg-gradient-to-br from-[#405189] to-[#2a3a6a] text-white shadow-lg animate-pulse" style="animation-duration: 3s;">
                        <i class="ri-file-chart-line"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold tracking-tight text-[#2c3e50]">Laporan Jasa Medis</h1>
                        <p class="text-xs text-[#878a99] font-medium mt-0.5">Rekapitulasi jasa medis dan BHP per periode.</p>
                    </div>
                </div>
                <div class="page-header-breadcrumb">
                    <a href="/dashboard" wire:navigate class="hover:text-[#405189] transition-colors"><i class="ri-home-4-line"></i></a>
                    <span class="sep text-gray-300">/</span>
                    <span class="text-gray-400 font-medium">Laporan</span>
                    <span class="sep text-gray-300">/</span>
                    <span class="text-[#405189] font-bold">Jasa Medis</span>
                </div>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden mb-12">
                <div class="p-4 sm:p-6 border-b border-gray-50 flex flex-col lg:flex-row justify-between items-stretch lg:items-center gap-4 sm:gap-6 glass-header sticky top-0 z-20">
                    <div class="grid grid-cols-2 lg:flex lg:items-end gap-3 sm:gap-4 w-full lg:w-auto">
                        <div class="space-y-1 col-span-2 lg:w-32 xl:w-40 min-w-0">
                            <label class="text-[9px] font-black text-gray-400 uppercase tracking-widest px-1">Bulan</label>
                            <x-custom-dropdown 
                                model="selectedBulan" 
                                :options="$listBulan"
                                placeholder="Pilih Bulan"
                                live="true"
                            />
                        </div>
                        <div class="space-y-1 col-span-2 lg:w-32 xl:w-40 min-w-0">
                            <label class="text-[9px] font-black text-gray-400 uppercase tracking-widest px-1">Tahun</label>
                            <select wire:model.live="selectedTahun" class="w-full bg-gray-50 border border-gray-100 rounded-xl py-2.5 px-4 text-sm font-bold text-[#2c3e50] focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-[#405189] transition-all outline-none">
                                @foreach($availableYears as $year)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="space-y-1 col-span-2 lg:w-64 xl:w-80 min-w-0">
                            <label class="text-[9px] font-black text-gray-400 uppercase tracking-widest px-1">Dokter</label>
                            <x-custom-dropdown 
                                model="selectedDokter" 
                                :options="$dokterList"
                                placeholder="Semua Dokter"
                                live="true"
                                searchable="true"
                            />
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row lg:items-end gap-4 w-full lg:w-auto">
                        <div class="relative flex-grow lg:min-w-[280px]">
                            <label class="lg:hidden text-[9px] font-black text-gray-400 uppercase tracking-widest px-1 block mb-1">Cari Pasien</label>
                            <div class="relative">
                                <i class="ri-search-2-line absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                                <input type="text" wire:model.live.debounce.300ms="search" 
                                       class="w-full bg-gray-50/50 border border-gray-200 rounded-2xl py-2.5 pl-12 pr-4 text-sm font-medium outline-none transition-all search-focus-glow placeholder:text-gray-300" 
                                       placeholder="Cari nomor RM atau nama pasien...">
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 sm:flex sm:items-center gap-3 w-full lg:w-auto lg:p-1 lg:rounded-lg lg:border lg:border-[#e2e8f0] lg:bg-white">
                            <a href="{{ route('laporan.jasamedis.print', ['bulan' => $selectedBulan, 'tahun' => $selectedTahun, 'dokter' => $selectedDokter]) }}" target="_blank" 
                               class="flex flex-row items-center justify-center gap-2 p-3 lg:p-0 lg:h-8 lg:w-8 rounded-xl lg:rounded-md bg-white border border-gray-100 lg:border-none shadow-sm lg:shadow-none hover:bg-indigo-50 transition-all group/print" title="Cetak PDF">
                                <i class="ri-printer-line text-lg text-indigo-500 group-hover/print:scale-110 transition-transform"></i>
                                <span class="lg:hidden text-[11px] font-bold text-gray-600">Cetak PDF</span>
                            </a>
                            <div class="hidden lg:block w-[1px] h-4 bg-[#e2e8f0]"></div>
                            <a href="{{ route('laporan.jasamedis.export', ['bulan' => $selectedBulan, 'tahun' => $selectedTahun, 'dokter' => $selectedDokter]) }}" target="_blank" 
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
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">No</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">No. Kunjungan</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Pasien</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Tgl Periksa</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 text-right">Biaya Pemeriksaan</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 text-right">Jasa Medis</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 text-right">BHP</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 text-center">Detail</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($this->laporanJasaMedis as $index => $item)
                            @php
                                $totals = $this->getTotalsByKunjungan($item->nomor_kunjungan);
                                $isExpanded = isset($this->expandedRows[$item->nomor_kunjungan]);
                            @endphp
                            <tr wire:key="laporan-{{ $item->id }}" class="laporan-row transition-all duration-200">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-bold text-gray-400">{{ $this->laporanJasaMedis->firstItem() + $index }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-mono font-bold text-[#405189] text-sm">{{ $item->nomor_kunjungan }}</span>
                                </td>
                                <td class="px-6 py-4 min-w-[280px]">
                                    <div class="group">
                                        <div class="font-bold text-[#2c3e50] text-sm group-hover:text-[#405189] transition-colors line-clamp-1">
                                            {{ $item->pasien ? $item->pasien->nama_pasien : '-' }}
                                        </div>
                                        <div class="flex items-center gap-3 mt-1.5 flex-wrap">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-gray-100 text-gray-600 text-[10px] font-bold">
                                                <i class="ri-user-line"></i> RM: {{ $item->pasien ? $item->pasien->no_rm : '-' }}
                                            </span>
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-indigo-50 text-indigo-600 text-[10px] font-bold">
                                                <i class="ri-user-star-line"></i> {{ $item->dokter ? $item->dokter->nama_dokter : '-' }}
                                            </span>
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-blue-50 text-blue-600 text-[10px] font-bold">
                                                <i class="ri-shield-check-line"></i> {{ $item->asuransi ? $item->asuransi->nama_asuransi : 'Pribadi' }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-600">{{ $item->created_at ? $item->created_at->format('d/m/Y') : '-' }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <span class="font-black text-[#405189] text-sm">Rp {{ number_format($totals['total_tagihan'], 0, ',', '.') }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <span class="font-bold text-emerald-600">Rp {{ number_format($totals['jasa_medis'], 0, ',', '.') }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <span class="font-bold text-amber-600">Rp {{ number_format($totals['bhp'], 0, ',', '.') }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <button wire:click="toggleDetail('{{ $item->nomor_kunjungan }}')" 
                                            class="action-btn-soft {{ $isExpanded ? 'bg-emerald-100 text-emerald-600' : 'bg-gray-100 text-gray-500 hover:bg-emerald-50 hover:text-emerald-600' }} shadow-sm" 
                                            title="{{ $isExpanded ? 'Sembunyikan Detail' : 'Lihat Detail' }}">
                                        <i class="ri-{{ $isExpanded ? 'arrow-up-s-line' : 'arrow-down-s-line' }} text-sm"></i>
                                    </button>
                                </td>
                            </tr>
                            @if($isExpanded)
                            <tr wire:key="detail-{{ $item->nomor_kunjungan }}" class="detail-row">
                                <td colspan="8" class="px-6 py-4">
                                    <div class="space-y-2">
                                        <div class="flex items-center gap-2 mb-3">
                                            <i class="ri-list-check-2 text-[#405189]"></i>
                                            <span class="font-bold text-xs text-[#405189] uppercase tracking-wider">Daftar Tindakan</span>
                                        </div>
                                        @php
                                            $tindakanList = $this->getTindakanDetails($item->nomor_kunjungan);
                                        @endphp
                                        @forelse($tindakanList as $tindakan)
                                        <div class="flex items-center justify-between p-3 bg-white rounded-xl border border-gray-100 tindakan-item">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center">
                                                    <i class="ri-medicine-bottle-line text-[#405189]"></i>
                                                </div>
                                                <div>
                                                    <span class="font-bold text-sm text-[#2c3e50]">{{ $tindakan['nama_tindakan'] }}</span>
                                                    <div class="flex items-center gap-2 mt-1">
                                                        <span class="text-[10px] text-gray-400 font-mono">{{ $tindakan['kode_tindakan'] }}</span>
                                                        <span class="text-[10px] text-gray-400">|</span>
                                                        <span class="text-[10px] text-gray-500">Biaya: Rp {{ number_format($tindakan['biaya'], 0, ',', '.') }}</span>
                                                        @if(strtolower($tindakan['satuan']) == '%')
                                                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-purple-50 text-purple-600 font-bold">
                                                            {{ $tindakan['jasa_medis_raw'] }}% = Rp {{ number_format($tindakan['jasa_medis_nominal'], 0, ',', '.') }}
                                                        </span>
                                                        @else
                                                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-blue-50 text-blue-600 font-bold">
                                                            {{ $tindakan['satuan'] }} = Rp {{ number_format($tindakan['jasa_medis_nominal'], 0, ',', '.') }}
                                                        </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-6">
                                                <div class="text-right">
                                                    <span class="text-[9px] text-gray-400 uppercase tracking-wider">Jasa Medis</span>
                                                    <p class="font-bold text-emerald-600">Rp {{ number_format($tindakan['jasa_medis_nominal'], 0, ',', '.') }}</p>
                                                </div>
                                                <div class="text-right">
                                                    <span class="text-[9px] text-gray-400 uppercase tracking-wider">BHP</span>
                                                    <p class="font-bold text-amber-600">Rp {{ number_format($tindakan['bhp'], 0, ',', '.') }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        @empty
                                        <div class="text-center py-4 text-gray-400 text-sm">
                                            <i class="ri-file-list-3-line text-2xl mb-2"></i>
                                            <p>Tidak ada tindakan</p>
                                        </div>
                                        @endforelse
                                        <div class="flex items-center justify-end gap-6 pt-3 mt-3 border-t border-gray-200">
                                            <div class="text-right">
                                                <span class="text-[9px] text-gray-400 uppercase tracking-wider">Total Jasa Medis</span>
                                                <p class="text-lg font-black text-emerald-600">Rp {{ number_format($totals['jasa_medis'], 0, ',', '.') }}</p>
                                            </div>
                                            <div class="text-right">
                                                <span class="text-[9px] text-gray-400 uppercase tracking-wider">Total BHP</span>
                                                <p class="text-lg font-black text-amber-600">Rp {{ number_format($totals['bhp'], 0, ',', '.') }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @endif
                            @empty
                            <tr>
                                <td colspan="8" class="py-20 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-32 h-32 bg-gray-50 rounded-full flex items-center justify-center mb-6 animate-bounce" style="animation-duration: 4s;">
                                            <i class="ri-file-search-line text-6xl text-gray-200"></i>
                                        </div>
                                        <p class="text-xl font-black text-gray-400">Data Tidak Ditemukan</p>
                                        <p class="text-xs text-gray-300 mt-1 uppercase tracking-widest font-bold">Cobalah menyesuaikan filter periode Anda</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                            
                            @if($this->laporanJasaMedis->total() > 0)
                            <tr class="grand-total-row">
                                <td colspan="4" class="px-6 py-4">
                                    <span class="font-black text-sm uppercase tracking-wider">GRAND TOTAL</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="text-right">
                                        <span class="text-[9px] text-white/70 uppercase tracking-wider">Total Biaya</span>
                                        <p class="text-xl font-black text-white">Rp {{ number_format($this->grandTotalBiaya, 0, ',', '.') }}</p>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="text-right">
                                        <span class="text-[9px] text-white/70 uppercase tracking-wider">Total Jasa Medis</span>
                                        <p class="text-lg font-black text-emerald-300">Rp {{ number_format($this->grandTotalJasaMedis, 0, ',', '.') }}</p>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="text-right">
                                        <span class="text-[9px] text-white/70 uppercase tracking-wider">Total BHP</span>
                                        <p class="text-lg font-black text-amber-300">Rp {{ number_format($this->grandTotalBhp, 0, ',', '.') }}</p>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-white/20 text-white">
                                        <i class="ri-check-line"></i>
                                    </span>
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                @if($this->laporanJasaMedis->hasPages())
                <div class="px-6 py-5 sm:px-8 sm:py-6 bg-gray-50/50 border-t border-gray-100 pagination-custom">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-5">
                        <div class="text-[11px] font-bold text-[#878a99] tracking-tight text-center sm:text-left">
                            <i class="ri-list-check-2 text-[#405189] mr-1 hidden sm:inline"></i>
                            <span class="hidden sm:inline">Menampilkan</span> 
                            <span class="text-[#405189] font-black">{{ $this->laporanJasaMedis->firstItem() ?: 0 }} - {{ $this->laporanJasaMedis->lastItem() ?: 0 }}</span> 
                            dari <span class="text-[#405189] font-black">{{ number_format($this->laporanJasaMedis->total()) }}</span> 
                            <span class="hidden sm:inline">data</span>
                            <span class="sm:hidden">total data</span>
                        </div>
                        {{ $this->laporanJasaMedis->links() }}
                    </div>
                </div>
                @endif
            </div>
        </div>
        HTML;
    }
}
