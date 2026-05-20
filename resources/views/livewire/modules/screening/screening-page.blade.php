        <div>
            <style>
                .custom-row:hover {
                    background-color: #d8dce1ff !important;
                    transition: all 0.3s ease;
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
                        <i class="ri-shield-check-line"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold tracking-tight text-[#2c3e50]">Screening Pasien</h1>
                        <p class="text-xs text-[#878a99] font-medium mt-0.5">Data hasil screening kesehatan awal pasien sebelum pemeriksaan.</p>
                    </div>
                </div>
                <div class="page-header-breadcrumb">
                    <a href="/dashboard" wire:navigate class="hover:text-[#405189] transition-colors"><i class="ri-home-4-line"></i></a>
                    <span class="sep text-gray-300">/</span>
                    <span class="text-gray-400 font-medium">Screening</span>
                    <span class="sep text-gray-300">/</span>
                    <span class="text-[#405189] font-bold">Layanan Screening</span>
                </div>
            </div>



            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Calendar Sidebar -->
                <div class="lg:col-span-1">
                    <div class="card shadow-sm border-t-2 border-[#405189]">
                        <div class="p-4 border-b border-[#eff2f7]"><h6 class="text-xs font-bold text-[#405189] uppercase tracking-widest mb-0"><i class="ri-calendar-line mr-1"></i>Pilih Tanggal</h6></div>
                        <div class="p-4">
                            <div class="flex items-center gap-2">
                                <button wire:click="prevDate" class="flex h-[42px] w-10 shrink-0 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-500 hover:border-[#405189] hover:text-[#405189] hover:bg-indigo-50 transition-all group" title="Hari Sebelumnya">
                                    <i class="ri-arrow-left-s-line text-xl group-hover:scale-110 transition-transform"></i>
                                </button>
                                
                                <div class="relative flex-grow">
                                    <input type="date" wire:model.live="selectedDate" class="w-full rounded-lg border-gray-200 text-sm px-4 h-[42px] focus:border-[#405189] transition-all text-center font-bold text-[#405189] appearance-none cursor-pointer">
                                </div>

                                <button wire:click="nextDate" class="flex h-[42px] w-10 shrink-0 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-500 hover:border-[#405189] hover:text-[#405189] hover:bg-indigo-50 transition-all group" title="Hari Berikutnya">
                                    <i class="ri-arrow-right-s-line text-xl group-hover:scale-110 transition-transform"></i>
                                </button>
                            </div>
                            <div class="mt-3 text-center p-2 bg-indigo-50/50 rounded-xl border border-indigo-100/50">
                                <p class="text-[10px] text-[#878a99] font-bold uppercase tracking-widest mb-0.5">Tanggal Terpilih</p>
                                <p class="font-black text-[#405189] text-xs">{{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('l, d F Y') }}</p>
                            </div>
                        </div>
                        <div class="p-4 border-t border-[#eff2f7]">
                            <p class="text-[10px] text-[#878a99] uppercase tracking-widest font-bold mb-3 text-center">Ringkasan Hari Ini</p>
                            <div class="space-y-2">
                                <div class="flex items-center justify-between p-2.5 rounded-xl bg-orange-50/50 border border-orange-100/50">
                                    <div class="flex items-center gap-2">
                                        <div class="w-1.5 h-1.5 rounded-full bg-orange-400"></div>
                                        <span class="text-[10px] font-bold text-orange-700">Belum</span>
                                    </div>
                                    <span class="text-xs font-black text-orange-600 tracking-tight">{{ number_format($totalBelum) }}</span>
                                </div>
                                <div class="flex items-center justify-between p-2.5 rounded-xl bg-green-50/50 border border-green-100/50">
                                    <div class="flex items-center gap-2">
                                        <div class="w-1.5 h-1.5 rounded-full bg-green-400"></div>
                                        <span class="text-[10px] font-bold text-green-700">Sudah</span>
                                    </div>
                                    <span class="text-xs font-black text-green-600 tracking-tight">{{ number_format($totalSudah) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card shadow-sm mt-4 p-4">
                        <div class="flex items-center gap-2 mb-3"><i class="ri-download-2-line text-[#405189]"></i><span class="text-xs font-bold text-[#405189] uppercase tracking-widest">Eksport</span></div>
                        <a href="{{ route('screening.export', ['date' => $selectedDate]) }}" target="_blank" class="btn bg-green-600 text-white w-full h-9 flex items-center justify-center gap-2 text-xs font-bold hover:bg-green-700 transition-all mb-2"><i class="ri-file-excel-2-line"></i> Unduh Excel</a>
                    </div>
                </div>

                <!-- Table -->
                <div class="lg:col-span-3">
                    <div class="card overflow-hidden border-t-2 border-[#405189]">
                        <div class="p-4 border-b border-[#eff2f7]">
                            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                                <div class="flex overflow-x-auto scrollbar-hide"><ul class="nav-pills-custom">
                                    <li class="nav-item"><a class="nav-link {{ $selectedTab === 'belum' ? 'active active-pill-warning' : '' }}" wire:click="setTab('belum')" role="button"><i class="ri-time-line"></i><span>Belum Screening ({{ $totalBelum }})</span></a></li>
                                    <li class="nav-item"><a class="nav-link {{ $selectedTab === 'sudah' ? 'active active-pill-success' : '' }}" wire:click="setTab('sudah')" role="button"><i class="ri-checkbox-circle-line"></i><span>Sudah Screening ({{ $totalSudah }})</span></a></li>
                                </ul></div>

                                <div class="relative flex-grow max-w-[320px]">
                                    <i class="ri-search-2-line absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                                    <input type="text" wire:model.live.debounce.300ms="search" class="w-full bg-gray-50 border border-gray-300 rounded-2xl py-2 pl-11 pr-4 text-sm font-medium outline-none transition-all focus:border-[#405189] focus:ring-4 focus:ring-[#405189]/5 placeholder:text-gray-300" placeholder="Cari pasien atau kunjungan...">
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0"><div class="overflow-x-auto dark:bg-transparent">
                            <table class="w-full text-left border-collapse">
                                <thead class="bg-gray-50/50">
                                    <tr>
                                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">No Kunjungan</th>
                                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Pasien</th>
                                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Poli & Dokter</th>
                                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Status</th>
                                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    @forelse($this->pendaftaranList as $item)
                                    <tr wire:key="scr-{{ $item->id }}" class="custom-row transition-all duration-200">
                                        <td class="px-6 py-4 whitespace-nowrap"><span class="font-mono font-bold text-[#405189] text-xs px-2 py-1 bg-[#405189]/5 rounded">{{ $item->nomor_kunjungan }}</span></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="font-bold text-[#2c3e50] text-sm">{{ $item->pasien->nama_pasien ?? '-' }}</div>
                                            <span class="text-[11px] font-mono text-gray-400 mt-0.5 inline-block">{{ $item->pasien->no_rm ?? '' }}</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-700">
                                            <div class="font-bold text-[#495057] text-sm">{{ $item->poli->nama_poli ?? '-' }}</div>
                                            <div class="text-[10px] text-gray-400 font-medium italic mt-0.5"><i class="ri-user-star-line mr-0.5"></i> {{ $item->dokter->nama_dokter ?? '-' }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($item->status === 'selesai')
                                                <span class="px-2.5 py-1.5 rounded-lg text-xs font-bold w-max gap-1.5 flex items-center bg-success-subtle text-emerald-600"><i class="ri-checkbox-circle-line"></i> Selesai</span>
                                            @else
                                                <span class="px-2.5 py-1.5 rounded-lg text-xs font-bold w-max gap-1.5 flex items-center bg-warning-subtle text-amber-600"><i class="ri-time-line"></i> Belum Screening</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center justify-center gap-2">
                                                @if($item->status !== 'selesai')
                                                    <a href="{{ route('screening.form', ['pendaftaranId' => $item->id]) }}" wire:navigate class="flex h-8 px-3 rounded-full items-center justify-center bg-indigo-50 text-[#405189] hover:bg-[#405189] hover:text-white transition-all shadow-sm text-xs font-bold gap-1"><i class="ri-shield-check-line"></i> Screening</a>
                                                @else
                                                    <a href="{{ route('screening.print', ['pendaftaranId' => $item->id]) }}" target="_blank" class="w-8 h-8 rounded-full flex items-center justify-center bg-indigo-50 text-[#405189] hover:bg-[#405189] hover:text-white transition-all shadow-sm" title="Cetak"><i class="ri-printer-line"></i></a>
                                                    <a href="{{ route('screening.form', ['pendaftaranId' => $item->id]) }}" wire:navigate class="w-8 h-8 rounded-full flex items-center justify-center bg-emerald-50 text-emerald-500 hover:bg-emerald-500 hover:text-white transition-all shadow-sm" title="Lihat"><i class="ri-eye-line"></i></a>
                                                    <button type="button" wire:click="editScreening({{ $item->id }})" class="w-8 h-8 rounded-full flex items-center justify-center bg-orange-50 text-orange-500 hover:bg-orange-500 hover:text-white transition-all shadow-sm" title="Edit"><i class="ri-edit-line"></i></button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="py-16 text-center">
                                            <div class="flex flex-col items-center justify-center">
                                                <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                                    <i class="ri-heart-pulse-line text-4xl text-gray-300"></i>
                                                </div>
                                                <p class="text-base font-bold text-gray-500">Belum ada pasien screening</p>
                                                <p class="text-xs text-gray-400 mt-1">Belum ada data pasien yang harus di screening hari ini.</p>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                            @if($this->pendaftaranList->hasPages())
                            <div class="px-6 py-5 sm:px-8 sm:py-6 bg-gray-50/50 border-t border-gray-100 pagination-custom">
                                <div class="flex flex-col sm:flex-row items-center justify-between gap-5">
                                    <div class="text-[11px] font-bold text-[#878a99] tracking-tight text-center sm:text-left">
                                        <span class="hidden sm:inline">Menampilkan</span> 
                                        <span class="text-[#405189] font-black">{{ $this->pendaftaranList->firstItem() }} - {{ $this->pendaftaranList->lastItem() }}</span> 
                                        dari <span class="text-[#405189] font-black">{{ number_format($this->pendaftaranList->total()) }}</span> 
                                        <span class="hidden sm:inline">screening</span>
                                    </div>
                                    {{ $this->pendaftaranList->links() }}
                                </div>
                            </div>
                            @endif
                        </div></div>
                    </div>
                </div>
            </div>
            <!-- Edit Modal -->
            <div x-show="$wire.showEditModal" class="fixed inset-0 z-[1050] overflow-y-auto" x-cloak>
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 transition-opacity" x-show="$wire.showEditModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                        <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm"></div>
                    </div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen"></span>&#8203;
                    <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full relative z-10" x-show="$wire.showEditModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                        <div class="bg-[#405189] px-6 py-4 flex justify-between items-center shadow-lg">
                             <h3 class="text-white font-bold flex items-center gap-2"><i class="ri-edit-box-line text-xl"></i> Edit Screening</h3>
                             <button wire:click="closeEditModal" type="button" class="text-white/80 hover:text-white transition-colors"><i class="ri-close-line text-2xl"></i></button>
                        </div>
                        <form wire:submit.prevent="updateScreening">
                            <div class="px-8 py-6 max-h-[70vh] overflow-y-auto custom-scrollbar">
                                
                                <!-- Patient Info Card -->
                                <div class="bg-gray-50/50 p-5 rounded-2xl border border-gray-100 shadow-sm mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                                     <div class="flex items-center gap-4">
                                         <div class="h-14 w-14 rounded-full bg-gradient-to-br from-[#405189] to-[#3577f1] text-white flex items-center justify-center font-bold text-xl">{{ substr($editPasienName, 0, 1) }}</div>
                                         <div>
                                             <h4 class="font-bold text-lg text-[#495057]">{{ $editPasienName }}</h4>
                                             <div class="flex flex-wrap gap-3 text-xs text-[#878a99]">
                                                 <span><i class="ri-hashtag mr-1"></i>{{ $editNoRm }}</span>
                                                 <span><i class="ri-hospital-line mr-1"></i>{{ $editPoliName }}</span>
                                                 <span><i class="ri-user-star-line mr-1"></i>{{ $editDokterName }}</span>
                                             </div>
                                         </div>
                                     </div>
                                     <div class="text-right">
                                         <span class="font-mono font-bold text-[#0ab39c] text-sm">{{ $editKunjungan }}</span>
                                     </div>
                                </div>

                                <!-- Pertanyaan Grid -->
                                <div class="space-y-4">
                                    @foreach($pertanyaanList as $index => $p_survei)
                                    <div class="p-4 rounded-xl border border-gray-100 hover:border-[#405189]/20 transition-all {{ isset($jawaban[$p_survei->id]) && $jawaban[$p_survei->id] === 'ya' ? 'bg-red-50 border-red-200' : 'bg-white' }}">
                                        <div class="flex items-start gap-4">
                                            <span class="flex-shrink-0 h-7 w-7 rounded-lg bg-[#405189] text-white flex items-center justify-center text-xs font-bold mt-0.5">{{ $index + 1 }}</span>
                                            <div class="flex-1">
                                                <p class="font-medium text-[#495057] text-sm mb-3">{{ $p_survei->pertanyaan }}</p>
                                                <div class="flex items-center gap-6">
                                                     <label class="flex items-center gap-2 cursor-pointer group">
                                                         <input type="radio" wire:model="jawaban.{{ $p_survei->id }}" value="ya" class="w-4 h-4 text-red-500 border-gray-300 focus:ring-red-400">
                                                         <span class="text-sm font-semibold {{ isset($jawaban[$p_survei->id]) && $jawaban[$p_survei->id] === 'ya' ? 'text-red-600' : 'text-gray-500' }} group-hover:text-red-500">Ya</span>
                                                     </label>
                                                     <label class="flex items-center gap-2 cursor-pointer group">
                                                         <input type="radio" wire:model="jawaban.{{ $p_survei->id }}" value="tidak" class="w-4 h-4 text-green-500 border-gray-300 focus:ring-green-400">
                                                         <span class="text-sm font-semibold {{ isset($jawaban[$p_survei->id]) && $jawaban[$p_survei->id] === 'tidak' ? 'text-green-600' : 'text-gray-500' }} group-hover:text-green-500">Tidak</span>
                                                     </label>
                                                </div>
                                                <div class="mt-4">
                                                     <input type="text" wire:model="keterangan.{{ $p_survei->id }}" class="w-full rounded-lg border border-gray-300 text-sm px-4 h-11 focus:border-[#405189] focus:ring focus:ring-[#405189]/20 transition-all bg-gray-50/50" placeholder="Tambahkan keterangan rincian (opsional)...">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="bg-gray-50 px-8 py-5 flex justify-end gap-3 rounded-b-2xl border-t border-gray-100">
                                 <button type="button" wire:click="closeEditModal" class="btn bg-gray-500 text-white px-6 h-10 flex items-center gap-2 transition-all hover:bg-gray-600 rounded-xl font-bold"><i class="ri-close-line"></i> Batal</button>
                                 <button type="submit" class="btn bg-[#0d6efd] text-white px-8 h-10 shadow-md flex items-center justify-center gap-2 transition-all hover:bg-[#0b5ed7] hover:translate-y-[-2px] rounded-xl font-bold"><i class="ri-save-line"></i> Simpan Perubahan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>