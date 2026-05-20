        <div x-data="{ 
                showModal: false, 
                searchPracModal: false,
                searchBpjsModal: false,
                init(){
                    this.$watch('showModal', v => {
                        if(v){ $nextTick(() => { this.$refs.firstInput && this.$refs.firstInput.focus() }) }
                    })
                } 
            }" 
            @open-modal.window="showModal=true" 
            @close-modal.window="showModal=false" 
            @open-search-prac-modal.window="searchPracModal = true" 
            @close-search-prac-modal.window="searchPracModal = false"
            @open-search-bpjs-modal.window="searchBpjsModal = true" 
            @close-search-bpjs-modal.window="searchBpjsModal = false"
            x-init="init()">

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

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 sm:gap-6">
                                <div class="space-y-1.5">
                                    <label class="text-[9px] sm:text-[10px] font-black text-[#405189] uppercase tracking-widest px-1">Practitioner ID (SatuSehat)</label>
                                    <div class="flex gap-2">
                                        <div class="relative flex-1">
                                            <i class="ri-fingerprint-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                            <input type="text" wire:model="practitioner_id" class="w-full pl-10 rounded-xl border-gray-200 text-sm py-2.5 focus:border-[#405189] focus:ring focus:ring-[#405189]/20 transition-all bg-indigo-50/30" placeholder="P0000...">
                                        </div>
                                        <button type="button" @click="$dispatch('open-search-prac-modal')" class="bg-[#405189] text-white px-4 rounded-xl text-xs font-bold shadow-sm hover:bg-slate-700 transition-colors">
                                            <i class="ri-search-line mr-1"></i> Cari
                                        </button>
                                    </div>
                                    @error('practitioner_id') <span class="text-[10px] text-rose-500 font-bold px-1">{{ $message }}</span> @enderror
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[9px] sm:text-[10px] font-black text-emerald-600 uppercase tracking-widest px-1">BPJS ID (Dokter)</label>
                                    <div class="flex gap-2">
                                        <div class="relative flex-1">
                                            <i class="ri-bank-card-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                            <input type="text" wire:model="dokter_bpjs_id" class="w-full pl-10 rounded-xl border-gray-200 text-sm py-2.5 focus:border-[#0ab39c] focus:ring focus:ring-[#0ab39c]/20 transition-all bg-emerald-50/30" placeholder="00020...">
                                        </div>
                                        <button type="button" wire:click="searchBpjsPrac" class="bg-[#0ab39c] text-white px-4 rounded-xl text-xs font-bold shadow-sm hover:bg-emerald-600 transition-colors">
                                            <i class="ri-file-search-line"></i>
                                        </button>
                                    </div>
                                    @error('dokter_bpjs_id') <span class="text-[10px] text-rose-500 font-bold px-1">{{ $message }}</span> @enderror
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

            <!-- Search Practitioner Modal -->
            <div x-show="searchPracModal" class="fixed inset-0 z-[1055] flex items-center justify-center p-4" x-transition.opacity style="display: none;">
                <div class="absolute inset-0 bg-[#0a192f]/60 backdrop-blur-md" @click="searchPracModal = false"></div>
                <div x-show="searchPracModal" x-transition.scale.95 class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden p-8 relative z-10 border border-white/20">
                    <div class="absolute top-6 right-6">
                        <button type="button" @click="searchPracModal = false" class="w-10 h-10 rounded-full flex items-center justify-center text-gray-400 hover:bg-rose-50 hover:text-rose-500 transition-all">
                            <i class="ri-close-circle-fill text-2xl"></i>
                        </button>
                    </div>

                    <div class="mb-8">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shadow-inner">
                                <i class="ri-user-search-line text-xl"></i>
                            </div>
                            <h3 class="text-xl font-black text-[#2c3e50] tracking-tight">Cari Practitioner SatuSehat</h3>
                        </div>
                        <p class="text-xs text-gray-400 font-bold uppercase tracking-widest">Gunakan NIK atau Nama Lengkap Dokter Terdaftar</p>
                    </div>

                    <div class="bg-gray-50/50 p-6 rounded-2xl border border-gray-100 mb-8">
                        <form wire:submit.prevent="searchSatuSehatPrac" class="flex flex-col sm:flex-row gap-3">
                            <div class="relative flex-1">
                                <i class="ri-id-card-line absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <input type="text" wire:model="searchPracQuery" class="w-full pl-12 rounded-xl border-gray-200 text-sm py-3 px-4 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 placeholder:text-gray-300 font-bold tracking-tight" placeholder="NIK 16 digit atau Nama lengkap...">
                            </div>
                            <button type="submit" class="bg-indigo-600 text-white px-8 py-3 rounded-xl text-xs font-black uppercase tracking-widest shadow-lg shadow-indigo-200 hover:bg-indigo-700 hover:-translate-y-0.5 transition-all">
                                <span wire:loading.remove wire:target="searchSatuSehatPrac">Cari Data</span>
                                <span wire:loading wire:target="searchSatuSehatPrac"><i class="ri-loader-4-line animate-spin mr-2"></i>Searching...</span>
                            </button>
                        </form>
                        <p class="text-[10px] text-gray-400 mt-3 font-medium flex items-center gap-1.5 px-1">
                            <i class="ri-information-line text-indigo-500"></i> Pencarian via Nama memerlukan Jenis Kelamin & Tanggal Lahir yang sudah terisi di form.
                        </p>
                    </div>

                    @if(!empty($foundPractitioners))
                    <div class="max-h-96 overflow-y-auto space-y-4 pr-3 scrollbar-custom">
                        @foreach($foundPractitioners as $prac)
                            @php
                                $resource = $prac['resource'];
                                $pracName = $resource['name'][0]['text'] ?? ($resource['name'][0]['family'] ?? 'Unknown');
                                $pracNik = collect($resource['identifier'] ?? [])->firstWhere('system', 'https://fhir.kemkes.go.id/id/nik')['value'] ?? '-';
                            @endphp
                            <div class="group border border-gray-100 rounded-2xl p-5 flex items-center justify-between hover:border-indigo-300 hover:bg-indigo-50/30 transition-all duration-300 bg-white">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-full bg-indigo-50 text-indigo-500 flex items-center justify-center font-black text-lg border-2 border-white shadow-sm group-hover:bg-indigo-500 group-hover:text-white transition-all">
                                        {{ substr($pracName, 0, 1) }}
                                    </div>
                                    <div>
                                        <h4 class="font-black text-[#2c3e50] text-sm group-hover:text-indigo-600 transition-colors uppercase tracking-tight">{{ $pracName }}</h4>
                                        <div class="flex flex-wrap gap-4 mt-1.5 text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                            <span><i class="ri-fingerprint-line mr-1 text-indigo-400"></i> NIK: {{ $pracNik }}</span>
                                            <span><i class="ri-key-line mr-1 text-emerald-400"></i> ID: <span class="text-indigo-600 font-black">{{ $resource['id'] }}</span></span>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" 
                                        wire:click="selectPrac('{{ $resource['id'] }}')" 
                                        class="btn btn-sm bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white rounded-xl px-6 h-9 font-black text-[10px] uppercase tracking-widest shadow-sm transition-all">
                                    Pilih
                                </button>
                            </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>

            <!-- Search BPJS Modal -->
            <div x-show="searchBpjsModal" class="fixed inset-0 z-[1100] flex items-center justify-center p-4" x-transition.opacity style="display: none;">
                <div class="absolute inset-0 bg-[#0a192f]/80 backdrop-blur-sm"></div>
                <div x-show="searchBpjsModal" x-transition.scale.95 
                     class="relative w-full max-w-2xl bg-white rounded-3xl shadow-2xl overflow-hidden animate-in zoom-in duration-300">
                    <div class="px-8 py-6 bg-emerald-600 text-white flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <i class="ri-bank-card-line text-2xl"></i>
                            <div>
                                <h5 class="text-lg font-black tracking-tight">Cari Dokter BPJS</h5>
                                <p class="text-[10px] text-emerald-100 font-bold uppercase tracking-widest">Master Data Dokter dari PCare BPJS</p>
                            </div>
                        </div>
                        <button @click="searchBpjsModal = false" class="text-white/50 hover:text-white transition-colors"><i class="ri-close-line text-2xl"></i></button>
                    </div>
                    <div class="p-8">
                        <div class="relative mb-6">
                            <input type="text" wire:model.live.debounce.300ms="searchBpjsQuery" 
                                   class="w-full bg-gray-50 border border-gray-100 rounded-2xl py-3 pl-12 pr-4 text-sm font-bold focus:bg-white focus:ring-4 focus:ring-emerald-100 focus:border-emerald-600 transition-all outline-none" 
                                   placeholder="Cari Nama atau Kode Dokter di BPJS...">
                            <i class="ri-search-2-line absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                        </div>

                        <div class="max-h-[400px] overflow-y-auto scrollbar-hide space-y-3">
                            @forelse($foundBpjsDokters as $bpjsDoc)
                            <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100 flex items-center justify-between group hover:bg-white hover:border-emerald-200 hover:shadow-lg hover:shadow-emerald-500/10 transition-all cursor-pointer"
                                 wire:click="selectBpjsDokter('{{ $bpjsDoc['kdDokter'] ?? '' }}')">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center font-bold">
                                        {{ strtoupper(substr($bpjsDoc['nmDokter'] ?? '?', 0, 1)) }}
                                    </div>
                                    <div>
                                        <h6 class="text-sm font-black text-gray-700 group-hover:text-emerald-600">{{ $bpjsDoc['nmDokter'] ?? '-' }}</h6>
                                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest leading-none mt-1">Kode: {{ $bpjsDoc['kdDokter'] ?? '-' }}</p>
                                    </div>
                                </div>
                                <button class="btn btn-sm bg-emerald-600 text-white px-4 rounded-xl text-[10px] font-bold">PILIH</button>
                            </div>
                            @empty
                            <div class="py-12 text-center">
                                <i class="ri-file-search-line text-5xl text-gray-200 mb-3 block"></i>
                                <span class="text-sm text-gray-400 font-bold">Tidak ada data dokter ditemukan di BPJS.</span>
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enhanced Practitioner Search Modal (SatuSehat) -->
            <div x-show="searchPracModal" class="fixed inset-0 z-[1100] flex items-center justify-center p-4" x-transition.opacity style="display: none;">
                <div class="absolute inset-0 bg-[#0a192f]/80 backdrop-blur-sm"></div>
                <div x-show="searchPracModal" x-transition.scale.95 
                     class="relative w-full max-w-2xl bg-white rounded-3xl shadow-2xl overflow-hidden animate-in zoom-in duration-300">
                    <div class="px-8 py-6 bg-slate-900 text-white flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <i class="ri-fingerprint-line text-2xl"></i>
                            <div>
                                <h5 class="text-lg font-black tracking-tight">Cari Practitioner ID</h5>
                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Integrasi SatuSehat Kementerian Kesehatan</p>
                            </div>
                        </div>
                        <button @click="searchPracModal = false" class="text-white/50 hover:text-white transition-colors"><i class="ri-close-line text-2xl"></i></button>
                    </div>

                    <div class="p-8">
                        <div class="flex gap-3 mb-6">
                            <div class="relative flex-1">
                                <input type="text" wire:model="searchPracQuery" 
                                       class="w-full bg-gray-50 border border-gray-100 rounded-2xl py-3 px-4 text-sm font-bold focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-[#405189] transition-all outline-none" 
                                       placeholder="Masukkan NIK atau Nama Dokter...">
                            </div>
                            <button wire:click="searchSatuSehatPrac" class="btn btn-primary px-8 rounded-2xl font-bold">CARI</button>
                        </div>

                        <div class="max-h-[400px] overflow-y-auto scrollbar-hide space-y-3">
                            @forelse($foundPractitioners as $prac)
                            <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100 flex items-center justify-between group hover:bg-white hover:border-indigo-200 hover:shadow-lg hover:shadow-indigo-500/10 transition-all cursor-pointer"
                                 wire:click="selectPrac('{{ $prac['id'] ?? '' }}')">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-indigo-50 text-[#405189] flex items-center justify-center font-bold">
                                        {{ strtoupper(substr($prac['name'] ?? '?', 0, 1)) }}
                                    </div>
                                    <div>
                                        <h6 class="text-sm font-black text-gray-700 group-hover:text-[#405189]">{{ $prac['name'] ?? '-' }}</h6>
                                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest leading-none mt-1">ID: {{ $prac['id'] ?? '-' }}</p>
                                    </div>
                                </div>
                                <button class="btn btn-sm bg-indigo-600 text-white px-4 rounded-xl text-[10px] font-bold">PILIH</button>
                            </div>
                            @empty
                            <div class="py-12 text-center">
                                <i class="ri-file-search-line text-5xl text-gray-200 mb-3 block"></i>
                                <span class="text-sm text-gray-400 font-bold">Pencarian untuk NIK atau Nama di database SatuSehat.</span>
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>