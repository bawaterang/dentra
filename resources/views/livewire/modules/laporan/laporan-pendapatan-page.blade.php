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
                .summary-card-value {
                    font-size: 1.5rem;
                    font-weight: 900;
                    letter-spacing: -0.02em;
                }
            </style>

            <div class="page-header">
                <div class="page-header-title">
                    <div class="page-header-icon bg-gradient-to-br from-[#10b981] to-[#047857] text-white shadow-lg animate-pulse" style="animation-duration: 3s;">
                        <i class="ri-money-dollar-circle-line"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold tracking-tight text-[#2c3e50]">Laporan Pendapatan</h1>
                        <p class="text-xs text-[#878a99] font-medium mt-0.5">Rekapitulasi pendapatan, pengeluaran BHP, dan laba bersih.</p>
                    </div>
                </div>
                <div class="page-header-breadcrumb">
                    <a href="/dashboard" wire:navigate class="hover:text-[#405189] transition-colors"><i class="ri-home-4-line"></i></a>
                    <span class="sep text-gray-300">/</span>
                    <span class="text-gray-400 font-medium">Laporan</span>
                    <span class="sep text-gray-300">/</span>
                    <span class="text-[#405189] font-bold">Pendapatan</span>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <!-- Pendapatan -->
                <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm relative overflow-hidden group">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                        <i class="ri-wallet-3-fill text-6xl text-emerald-500"></i>
                    </div>
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-8 h-8 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                            <i class="ri-arrow-right-down-line text-lg"></i>
                        </div>
                        <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Pendapatan</h3>
                    </div>
                    <div class="summary-card-value text-[#2c3e50] mt-3">
                        Rp {{ number_format($this->summaryData['pendapatan'], 0, ',', '.') }}
                    </div>
                    <div class="text-xs text-emerald-600 font-semibold mt-1">Pembayaran dari pasien</div>
                </div>

                <!-- Pengeluaran BHP -->
                <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm relative overflow-hidden group">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                        <i class="ri-first-aid-kit-fill text-6xl text-amber-500"></i>
                    </div>
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-8 h-8 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600">
                            <i class="ri-arrow-right-up-line text-lg"></i>
                        </div>
                        <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Pengeluaran</h3>
                    </div>
                    <div class="summary-card-value text-[#2c3e50] mt-3">
                        Rp {{ number_format($this->summaryData['pengeluaran'], 0, ',', '.') }}
                    </div>
                    <div class="text-xs text-amber-600 font-semibold mt-1">BHP dari tindakan</div>
                </div>

                <!-- Piutang -->
                <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm relative overflow-hidden group">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                        <i class="ri-file-warning-fill text-6xl text-rose-500"></i>
                    </div>
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-8 h-8 rounded-xl bg-rose-50 flex items-center justify-center text-rose-600">
                            <i class="ri-time-line text-lg"></i>
                        </div>
                        <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Piutang</h3>
                    </div>
                    <div class="summary-card-value text-[#2c3e50] mt-3">
                        Rp {{ number_format($this->summaryData['piutang'], 0, ',', '.') }}
                    </div>
                    <div class="text-xs text-rose-600 font-semibold mt-1">Billing belum lunas</div>
                </div>

                <!-- Laba Bersih -->
                <div class="bg-gradient-to-br from-[#405189] to-[#2a3a6a] rounded-3xl p-5 border border-indigo-900 shadow-lg relative overflow-hidden group">
                    <div class="absolute top-0 right-0 p-4 opacity-20 group-hover:opacity-30 transition-opacity">
                        <i class="ri-safe-2-fill text-6xl text-white"></i>
                    </div>
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-8 h-8 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center text-white">
                            <i class="ri-funds-line text-lg"></i>
                        </div>
                        <h3 class="text-[10px] font-black text-indigo-200 uppercase tracking-widest">Laba Bersih</h3>
                    </div>
                    <div class="summary-card-value text-white mt-3">
                        Rp {{ number_format($this->summaryData['laba_bersih'], 0, ',', '.') }}
                    </div>
                    <div class="text-xs text-indigo-200 font-semibold mt-1">Pendapatan dikurangi pengeluaran</div>
                </div>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 mb-12 relative">
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
                            <label class="lg:hidden text-[9px] font-black text-gray-400 uppercase tracking-widest px-1 block mb-1">Cari Pasien / Faktur</label>
                            <div class="relative">
                                <i class="ri-search-2-line absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                                <input type="text" wire:model.live.debounce.300ms="search" 
                                       class="w-full bg-gray-50/50 border border-gray-200 rounded-2xl py-2.5 pl-12 pr-4 text-sm font-medium outline-none transition-all search-focus-glow placeholder:text-gray-300" 
                                       placeholder="RM, nama pasien, atau faktur...">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 sm:flex sm:items-center gap-3 w-full lg:w-auto lg:p-1 lg:rounded-lg lg:border lg:border-[#e2e8f0] lg:bg-white">
                            <a href="{{ route('laporan.pendapatan.print', ['periodType' => $periodType, 'selectedDate' => $selectedDate, 'selectedBulan' => $selectedBulan, 'selectedTahun' => $selectedTahun, 'search' => $search]) }}" target="_blank" 
                               class="flex flex-row items-center justify-center gap-2 p-3 lg:p-0 lg:h-8 lg:w-8 rounded-xl lg:rounded-md bg-white border border-gray-100 lg:border-none shadow-sm lg:shadow-none hover:bg-indigo-50 transition-all group/print" title="Cetak PDF">
                                <i class="ri-printer-line text-lg text-indigo-500 group-hover/print:scale-110 transition-transform"></i>
                                <span class="lg:hidden text-[11px] font-bold text-gray-600">Cetak PDF</span>
                            </a>
                            <div class="hidden lg:block w-[1px] h-4 bg-[#e2e8f0]"></div>
                            <a href="{{ route('laporan.pendapatan.export', ['periodType' => $periodType, 'selectedDate' => $selectedDate, 'selectedBulan' => $selectedBulan, 'selectedTahun' => $selectedTahun, 'search' => $search]) }}" target="_blank" 
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
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Faktur & Kunjungan</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Pasien</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 text-right">Pendapatan</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 text-right">Pengeluaran (BHP)</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 text-right">Piutang</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 text-center">Status</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 text-center">Detail</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($this->laporanPendapatan as $index => $item)
                            @php
                                $isExpanded = isset($this->expandedRows[$item->id]);
                                $pengeluaran = $this->getPengeluaranBhp($item->nomor_kunjungan);
                                $isLunas = $item->status === 'Lunas';
                            @endphp
                            <tr wire:key="laporan-{{ $item->id }}" class="laporan-row transition-all duration-200">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-bold text-gray-400">{{ $this->laporanPendapatan->firstItem() + $index }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-mono font-black text-[#405189] text-sm">{{ $item->no_faktur }}</div>
                                    <div class="text-[10px] text-gray-500 font-mono mt-1"><i class="ri-walk-line"></i> {{ $item->nomor_kunjungan }}</div>
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
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <span class="font-black text-emerald-600 text-sm">Rp {{ number_format($item->total_bayar, 0, ',', '.') }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <span class="font-black text-amber-600 text-sm">Rp {{ number_format($pengeluaran, 0, ',', '.') }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <span class="font-black {{ $item->hutang > 0 ? 'text-rose-600' : 'text-gray-400' }} text-sm">Rp {{ number_format($item->hutang, 0, ',', '.') }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider {{ $isLunas ? 'bg-emerald-50 text-emerald-600 border border-emerald-200' : 'bg-rose-50 text-rose-600 border border-rose-200' }}">
                                        {{ $item->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="flex justify-center">
                                        <button wire:click="toggleDetail({{ $item->id }})" 
                                                class="action-btn-soft {{ $isExpanded ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-100 text-gray-500 hover:bg-indigo-50 hover:text-indigo-600' }} shadow-sm" 
                                                title="{{ $isExpanded ? 'Sembunyikan Detail' : 'Lihat Detail' }}">
                                            <i class="ri-{{ $isExpanded ? 'arrow-up-s-line' : 'arrow-down-s-line' }} text-sm"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @if($isExpanded)
                            <tr wire:key="detail-{{ $item->id }}" class="detail-row">
                                <td colspan="8" class="px-6 py-8">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <!-- Detail Billing -->
                                        <div class="card-clinical">
                                            <div class="clinical-title"><i class="ri-file-invoice-line"></i> Detail Tagihan & Pembayaran</div>
                                            
                                            <div class="mb-4">
                                                <div class="flex justify-between items-center mb-1">
                                                    <span class="text-xs text-gray-500 font-semibold">Total Tagihan</span>
                                                    <span class="text-sm font-bold text-gray-700">Rp {{ number_format($item->total_tagihan, 0, ',', '.') }}</span>
                                                </div>
                                                <div class="flex justify-between items-center mb-1">
                                                    <span class="text-xs text-gray-500 font-semibold">Total Dibayar</span>
                                                    <span class="text-sm font-bold text-emerald-600">Rp {{ number_format($item->total_bayar, 0, ',', '.') }}</span>
                                                </div>
                                                @if($item->kembalian > 0)
                                                <div class="flex justify-between items-center mb-1">
                                                    <span class="text-xs text-gray-500 font-semibold">Kembalian</span>
                                                    <span class="text-sm font-bold text-gray-400">Rp {{ number_format($item->kembalian, 0, ',', '.') }}</span>
                                                </div>
                                                @endif
                                                @if($item->hutang > 0)
                                                <div class="flex justify-between items-center">
                                                    <span class="text-xs text-gray-500 font-semibold">Hutang</span>
                                                    <span class="text-sm font-bold text-rose-600">Rp {{ number_format($item->hutang, 0, ',', '.') }}</span>
                                                </div>
                                                @endif
                                            </div>

                                            <div class="space-y-2">
                                                <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 border-b border-gray-100 pb-1">Tindakan / Item</div>
                                                @forelse($item->details as $d)
                                                <div class="flex items-start justify-between gap-3 p-2 bg-indigo-50/50 rounded-lg border border-indigo-50">
                                                    <div>
                                                        <div class="text-xs font-bold text-indigo-900">{{ $d->nama_tindakan }}</div>
                                                        <div class="text-[10px] text-indigo-400 font-mono">{{ $d->kode_tindakan }}</div>
                                                    </div>
                                                    <div class="text-xs font-black text-indigo-600 whitespace-nowrap">
                                                        Rp {{ number_format($d->biaya, 0, ',', '.') }}
                                                    </div>
                                                </div>
                                                @empty
                                                <div class="text-center py-4 text-gray-400 text-xs italic">Tidak ada detail billing</div>
                                                @endforelse
                                            </div>
                                        </div>

                                        <!-- Detail Pengeluaran BHP -->
                                        <div class="card-clinical" style="border-left-color: #f59e0b;">
                                            <div class="clinical-title" style="color: #d97706;"><i class="ri-first-aid-kit-line"></i> Detail Pengeluaran BHP</div>
                                            
                                            <div class="mb-4">
                                                <div class="flex justify-between items-center">
                                                    <span class="text-xs text-gray-500 font-semibold">Total BHP</span>
                                                    <span class="text-sm font-black text-amber-600">Rp {{ number_format($pengeluaran, 0, ',', '.') }}</span>
                                                </div>
                                            </div>

                                            <div class="space-y-2">
                                                <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 border-b border-gray-100 pb-1">BHP dari Tindakan</div>
                                                @php
                                                    $detailBhp = $this->getDetailBhp($item->nomor_kunjungan);
                                                @endphp
                                                @forelse($detailBhp as $bhp)
                                                <div class="flex items-start justify-between gap-3 p-2 bg-amber-50/50 rounded-lg border border-amber-50">
                                                    <div>
                                                        <div class="text-xs font-bold text-amber-900">{{ \App\Models\MstTindakan::where('kode_tindakan', $bhp->kode_tindakan)->value('nama_tindakan') ?? $bhp->kode_tindakan }}</div>
                                                    </div>
                                                    <div class="text-xs font-black text-amber-600 whitespace-nowrap">
                                                        Rp {{ number_format($bhp->bhp, 0, ',', '.') }}
                                                    </div>
                                                </div>
                                                @empty
                                                <div class="text-center py-4 text-gray-400 text-xs italic">Tidak ada pengeluaran BHP</div>
                                                @endforelse
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
                                        <p class="text-xs text-gray-300 mt-1 uppercase tracking-widest font-bold">Cobalah menyesuaikan filter periode/pencarian Anda</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($this->laporanPendapatan->hasPages())
                <div class="px-6 py-5 sm:px-8 sm:py-6 bg-gray-50/50 border-t border-gray-100 pagination-custom rounded-b-3xl">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-5">
                        <div class="text-[11px] font-bold text-[#878a99] tracking-tight text-center sm:text-left">
                            <i class="ri-list-check-2 text-[#405189] mr-1 hidden sm:inline"></i>
                            <span class="hidden sm:inline">Menampilkan</span> 
                            <span class="text-[#405189] font-black">{{ $this->laporanPendapatan->firstItem() ?: 0 }} - {{ $this->laporanPendapatan->lastItem() ?: 0 }}</span> 
                            dari <span class="text-[#405189] font-black">{{ number_format($this->laporanPendapatan->total()) }}</span> 
                            <span class="hidden sm:inline">data</span>
                            <span class="sm:hidden">total data</span>
                        </div>
                        {{ $this->laporanPendapatan->links() }}
                    </div>
                </div>
                @endif
            </div>
        </div>