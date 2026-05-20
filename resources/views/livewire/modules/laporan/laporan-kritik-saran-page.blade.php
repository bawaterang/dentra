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
            </style>

            <div class="page-header">
                <div class="page-header-title">
                    <div class="page-header-icon bg-gradient-to-br from-[#405189] to-[#2a3a6a] text-white shadow-lg animate-pulse" style="animation-duration: 3s;">
                        <i class="ri-chat-quote-line"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold tracking-tight text-[#2c3e50]">Laporan Kritik dan Saran</h1>
                        <p class="text-xs text-[#878a99] font-medium mt-0.5">Rekapitulasi kritik, saran, dan pesan dari pasien.</p>
                    </div>
                </div>
                <div class="page-header-breadcrumb">
                    <a href="/dashboard" wire:navigate class="hover:text-[#405189] transition-colors"><i class="ri-home-4-line"></i></a>
                    <span class="sep text-gray-300">/</span>
                    <span class="text-gray-400 font-medium">Laporan</span>
                    <span class="sep text-gray-300">/</span>
                    <span class="text-[#405189] font-bold">Kritik & Saran</span>
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
                            <label class="lg:hidden text-[9px] font-black text-gray-400 uppercase tracking-widest px-1 block mb-1">Cari Pesan</label>
                            <div class="relative">
                                <i class="ri-search-2-line absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                                <input type="text" wire:model.live.debounce.300ms="search" 
                                       class="w-full bg-gray-50/50 border border-gray-200 rounded-2xl py-2.5 pl-12 pr-4 text-sm font-medium outline-none transition-all search-focus-glow placeholder:text-gray-300" 
                                       placeholder="Cari nama, kontak, pesan...">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 sm:flex sm:items-center gap-3 w-full lg:w-auto lg:p-1 lg:rounded-lg lg:border lg:border-[#e2e8f0] lg:bg-white">
                            <a href="{{ route('laporan.kritik-saran.print', ['periodType' => $periodType, 'selectedDate' => $selectedDate, 'selectedBulan' => $selectedBulan, 'selectedTahun' => $selectedTahun, 'search' => $search]) }}" target="_blank" 
                               class="flex flex-row items-center justify-center gap-2 p-3 lg:p-0 lg:h-8 lg:w-8 rounded-xl lg:rounded-md bg-white border border-gray-100 lg:border-none shadow-sm lg:shadow-none hover:bg-indigo-50 transition-all group/print" title="Cetak PDF">
                                <i class="ri-printer-line text-lg text-indigo-500 group-hover/print:scale-110 transition-transform"></i>
                                <span class="lg:hidden text-[11px] font-bold text-gray-600">Cetak PDF</span>
                            </a>
                            <div class="hidden lg:block w-[1px] h-4 bg-[#e2e8f0]"></div>
                            <a href="{{ route('laporan.kritik-saran.export', ['periodType' => $periodType, 'selectedDate' => $selectedDate, 'selectedBulan' => $selectedBulan, 'selectedTahun' => $selectedTahun, 'search' => $search]) }}" target="_blank" 
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
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Pengirim</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Kontak</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Pesan & Info</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Status Jawaban</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($this->laporanKritikSaran as $index => $item)
                            <tr wire:key="laporan-{{ $item->id }}" class="laporan-row transition-all duration-200">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-bold text-gray-400">{{ $this->laporanKritikSaran->firstItem() + $index }}</span>
                                </td>
                                <td class="px-6 py-4 min-w-[200px]">
                                    <div class="font-bold text-[#2c3e50] text-sm group-hover:text-[#405189] transition-colors">
                                        {{ $item->nama ?: 'Anonim' }}
                                    </div>
                                    <div class="text-[10px] font-medium text-gray-500 mt-1">
                                        <i class="ri-time-line"></i> {{ $item->created_at ? $item->created_at->format('d/m/Y H:i') : '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 min-w-[200px]">
                                    <div class="flex flex-col gap-1.5">
                                        <span class="inline-flex items-center gap-1.5 text-xs text-gray-600">
                                            <i class="ri-mail-line text-gray-400"></i> {{ $item->email ?: '-' }}
                                        </span>
                                        <span class="inline-flex items-center gap-1.5 text-xs text-gray-600">
                                            <i class="ri-phone-line text-gray-400"></i> {{ $item->nomor_hp ?: '-' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 min-w-[300px]">
                                    <div class="text-sm text-gray-700 whitespace-normal bg-gray-50/50 p-3 rounded-xl border border-gray-100">
                                        "{{ $item->pesan }}"
                                    </div>
                                    <div class="flex items-center gap-3 mt-2">
                                        @if($item->platform)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-blue-50 text-blue-600 text-[9px] font-bold uppercase tracking-wider">
                                            <i class="ri-smartphone-line"></i> {{ $item->platform }}
                                        </span>
                                        @endif
                                        @if($item->ip_address)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-gray-100 text-gray-500 text-[9px] font-bold uppercase tracking-wider">
                                            <i class="ri-route-line"></i> IP: {{ $item->ip_address }}
                                        </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 min-w-[250px]">
                                    @if($item->jawaban)
                                        <div class="bg-indigo-50/50 p-3 rounded-xl border border-indigo-100">
                                            <div class="flex items-center justify-between mb-1.5">
                                                <span class="text-[9px] font-black uppercase text-indigo-500 tracking-wider"><i class="ri-check-double-line"></i> Terjawab</span>
                                                <span class="text-[9px] font-bold text-indigo-400">{{ $item->waktu_jawab ? \Carbon\Carbon::parse($item->waktu_jawab)->format('d/m/Y H:i') : '' }}</span>
                                            </div>
                                            <div class="text-xs text-indigo-700 font-medium">
                                                {{ $item->jawaban }}
                                            </div>
                                        </div>
                                    @else
                                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-amber-50 text-amber-600 border border-amber-100 text-[10px] font-bold uppercase tracking-wider">
                                            <i class="ri-time-line"></i> Menunggu Jawaban
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button wire:click="openJawabModal('{{ $item->id }}')" class="action-btn-soft bg-emerald-50 text-emerald-600 hover:bg-emerald-100 shadow-sm" title="Jawab Pesan">
                                            <i class="ri-reply-line text-sm"></i>
                                        </button>
                                        <button wire:click="deleteMessage('{{ $item->id }}')" 
                                                wire:confirm="Apakah Anda yakin ingin menghapus pesan ini?"
                                                class="action-btn-soft bg-red-50 text-red-500 hover:bg-red-100 shadow-sm" 
                                                title="Hapus Pesan">
                                            <i class="ri-delete-bin-line text-sm"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="py-20 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center mb-6 animate-bounce" style="animation-duration: 4s;">
                                            <i class="ri-chat-quote-line text-5xl text-gray-200"></i>
                                        </div>
                                        <p class="text-lg font-black text-gray-400">Belum Ada Kritik/Saran</p>
                                        <p class="text-xs text-gray-300 mt-1 uppercase tracking-widest font-bold">Cobalah menyesuaikan filter periode Anda</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($this->laporanKritikSaran->hasPages())
                <div class="px-6 py-5 sm:px-8 sm:py-6 bg-gray-50/50 border-t border-gray-100 pagination-custom rounded-b-3xl">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-5">
                        <div class="text-[11px] font-bold text-[#878a99] tracking-tight text-center sm:text-left">
                            <i class="ri-list-check-2 text-[#405189] mr-1 hidden sm:inline"></i>
                            <span class="hidden sm:inline">Menampilkan</span> 
                            <span class="text-[#405189] font-black">{{ $this->laporanKritikSaran->firstItem() ?: 0 }} - {{ $this->laporanKritikSaran->lastItem() ?: 0 }}</span> 
                            dari <span class="text-[#405189] font-black">{{ number_format($this->laporanKritikSaran->total()) }}</span> 
                            <span class="hidden sm:inline">data</span>
                            <span class="sm:hidden">total data</span>
                        </div>
                        {{ $this->laporanKritikSaran->links() }}
                    </div>
                </div>
                @endif
            </div>

            <!-- Modal Jawab Pesan -->
            @if($showJawabModal)
            <div class="fixed inset-0 z-[1050] flex items-center justify-center p-4 sm:p-6 lg:p-8" x-data="{ show: @entangle('showJawabModal') }" x-show="show" x-cloak>
                <!-- Overlay -->
                <div class="fixed inset-0 transition-opacity bg-black/60 shadow-2xl backdrop-blur-sm" wire:click="closeJawabModal" x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"></div>
                
                <!-- Modal Box -->
                <div class="relative w-full max-w-2xl text-left bg-white shadow-2xl rounded-3xl z-10 overflow-hidden flex flex-col"
                     x-show="show" x-transition:enter="ease-out duration-400" x-transition:enter-start="opacity-0 translate-y-8 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100">

                    <!-- Header -->
                    <div class="px-6 py-5 bg-gradient-to-r from-emerald-600 to-indigo-700 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center text-white text-xl shadow-inner">
                                <i class="ri-reply-all-line"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-black text-white leading-none">Jawab Kritik/Saran</h3>
                                <p class="text-[10px] text-emerald-100 font-bold mt-1 uppercase tracking-widest">Pesan dari {{ $formJawab['nama'] }}</p>
                            </div>
                        </div>
                        <button wire:click="closeJawabModal" class="text-white/60 hover:text-white transition-colors">
                            <i class="ri-close-circle-fill text-3xl"></i>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="p-6">
                        <div class="mb-5 p-4 bg-gray-50 rounded-2xl border border-gray-100">
                            <span class="text-[9px] font-black uppercase text-gray-400 tracking-wider block mb-1">Pesan Pasien</span>
                            <p class="text-sm font-medium text-gray-700">"{{ $formJawab['pesan'] }}"</p>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase text-gray-500 tracking-wider block pl-1">Jawaban Anda <span class="text-red-500">*</span></label>
                            <textarea wire:model="formJawab.jawaban" rows="5" class="w-full bg-white border {{ $errors->has('formJawab.jawaban') ? 'border-red-300 focus:ring-red-100 focus:border-red-500' : 'border-gray-200 focus:ring-indigo-100 focus:border-[#405189]' }} rounded-2xl p-4 text-sm font-medium outline-none transition-all shadow-sm resize-none" placeholder="Ketik jawaban Anda di sini..."></textarea>
                            @error('formJawab.jawaban') <span class="text-xs font-bold text-red-500 pl-1"><i class="ri-error-warning-line"></i> {{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-6 py-4 bg-gray-50/80 border-t border-gray-100 flex items-center justify-end gap-3 rounded-b-3xl">
                        <button type="button" wire:click="closeJawabModal" class="px-5 py-2.5 rounded-xl text-xs font-bold text-gray-600 bg-white border border-gray-200 hover:bg-gray-50 hover:text-gray-900 transition-all shadow-sm">
                            Batal
                        </button>
                        <button type="button" wire:click="simpanJawaban" class="px-6 py-2.5 rounded-xl text-xs font-black text-white bg-gradient-to-r from-emerald-600 to-indigo-600 hover:from-emerald-700 hover:to-indigo-700 transition-all shadow-md hover:shadow-lg flex items-center gap-2">
                            <i class="ri-save-3-line"></i> SIMPAN JAWABAN
                        </button>
                    </div>
                </div>
            </div>
            @endif
        </div>