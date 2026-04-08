<?php

namespace App\Modules\Laporan\Http\Livewire;

use App\Models\MstDokter;
use App\Models\TrxPendaftaran;
use App\Models\TrxTindakan;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class LaporanKunjunganPage extends Component
{
    use WithPagination;

    public $periodType = 'DAILY'; // DAILY, MONTHLY, YEARLY

    public $selectedDate;

    public $selectedBulan;

    public $selectedTahun;

    public $selectedDokter = 'all';

    public $selectedPasienId;
    public $showRiwayatModal = false;
    public $pasienHistoryData = [];
    public $currentPasien;
    public $latestOdontogramState = [];
    public $dentalCategories = [];

    public $search = '';

    public $expandedRows = [];

    public $availableYears = [];

    public $dokterList = [];

    public $listBulan = [];

    public $listPeriodType = [
        ['value' => 'DAILY', 'label' => 'HARIAN', 'icon' => 'ri-calendar-event-line text-blue-500'],
        ['value' => 'MONTHLY', 'label' => 'BULANAN', 'icon' => 'ri-calendar-2-line text-indigo-500'],
        ['value' => 'YEARLY', 'label' => 'TAHUNAN', 'icon' => 'ri-calendar-todo-line text-purple-500'],
    ];

    protected $queryString = ['periodType', 'selectedDate', 'selectedBulan', 'selectedTahun', 'selectedDokter', 'search'];

    public function mount()
    {
        $this->selectedDate = date('Y-m-d');
        $this->selectedBulan = (int) date('n');
        $this->selectedTahun = (int) date('Y');
        $this->loadAvailableYears();
        $this->loadDokterList();
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
    public function laporanKunjungan()
    {
        $query = TrxPendaftaran::with([
            'pasien',
            'dokter' => fn($q) => $q->withTrashed(),
            'asuransi',
            'billing'
        ])
            ->whereNotNull('created_at');

        if ($this->periodType === 'DAILY') {
            $query->whereDate('created_at', $this->selectedDate);
        } elseif ($this->periodType === 'MONTHLY') {
            $query->whereMonth('created_at', $this->selectedBulan)
                ->whereYear('created_at', $this->selectedTahun);
        } elseif ($this->periodType === 'YEARLY') {
            $query->whereYear('created_at', $this->selectedTahun);
        }

        if ($this->selectedDokter !== 'all') {
            $query->where('dokter_id', $this->selectedDokter);
        }

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('nomor_kunjungan', 'like', '%' . $this->search . '%')
                    ->orWhereHas('pasien', function ($pq) {
                        $pq->where('nama_pasien', 'like', '%' . $this->search . '%')
                            ->orWhere('no_rm', 'like', '%' . $this->search . '%');
                    });
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate(10);
    }

    public function getClinicalDetails($nomorKunjungan)
    {
        $pendaftaran = TrxPendaftaran::where('nomor_kunjungan', $nomorKunjungan)->first();

        $soap = DB::table('trx_pemeriksaan')->where('nomor_kunjungan', $nomorKunjungan)->first();

        $diagnoses = DB::table('trx_diagnosis')
            ->join('mst_diagnosis', 'trx_diagnosis.kode_diagnosa', '=', 'mst_diagnosis.kode_diagnosa')
            ->where('trx_diagnosis.nomor_kunjungan', $nomorKunjungan)
            ->whereNull('trx_diagnosis.deleted_at')
            ->select('mst_diagnosis.nama_diagnosa', 'trx_diagnosis.kode_diagnosa', 'trx_diagnosis.jenis_icd', 'trx_diagnosis.kasus_icd')
            ->get();

        $obat = DB::table('trx_obat')
            ->join('mst_obat', 'trx_obat.kode_obat', '=', 'mst_obat.kode_obat')
            ->where('trx_obat.nomor_kunjungan', $nomorKunjungan)
            ->whereNull('trx_obat.deleted_at')
            ->select('mst_obat.nama_obat', 'trx_obat.dosis', 'trx_obat.aturan')
            ->get();

        $ohis = DB::table('trx_ohis')
            ->where('nomor_kunjungan', $nomorKunjungan)
            ->whereNull('deleted_at')
            ->first();

        $odontogram_visit = DB::table('trx_odontogram')
            ->leftJoin('mst_kategori_gigi', 'trx_odontogram.kode_kategori', '=', 'mst_kategori_gigi.kode_kategori')
            ->where('trx_odontogram.nomor_kunjungan', $nomorKunjungan)
            ->whereNull('trx_odontogram.deleted_at')
            ->select('trx_odontogram.nomor_gigi', 'trx_odontogram.bagian', 'mst_kategori_gigi.nama_kategori', 'trx_odontogram.warna')
            ->get();

        return [
            'pemeriksaan_awal' => [
                'kesadaran' => $pendaftaran->kesadaran,
                'td' => $pendaftaran->tekanan_darah,
                'nadi' => $pendaftaran->nadi,
                'suhu' => $pendaftaran->suhu,
                'bb' => $pendaftaran->berat_badan,
                'tb' => $pendaftaran->tinggi_badan,
                'riwayat' => $pendaftaran->riwayat_penyakit,
                'alergi' => $pendaftaran->alergi,
            ],
            'soap' => $soap,
            'diagnoses' => $diagnoses,
            'obat' => $obat,
            'ohis' => $ohis,
            'odontogram_visit' => $odontogram_visit,
        ];
    }

    public function openRiwayatModal($pasienId)
    {
        $this->selectedPasienId = $pasienId;
        $this->currentPasien = \App\Models\MstPasien::find($pasienId);

        $history = TrxPendaftaran::with(['dokter', 'asuransi', 'billing'])
            ->where('pasien_id', $pasienId)
            ->whereNotNull('created_at')
            ->orderBy('created_at', 'desc')
            ->get();

        $this->pasienHistoryData = [];
        foreach ($history as $item) {
            $this->pasienHistoryData[] = [
                'pendaftaran' => $item,
                'clinical' => $this->getClinicalDetails($item->nomor_kunjungan)
            ];
        }

        // Get latest odontogram state for the patient
        $odontogram = DB::table('trx_odontogram')
            ->where('pasien_id', $pasienId)
            ->whereNull('deleted_at')
            ->get();

        $this->latestOdontogramState = [];
        foreach ($odontogram as $o) {
            $this->latestOdontogramState[$o->nomor_gigi . '-' . $o->bagian] = [
                'color' => $o->warna,
                'kategori' => $o->kode_kategori
            ];
        }

        // Fetch dental categories for legend
        $this->dentalCategories = \App\Models\MstKategoriGigi::where('status', 'Aktif')
            ->whereNull('deleted_at')
            ->orderBy('nama_kategori', 'asc')
            ->get();

        $this->showRiwayatModal = true;
    }

    public function closeRiwayatModal()
    {
        $this->showRiwayatModal = false;
        $this->selectedPasienId = null;
        $this->pasienHistoryData = [];
        $this->currentPasien = null;
    }

    public function toggleDetail($nomorKunjungan)
    {
        if (isset($this->expandedRows[$nomorKunjungan])) {
            unset($this->expandedRows[$nomorKunjungan]);
        } else {
            $this->expandedRows[$nomorKunjungan] = true;
        }
    }

    public function updatedPeriodType()
    {
        $this->resetPage();
    }

    public function updatedSelectedDate()
    {
        $this->resetPage();
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
                .detail-row {
                    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%) !important;
                }
                .card-clinical {
                    background: white;
                    border-radius: 1rem;
                    border: 1px solid #e2e8f0;
                    border-left: 4px solid #405189;
                    padding: 1.25rem;
                    height: 100%;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
                }
                .clinical-title {
                    font-size: 0.7rem;
                    font-weight: 800;
                    color: #405189;
                    text-transform: uppercase;
                    letter-spacing: 0.05em;
                    margin-bottom: 0.75rem;
                    display: flex;
                    align-items: center;
                    gap: 0.5rem;
                }
                .vital-badge {
                    display: flex;
                    flex-direction: column;
                    padding: 0.5rem;
                    border-radius: 0.5rem;
                    background: #f8fafc;
                    border: 1px solid #e2e8f0;
                }
                .vital-label {
                    font-size: 0.6rem;
                    color: #878a99;
                    font-weight: 700;
                    text-transform: uppercase;
                }
                .vital-value {
                    font-size: 0.8rem;
                    font-weight: 800;
                    color: #2c3e50;
                }
            </style>

            <div class="page-header">
                <div class="page-header-title">
                    <div class="page-header-icon bg-gradient-to-br from-[#405189] to-[#2a3a6a] text-white shadow-lg animate-pulse" style="animation-duration: 3s;">
                        <i class="ri-walk-line"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold tracking-tight text-[#2c3e50]">Laporan Kunjungan Pasien</h1>
                        <p class="text-xs text-[#878a99] font-medium mt-0.5">Rekapitulasi kunjungan dan riwayat pemeriksaan pasien.</p>
                    </div>
                </div>
                <div class="page-header-breadcrumb">
                    <a href="/dashboard" wire:navigate class="hover:text-[#405189] transition-colors"><i class="ri-home-4-line"></i></a>
                    <span class="sep text-gray-300">/</span>
                    <span class="text-gray-400 font-medium">Laporan</span>
                    <span class="sep text-gray-300">/</span>
                    <span class="text-[#405189] font-bold">Kunjungan</span>
                </div>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden mb-12">
                <div class="p-4 sm:p-6 border-b border-gray-50 flex flex-col lg:flex-row justify-between items-stretch lg:items-center gap-4 sm:gap-6 glass-header sticky top-0 z-20">
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

                        <div class="space-y-1 col-span-2 lg:w-64 xl:w-72 min-w-0">
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
                            <a href="{{ route('laporan.kunjungan.print', ['periodType' => $periodType, 'selectedDate' => $selectedDate, 'selectedBulan' => $selectedBulan, 'selectedTahun' => $selectedTahun, 'selectedDokter' => $selectedDokter]) }}" target="_blank" 
                               class="flex flex-row items-center justify-center gap-2 p-3 lg:p-0 lg:h-8 lg:w-8 rounded-xl lg:rounded-md bg-white border border-gray-100 lg:border-none shadow-sm lg:shadow-none hover:bg-indigo-50 transition-all group/print" title="Cetak PDF">
                                <i class="ri-printer-line text-lg text-indigo-500 group-hover/print:scale-110 transition-transform"></i>
                                <span class="lg:hidden text-[11px] font-bold text-gray-600">Cetak PDF</span>
                            </a>
                            <div class="hidden lg:block w-[1px] h-4 bg-[#e2e8f0]"></div>
                            <a href="{{ route('laporan.kunjungan.export', ['periodType' => $periodType, 'selectedDate' => $selectedDate, 'selectedBulan' => $selectedBulan, 'selectedTahun' => $selectedTahun, 'selectedDokter' => $selectedDokter]) }}" target="_blank" 
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
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Dokter</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 text-center">Riwayat & Detail</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($this->laporanKunjungan as $index => $item)
                            @php
                                $isExpanded = isset($this->expandedRows[$item->nomor_kunjungan]);
                            @endphp
                            <tr wire:key="laporan-{{ $item->id }}" class="laporan-row transition-all duration-200">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-bold text-gray-400">{{ $this->laporanKunjungan->firstItem() + $index }}</span>
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
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-blue-50 text-blue-600 text-[10px] font-bold">
                                                <i class="ri-calendar-check-line"></i> {{ $item->created_at ? $item->created_at->format('d/m/Y') : '-' }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600">
                                            <i class="ri-user-star-line"></i>
                                        </div>
                                        <span class="text-sm font-bold text-gray-700">{{ $item->dokter ? $item->dokter->nama_dokter : '-' }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button wire:click="openRiwayatModal('{{ $item->pasien_id }}')" class="action-btn-soft bg-blue-50 text-blue-600 hover:bg-blue-100 shadow-sm" title="Riwayat Kunjungan">
                                            <i class="ri-history-line text-sm"></i>
                                        </button>
                                        <button wire:click="toggleDetail('{{ $item->nomor_kunjungan }}')" 
                                                class="action-btn-soft {{ $isExpanded ? 'bg-emerald-100 text-emerald-600' : 'bg-gray-100 text-gray-500 hover:bg-emerald-50 hover:text-emerald-600' }} shadow-sm" 
                                                title="{{ $isExpanded ? 'Sembunyikan Detail' : 'Lihat Detail Pemeriksaan' }}">
                                            <i class="ri-{{ $isExpanded ? 'arrow-up-s-line' : 'arrow-down-s-line' }} text-sm"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @if($isExpanded)
                            <tr wire:key="detail-{{ $item->nomor_kunjungan }}" class="detail-row">
                                <td colspan="5" class="px-6 py-8">
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                        @php
                                            $details = $this->getClinicalDetails($item->nomor_kunjungan);
                                        @endphp
                                        
                                        <!-- Pemeriksaan Awal -->
                                        <div class="card-clinical">
                                            <div class="clinical-title"><i class="ri-heart-pulse-line"></i> Pemeriksaan Awal</div>
                                            <div class="grid grid-cols-2 gap-3">
                                                <div class="vital-badge">
                                                    <span class="vital-label">Kesadaran</span>
                                                    <span class="vital-value">{{ $details['pemeriksaan_awal']['kesadaran'] ?: '-' }}</span>
                                                </div>
                                                <div class="vital-badge">
                                                    <span class="vital-label">TD (mmHg)</span>
                                                    <span class="vital-value">{{ $details['pemeriksaan_awal']['td'] ?: '-' }}</span>
                                                </div>
                                                <div class="vital-badge">
                                                    <span class="vital-label">Nadi (x/mnt)</span>
                                                    <span class="vital-value">{{ $details['pemeriksaan_awal']['nadi'] ?: '-' }}</span>
                                                </div>
                                                <div class="vital-badge">
                                                    <span class="vital-label">Suhu (°C)</span>
                                                    <span class="vital-value">{{ $details['pemeriksaan_awal']['suhu'] ?: '-' }}</span>
                                                </div>
                                                <div class="vital-badge">
                                                    <span class="vital-label">BB (kg)</span>
                                                    <span class="vital-value">{{ $details['pemeriksaan_awal']['bb'] ?: '-' }}</span>
                                                </div>
                                                <div class="vital-badge">
                                                    <span class="vital-label">TB (cm)</span>
                                                    <span class="vital-value">{{ $details['pemeriksaan_awal']['tb'] ?: '-' }}</span>
                                                </div>
                                            </div>
                                            <div class="mt-4 space-y-2">
                                                <div class="p-3 bg-red-50 rounded-lg border border-red-100">
                                                    <span class="text-[10px] font-bold text-red-600 uppercase tracking-wider block">Alergi</span>
                                                    <p class="text-xs text-red-700 font-medium">{{ $details['pemeriksaan_awal']['alergi'] ?: 'Tidak ada' }}</p>
                                                </div>
                                                <div class="p-3 bg-amber-50 rounded-lg border border-amber-100">
                                                    <span class="text-[10px] font-bold text-amber-600 uppercase tracking-wider block">Riwayat Penyakit</span>
                                                    <p class="text-xs text-amber-700 font-medium">{{ $details['pemeriksaan_awal']['riwayat'] ?: 'Tidak ada' }}</p>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Clinical Notes (SOPA) -->
                                        <div class="card-clinical">
                                            <div class="clinical-title"><i class="ri-file-list-3-line"></i> Clinical Notes (SOPA)</div>
                                            <div class="space-y-3">
                                                @foreach(['subjective' => 'S', 'objective' => 'O', 'assessment' => 'A', 'planning' => 'P'] as $key => $label)
                                                <div class="flex gap-3">
                                                    <div class="w-6 h-6 rounded bg-gray-100 flex items-center justify-center text-[10px] font-black text-gray-500 shrink-0">{{ $label }}</div>
                                                    <p class="text-xs text-gray-600 leading-relaxed">{{ $details['soap']->$key ?? '-' }}</p>
                                                </div>
                                                @endforeach
                                            </div>
                                        </div>

                                        <!-- Diagnosis & Obat -->
                                        <div class="card-clinical">
                                            <div class="clinical-title"><i class="ri-microscope-line"></i> Diagnosis & Tindakan</div>
                                            @forelse($details['diagnoses'] as $diag)
                                            <div class="mb-3 p-3 bg-white rounded-xl border border-gray-100 shadow-sm">
                                                <div class="flex justify-between items-start mb-1">
                                                    <span class="text-[10px] font-black text-indigo-500 font-mono">{{ $diag->kode_diagnosa }}</span>
                                                    <span class="text-[9px] px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-600 font-bold uppercase">{{ $diag->jenis_icd }}</span>
                                                </div>
                                                <p class="text-xs font-bold text-gray-700 leading-tight">{{ $diag->nama_diagnosa }}</p>
                                            </div>
                                            @empty
                                            <div class="text-center py-4 text-gray-400 text-xs italic">Tidak ada diagnosis</div>
                                            @endforelse

                                            @if(count($details['odontogram_visit']) > 0)
                                            <div class="clinical-title mt-6"><i class="ri-tooth-line"></i> Gigi Diperiksa</div>
                                            <div class="grid grid-cols-2 gap-2">
                                                @foreach($details['odontogram_visit'] as $gv)
                                                <div class="flex items-center gap-2 p-2 bg-orange-50 rounded-lg border border-orange-100">
                                                    <div class="w-3 h-3 rounded-sm border border-black/10 shrink-0" style="background-color: {{ $gv->warna ?: '#ccc' }}"></div>
                                                    <div class="flex flex-col overflow-hidden">
                                                        <span class="text-[10px] font-black text-orange-700 leading-none">Gigi {{ $gv->nomor_gigi }} ({{ $gv->bagian }})</span>
                                                        <span class="text-[9px] font-bold text-orange-600 leading-none truncate mt-0.5">{{ $gv->nama_kategori ?: '-' }}</span>
                                                    </div>
                                                </div>
                                                @endforeach
                                            </div>
                                            @endif

                                            <div class="clinical-title mt-6"><i class="ri-capsule-line"></i> Resep Obat</div>
                                            <div class="space-y-2">
                                                @forelse($details['obat'] as $o)
                                                <div class="flex items-center gap-3 p-2 bg-emerald-50/50 rounded-lg border border-emerald-100">
                                                    <div class="w-8 h-8 rounded bg-emerald-100 flex items-center justify-center text-emerald-600 text-xs">
                                                        <i class="ri-medicine-bottle-line"></i>
                                                    </div>
                                                    <div class="flex-1">
                                                        <p class="text-xs font-bold text-gray-700">{{ $o->nama_obat }}</p>
                                                        <div class="flex items-center gap-2">
                                                            <span class="text-[10px] text-emerald-600 font-bold">{{ $o->dosis }}</span>
                                                            <span class="text-[10px] text-gray-400">|</span>
                                                            <span class="text-[10px] text-gray-500">{{ $o->aturan }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                @empty
                                                <div class="text-center py-4 text-gray-400 text-xs italic">Tidak ada resep</div>
                                                @endforelse
                                            </div>
                                        </div>

                                        <!-- OHI-S Value -->
                                        @if($details['ohis'] && $details['ohis']->ohis_total !== null)
                                        <div class="card-clinical lg:col-span-3" style="border-left-color: #7c3aed;">
                                            <div class="clinical-title" style="color: #7c3aed;"><i class="ri-tooth-line"></i> Nilai OHI-S</div>
                                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                                <div class="flex flex-col items-center p-4 bg-blue-50 rounded-2xl border border-blue-100">
                                                    <span class="text-[10px] font-bold text-blue-500 uppercase tracking-widest mb-1">Total DI</span>
                                                    <span class="text-2xl font-black text-blue-700">{{ $details['ohis']->di_total }}</span>
                                                </div>
                                                <div class="flex flex-col items-center p-4 bg-indigo-50 rounded-2xl border border-indigo-100">
                                                    <span class="text-[10px] font-bold text-indigo-500 uppercase tracking-widest mb-1">Total CI</span>
                                                    <span class="text-2xl font-black text-indigo-700">{{ $details['ohis']->ci_total }}</span>
                                                </div>
                                                <div class="flex flex-col items-center p-4 bg-purple-50 rounded-2xl border border-purple-100">
                                                    <span class="text-[10px] font-bold text-purple-500 uppercase tracking-widest mb-1">Skor OHI-S</span>
                                                    <span class="text-2xl font-black text-purple-700">{{ $details['ohis']->ohis_total }}</span>
                                                </div>
                                                <div class="flex flex-col items-center justify-center p-4 rounded-2xl border 
                                                    @if($details['ohis']->kategori == 'Baik') bg-emerald-50 border-emerald-100 text-emerald-700 
                                                    @elseif($details['ohis']->kategori == 'Sedang') bg-amber-50 border-amber-100 text-amber-700 
                                                    @else bg-red-50 border-red-100 text-red-700 @endif">
                                                    <span class="text-[10px] font-black uppercase tracking-widest mb-1">Kategori</span>
                                                    <span class="text-xl font-black">{{ strtoupper($details['ohis']->kategori) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endif
                            @empty
                            <tr>
                                <td colspan="5" class="py-20 text-center">
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
                        </tbody>
                    </table>
                </div>

                @if($this->laporanKunjungan->hasPages())
                <div class="px-6 py-5 sm:px-8 sm:py-6 bg-gray-50/50 border-t border-gray-100 pagination-custom">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-5">
                        <div class="text-[11px] font-bold text-[#878a99] tracking-tight text-center sm:text-left">
                            <i class="ri-list-check-2 text-[#405189] mr-1 hidden sm:inline"></i>
                            <span class="hidden sm:inline">Menampilkan</span> 
                            <span class="text-[#405189] font-black">{{ $this->laporanKunjungan->firstItem() ?: 0 }} - {{ $this->laporanKunjungan->lastItem() ?: 0 }}</span> 
                            dari <span class="text-[#405189] font-black">{{ number_format($this->laporanKunjungan->total()) }}</span> 
                            <span class="hidden sm:inline">data</span>
                            <span class="sm:hidden">total data</span>
                        </div>
                        {{ $this->laporanKunjungan->links() }}
                    </div>
                </div>
                @endif
            </div>

            <!-- Modal Riwayat Pasien -->
            @if($showRiwayatModal)
            <div class="fixed inset-0 z-[1050] flex items-center justify-center p-4 sm:p-6 lg:p-8" x-data="{ show: @entangle('showRiwayatModal') }" x-show="show" x-cloak>
                <!-- Overlay -->
                <div class="fixed inset-0 transition-opacity bg-black/60 shadow-2xl backdrop-blur-sm" wire:click="closeRiwayatModal" x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"></div>
                
                <!-- Modal Box -->
                <div class="relative inline-block w-full max-w-6xl max-h-full overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-3xl z-10 flex flex-col"
                     x-show="show" x-transition:enter="ease-out duration-400" x-transition:enter-start="opacity-0 translate-y-8 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100">

                        
                        <!-- Modal Header -->
                        <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-indigo-700 flex items-center justify-between border-b border-white/10">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center text-white text-2xl shadow-inner">
                                    <i class="ri-history-line"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-black text-white leading-none">Riwayat Rekam Medis</h3>
                                    <p class="text-xs text-blue-100 font-bold mt-1 opacity-80 uppercase tracking-widest">{{ $currentPasien->nama_pasien }} ({{ $currentPasien->no_rm }})</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <a href="{{ route('laporan.kunjungan.print-riwayat', $selectedPasienId) }}" target="_blank"
                                   class="hidden sm:flex items-center gap-2 bg-white/20 hover:bg-white text-white hover:text-indigo-700 px-4 py-2 rounded-xl text-xs font-black transition-all shadow-lg border border-white/10">
                                    <i class="ri-printer-line"></i> CETAK RIWAYAT
                                </a>
                                <button wire:click="closeRiwayatModal" class="text-white/60 hover:text-white transition-colors">
                                    <i class="ri-close-circle-fill text-3xl"></i>
                                </button>
                            </div>
                        </div>

                        <div class="p-6 max-h-[75vh] overflow-y-auto custom-scrollbar bg-gray-50/30">
                            <!-- Basic Patient Info Cards for Mobile -->
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8 sm:hidden">
                                <div class="p-3 bg-white rounded-2xl border border-gray-100 shadow-sm">
                                    <span class="text-[9px] font-black text-gray-400 uppercase">Pasien</span>
                                    <p class="text-xs font-bold text-gray-700">{{ $currentPasien->nama_pasien }}</p>
                                </div>
                                <div class="p-3 bg-white rounded-2xl border border-gray-100 shadow-sm">
                                    <span class="text-[9px] font-black text-gray-400 uppercase">RM</span>
                                    <p class="text-xs font-bold text-gray-700">{{ $currentPasien->no_rm }}</p>
                                </div>
                            </div>

                            <div class="space-y-6">
                                <!-- History Timeline / Table -->
                                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                                    <div class="px-5 py-4 border-b border-gray-50 flex items-center justify-between bg-gray-50/50">
                                        <h4 class="text-sm font-black text-[#405189] flex items-center gap-2">
                                            <i class="ri-calendar-todo-line"></i> TIMELINE KUNJUNGAN
                                        </h4>
                                        <span class="px-3 py-1 rounded-full bg-blue-50 text-blue-600 text-[10px] font-black uppercase">{{ count($pasienHistoryData) }} Total Kunjungan</span>
                                    </div>
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-left border-collapse min-w-[1000px]">
                                            <thead>
                                                <tr class="bg-gray-50/30">
                                                    <th class="px-5 py-3 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Tgl & Dokter</th>
                                                    <th class="px-5 py-3 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Pemeriksaan Awal (Vitals)</th>
                                                    <th class="px-5 py-3 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Notes (SOAP)</th>
                                                    <th class="px-5 py-3 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Diagnosis & Tindakan</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-50">
                                                @foreach($pasienHistoryData as $history)
                                                <tr class="hover:bg-gray-50/50 transition-colors">
                                                    <td class="px-5 py-4 align-top">
                                                        <div class="font-black text-[#2c3e50] text-[13px] leading-tight">{{ date('d M Y', strtotime($history['pendaftaran']->created_at)) }}</div>
                                                        <div class="flex items-center gap-1.5 mt-1 text-gray-500 text-[11px] font-bold">
                                                            <i class="ri-user-star-line text-indigo-400"></i>
                                                            {{ $history['pendaftaran']->dokter->nama_dokter ?? '-' }}
                                                        </div>
                                                        <div class="mt-2 font-mono text-[10px] font-bold text-indigo-400 bg-indigo-50 px-2 py-0.5 rounded-md inline-block">{{ $history['pendaftaran']->nomor_kunjungan }}</div>
                                                    </td>
                                                    <td class="px-5 py-4 align-top">
                                                        <div class="grid grid-cols-2 gap-x-4 gap-y-1.5">
                                                            <div class="flex items-center gap-2">
                                                                <span class="text-[10px] font-bold text-gray-400 w-8">TD</span>
                                                                <span class="text-[11px] font-black text-gray-700">{{ $history['clinical']['pemeriksaan_awal']['td'] ?: '-' }}</span>
                                                            </div>
                                                            <div class="flex items-center gap-2">
                                                                <span class="text-[10px] font-bold text-gray-400 w-8">SUHU</span>
                                                                <span class="text-[11px] font-black text-gray-700">{{ $history['clinical']['pemeriksaan_awal']['suhu'] ?: '-' }}°C</span>
                                                            </div>
                                                            <div class="flex items-center gap-2">
                                                                <span class="text-[10px] font-bold text-gray-400 w-8">NADI</span>
                                                                <span class="text-[11px] font-black text-gray-700">{{ $history['clinical']['pemeriksaan_awal']['nadi'] ?: '-' }}</span>
                                                            </div>
                                                            <div class="flex items-center gap-2">
                                                                <span class="text-[10px] font-bold text-gray-400 w-8">BB</span>
                                                                <span class="text-[11px] font-black text-gray-700">{{ $history['clinical']['pemeriksaan_awal']['bb'] ?: '-' }} kg</span>
                                                            </div>
                                                        </div>
                                                        @if($history['clinical']['pemeriksaan_awal']['alergi'])
                                                        <div class="mt-3 p-2 bg-red-50 rounded-lg border border-red-100 flex items-start gap-2 max-w-[200px]">
                                                            <i class="ri-error-warning-fill text-red-500 text-xs mt-0.5"></i>
                                                            <div>
                                                                <span class="text-[9px] font-black text-red-600 uppercase block leading-none">Alergi</span>
                                                                <p class="text-[10px] font-bold text-red-700 leading-tight">{{ $history['clinical']['pemeriksaan_awal']['alergi'] }}</p>
                                                            </div>
                                                        </div>
                                                        @endif
                                                    </td>
                                                    <td class="px-5 py-4 align-top max-w-[300px]">
                                                        <div class="space-y-2">
                                                            @foreach(['subjective' => 'S', 'objective' => 'O', 'assessment' => 'A', 'planning' => 'P'] as $key => $label)
                                                            <div class="flex gap-2">
                                                                <div class="w-5 h-5 rounded-md bg-gray-100 flex items-center justify-center text-[10px] font-bold text-gray-500 shrink-0">{{ $label }}</div>
                                                                <p class="text-[11px] font-medium text-gray-600 leading-relaxed">{{ $history['clinical']['soap']->$key ?? '-' }}</p>
                                                            </div>
                                                            @endforeach
                                                        </div>
                                                    </td>
                                                    <td class="px-5 py-4 align-top">
                                                        <div class="space-y-3">
                                                            <div>
                                                                <div class="text-[9px] font-black text-indigo-500 uppercase flex items-center gap-1 mb-1.5"><i class="ri-microscope-line"></i> Diagnosis</div>
                                                                <div class="flex flex-wrap gap-1.5">
                                                                    @forelse($history['clinical']['diagnoses'] as $diag)
                                                                    <div class="px-2 py-1 bg-indigo-50 rounded-lg border border-indigo-100">
                                                                        <span class="text-[10px] font-black text-indigo-600 block">{{ $diag->kode_diagnosa }}</span>
                                                                        <span class="text-[10px] font-bold text-gray-600 leading-none">{{ $diag->nama_diagnosa }}</span>
                                                                    </div>
                                                                    @empty
                                                                    <span class="text-[10px] font-bold text-gray-400 italic">Tidak ada diagnosis</span>
                                                                    @endforelse
                                                                </div>
                                                            </div>
                                                            @if(count($history['clinical']['odontogram_visit']) > 0)
                                                            <div>
                                                                <div class="text-[9px] font-black text-orange-500 uppercase flex items-center gap-1 mb-1.5"><i class="ri-tooth-line"></i> Gigi Diperiksa</div>
                                                                <div class="flex flex-wrap gap-1.5">
                                                                    @foreach($history['clinical']['odontogram_visit'] as $gv)
                                                                    <div class="px-2 py-1 bg-orange-50 rounded-lg border border-orange-100 flex items-center gap-2">
                                                                        <div class="w-3 h-3 rounded-sm border border-black/10" style="background-color: {{ $gv->warna ?: '#ccc' }}"></div>
                                                                        <div class="flex flex-col">
                                                                            <span class="text-[10px] font-black text-orange-700">Gigi {{ $gv->nomor_gigi }} ({{ $gv->bagian }})</span>
                                                                            <span class="text-[9px] font-bold text-orange-600 leading-none">{{ $gv->nama_kategori ?: '-' }}</span>
                                                                        </div>
                                                                    </div>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                            @endif
                                                            <div>
                                                                <div class="text-[9px] font-black text-emerald-500 uppercase flex items-center gap-1 mb-1.5"><i class="ri-capsule-line"></i> Obat / Resep</div>
                                                                <div class="space-y-1">
                                                                    @forelse($history['clinical']['obat'] as $o)
                                                                    <div class="text-[11px] font-bold text-gray-700">• {{ $o->nama_obat }} <span class="text-gray-400 font-medium">({{ $o->dosis }})</span></div>
                                                                    @empty
                                                                    <span class="text-[10px] font-bold text-gray-400 italic">Tidak ada resep</span>
                                                                    @endforelse
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Odontogram and OHI-S in horizontal layout -->
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 pb-8">
                                    <!-- Odontogram Overview -->
                                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex flex-col h-full">
                                        <div class="px-5 py-4 border-b border-gray-50 flex items-center justify-between bg-gray-50/50">
                                            <h4 class="text-sm font-black text-[#405189] flex items-center gap-2">
                                                <i class="ri-tooth-line"></i> STATUS ODONTOGRAM TERKINI
                                            </h4>
                                        </div>
                                        <div class="p-6 flex-grow flex items-center justify-center bg-white">
                                            <div class="scale-[0.8] origin-center">
                                                <div class="flex flex-col gap-6 select-none" style="min-width: 600px;">
                                                    @php
                                                        $toothRows = [
                                                            'AdultTop' => [[18,17,16,15,14,13,12,11], [21,22,23,24,25,26,27,28]],
                                                            'ChildTop' => [[55,54,53,52,51], [61,62,63,64,65]],
                                                            'ChildBot' => [[85,84,83,82,81], [71,72,73,74,75]],
                                                            'AdultBot' => [[48,47,46,45,44,43,42,41], [31,32,33,34,35,36,37,38]]
                                                        ];
                                                    @endphp

                                                    @foreach($toothRows as $type => $halves)
                                                    <div class="flex justify-center gap-4 {{ str_contains($type, 'Child') ? 'opacity-80 scale-90' : '' }}">
                                                        @foreach($halves as $teeth)
                                                        <div class="flex gap-1.5">
                                                            @foreach($teeth as $t)
                                                            <div class="flex flex-col items-center gap-1 {{ str_contains($type, 'Bot') ? 'flex-col-reverse' : '' }}">
                                                                <span class="text-[9px] font-black {{ str_contains($type, 'Child') ? 'text-gray-300' : 'text-gray-400' }}">{{ $t }}</span>
                                                                <div class="w-6 h-6 lg:w-8 lg:h-8 drop-shadow-sm">
                                                                    <svg viewBox="0 0 40 40" class="w-full h-full overflow-visible">
                                                                        <path :fill="'{{ $latestOdontogramState[$t.'-T']['color'] ?? 'white' }}'" d="M0,0 L40,0 L30,10 L10,10 Z" stroke="#e2e8f0" stroke-width="1.5"></path>
                                                                        <path :fill="'{{ $latestOdontogramState[$t.'-R']['color'] ?? 'white' }}'" d="M40,0 L40,40 L30,30 L30,10 Z" stroke="#e2e8f0" stroke-width="1.5"></path>
                                                                        <path :fill="'{{ $latestOdontogramState[$t.'-B']['color'] ?? 'white' }}'" d="M40,40 L0,40 L10,30 L30,30 Z" stroke="#e2e8f0" stroke-width="1.5"></path>
                                                                        <path :fill="'{{ $latestOdontogramState[$t.'-L']['color'] ?? 'white' }}'" d="M0,0 L10,10 L10,30 L0,40 Z" stroke="#e2e8f0" stroke-width="1.5"></path>
                                                                        <path :fill="'{{ $latestOdontogramState[$t.'-C']['color'] ?? 'white' }}'" d="M10,10 L30,10 L30,30 L10,30 Z" stroke="#e2e8f0" stroke-width="1.5"></path>
                                                                    </svg>
                                                                </div>
                                                            </div>
                                                            @endforeach
                                                        </div>
                                                        @endforeach
                                                    </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Color Legend -->
                                        <div class="px-5 py-4 border-t border-gray-50 bg-gray-50/30">
                                            <div class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-3 text-center">LEGENDA KONDISI GIGI</div>
                                            <div class="flex flex-wrap justify-center gap-x-6 gap-y-2">
                                                @foreach($dentalCategories as $cat)
                                                <div class="flex items-center gap-2">
                                                    <div class="w-2.5 h-2.5 rounded-sm border border-black/10 shrink-0" style="background-color: {{ $cat->warna }}"></div>
                                                    <span class="text-[10px] font-bold text-gray-600 uppercase">{{ $cat->nama_kategori }}</span>
                                                </div>
                                                @endforeach
                                                <div class="flex items-center gap-2">
                                                    <div class="w-2.5 h-2.5 rounded-sm border border-gray-200 bg-white shadow-inner shrink-0"></div>
                                                    <span class="text-[10px] font-bold text-gray-600 uppercase">NORMAL</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- latest OHI-S Stats -->
                                    @php
                                        $latestOhis = $pasienHistoryData[0]['clinical']['ohis'] ?? null;
                                    @endphp
                                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm flex flex-col h-full">
                                        <div class="px-5 py-4 border-b border-gray-50 flex items-center justify-between bg-gray-50/50">
                                            <h4 class="text-sm font-black text-purple-700 flex items-center gap-2">
                                                <i class="ri-health-book-line"></i> KESIMPULAN OHI-S (KUNJUNGAN TERAKHIR)
                                            </h4>
                                        </div>
                                        <div class="p-6 flex-grow flex flex-col justify-center gap-6">
                                            @if($latestOhis)
                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="p-5 bg-blue-50/50 rounded-2xl border border-blue-100 text-center">
                                                    <span class="text-[10px] font-black text-blue-500 uppercase tracking-widest block mb-2">Total Debris Index</span>
                                                    <span class="text-3xl font-black text-blue-700">{{ $latestOhis->di_total }}</span>
                                                </div>
                                                <div class="p-5 bg-indigo-50/50 rounded-2xl border border-indigo-100 text-center">
                                                    <span class="text-[10px] font-black text-indigo-500 uppercase tracking-widest block mb-2">Total Calculus Index</span>
                                                    <span class="text-3xl font-black text-indigo-700">{{ $latestOhis->ci_total }}</span>
                                                </div>
                                                <div class="p-5 bg-purple-50 rounded-2xl border border-purple-100 border-2 text-center col-span-2">
                                                    <span class="text-[10px] font-black text-purple-500 uppercase tracking-widest block mb-2">SKOR OHI-S KESSELURUHAN</span>
                                                    <div class="flex items-center justify-center gap-6">
                                                        <span class="text-5xl font-black text-purple-700">{{ $latestOhis->ohis_total }}</span>
                                                        <div class="h-10 w-[1px] bg-purple-200"></div>
                                                        <div class="flex flex-col items-start">
                                                            <span class="text-[10px] font-black text-purple-400 uppercase tracking-widest">KATEGORI</span>
                                                            <span class="text-xl font-black 
                                                                @if($latestOhis->kategori == 'Baik') text-emerald-600 
                                                                @elseif($latestOhis->kategori == 'Sedang') text-amber-600 
                                                                @else text-red-600 @endif">{{ strtoupper($latestOhis->kategori) }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            @else
                                            <div class="text-center py-10">
                                                <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
                                                    <i class="ri-health-book-line text-3xl text-gray-200"></i>
                                                </div>
                                                <p class="text-sm font-bold text-gray-400 italic">Belum ada data OHI-S tercatat</p>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="px-6 py-4 bg-gray-50/80 border-t border-gray-100 flex items-center justify-between sm:justify-start gap-4">
                            
                            <a href="{{ route('laporan.kunjungan.print-riwayat', $selectedPasienId) }}" target="_blank"
                               class="sm:hidden flex flex-grow items-center justify-center gap-2 bg-indigo-600 text-white px-6 py-2.5 rounded-xl text-xs font-black transition-all shadow-lg active:scale-95">
                                <i class="ri-printer-line"></i> CETAK RIWAYAT
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
        HTML;
    }
}
