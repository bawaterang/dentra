        <div x-data="{ 
                showModal: false, 
                searchBpjsPoliModal: false,
                init(){
                    this.$watch('showModal',v=>{
                        if(v){$nextTick(()=>{this.$refs.firstInput&&this.$refs.firstInput.focus()})}
                    })
                } 
            }" 
            @open-modal.window="showModal=true" 
            @close-modal.window="showModal=false" 
            @open-search-bpjs-poli-modal.window="searchBpjsPoliModal = true" 
            @close-search-bpjs-poli-modal.window="searchBpjsPoliModal = false"
            x-init="init()">
            <style>
                .glass-header {
                    background: rgba(255, 255, 255, 0.8) !important;
                    backdrop-filter: blur(8px);
                    -webkit-backdrop-filter: blur(8px);
                }
                .poli-row:hover {
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
                .kode-poli-chip {
                    font-family: 'JetBrains Mono', 'Fira Code', monospace;
                    background: #f1f5f9;
                    color: #475569;
                    padding: 4px 8px;
                    border-radius: 6px;
                    font-size: 0.75rem;
                    border: 1px solid #e2e8f0;
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
                        <i class="ri-hospital-line"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold tracking-tight text-[#2c3e50]">Master Data Poli</h1>
                        <p class="text-xs text-[#878a99] font-medium mt-0.5">Kelola data poli/klinik untuk pendaftaran pasien.</p>
                    </div>
                </div>
                <div class="page-header-breadcrumb">
                    <a href="/dashboard" wire:navigate class="hover:text-[#405189] transition-colors"><i class="ri-home-4-line"></i></a>
                    <span class="sep text-gray-300">/</span>
                    <span class="text-gray-400 font-medium">Master</span>
                    <span class="sep text-gray-300">/</span>
                    <span class="text-[#405189] font-bold">Poli</span>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 mb-8">
                <div class="group relative overflow-hidden bg-white rounded-2xl p-5 shadow-sm hover:shadow-xl transition-all duration-500 border-l-4 border-[#405189]">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-[#405189] group-hover:bg-indigo-500 group-hover:text-white transition-all duration-300 shadow-inner">
                            <i class="ri-database-2-line text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-[#878a99] font-bold text-[10px] uppercase tracking-[0.1em]">Total Poli</p>
                            <h4 class="text-2xl font-black text-[#2c3e50] leading-none mt-1">{{ number_format($totalPoli) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="group relative overflow-hidden bg-white rounded-2xl p-5 shadow-sm hover:shadow-xl transition-all duration-500 border-l-4 border-[#0ab39c]">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-[#0ab39c] group-hover:bg-emerald-500 group-hover:text-white transition-all duration-300 shadow-inner">
                            <i class="ri-checkbox-circle-line text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-[#878a99] font-bold text-[10px] uppercase tracking-[0.1em]">Poli Aktif</p>
                            <h4 class="text-2xl font-black text-[#2c3e50] leading-none mt-1 text-[#0ab39c]">{{ number_format($poliAktif) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="group relative overflow-hidden bg-white rounded-2xl p-5 shadow-sm hover:shadow-xl transition-all duration-500 border-l-4 border-[#f06548]">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-rose-50 text-[#f06548] group-hover:bg-rose-500 group-hover:text-white transition-all duration-300 shadow-inner">
                            <i class="ri-close-circle-line text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-[#878a99] font-bold text-[10px] uppercase tracking-[0.1em]">Tidak Aktif</p>
                            <h4 class="text-2xl font-black text-[#2c3e50] leading-none mt-1 text-[#f06548]">{{ number_format($takAktif) }}</h4>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="card mb-6">
                <div class="card-body p-0">
                    <ul class="flex flex-wrap text-sm font-medium text-center text-gray-500 border-b border-gray-200">
                        <li class="me-2">
                            <button wire:click="switchTab('polis')" class="inline-flex items-center justify-center p-4 border-b-2 rounded-t-lg transition-all {{ $activeTab === 'polis' ? 'text-[#405189] border-[#405189] font-bold bg-[#405189]/5' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">
                                <i class="ri-hospital-line mr-2 text-lg"></i>
                                Manajemen Poli
                            </button>
                        </li>
                        <li class="me-2">
                            <button wire:click="switchTab('mapping')" class="inline-flex items-center justify-center p-4 border-b-2 rounded-t-lg transition-all {{ $activeTab === 'mapping' ? 'text-[#f7b84b] border-[#f7b84b] font-bold bg-[#f7b84b]/5' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">
                                <i class="ri-user-star-line mr-2 text-lg"></i>
                                Pemetaan Poli ke Dokter
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            @if($activeTab === 'polis')
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
                                    <i class="ri-checkbox-circle-line"></i><span>Aktif</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $selectedStatus === 'Tidak Aktif' ? 'active active-pill-danger' : '' }}" 
                                   wire:click="setStatus('Tidak Aktif')" role="button">
                                    <i class="ri-close-circle-line"></i><span>Tidak Aktif</span>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div class="flex flex-wrap items-center gap-4 w-full lg:w-auto">
                        <div class="relative flex-grow min-w-[280px]">
                            <i class="ri-search-2-line absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-lg group-focus-within:text-[#405189]"></i>
                            <input type="text" wire:model.live.debounce.300ms="search" 
                                   class="w-full bg-gray-50/50 border border-gray-200 rounded-2xl py-2.5 pl-12 pr-4 text-sm font-medium outline-none transition-all search-focus-glow placeholder:text-gray-300" 
                                   placeholder="Cari kode atau nama poli...">
                        </div>
                        
                        <div class="grid grid-cols-2 gap-3 w-full lg:flex lg:w-auto lg:items-center lg:gap-1.5 lg:p-1 lg:rounded-lg lg:border lg:border-[#e2e8f0] lg:bg-white">
                            <a href="{{ route('master.poli.print', ['status' => $selectedStatus]) }}" target="_blank" 
                               class="flex flex-col lg:flex-row items-center justify-center gap-2 p-4 lg:p-0 lg:h-8 lg:w-8 rounded-2xl lg:rounded-md bg-white border border-gray-100 lg:border-none shadow-sm lg:shadow-none hover:bg-indigo-50 transition-all group/print" title="Cetak PDF">
                                <i class="ri-printer-line text-2xl lg:text-lg text-indigo-500 group-hover/print:scale-110 transition-transform"></i>
                                <span class="lg:hidden text-[10px] font-black text-gray-400 uppercase tracking-widest">Cetak PDF</span>
                            </a>
                            <div class="hidden lg:block w-[1px] h-4 bg-[#e2e8f0]"></div>
                            <a href="{{ route('master.poli.export', ['status' => $selectedStatus]) }}" target="_blank" 
                               class="flex flex-col lg:flex-row items-center justify-center gap-2 p-4 lg:p-0 lg:h-8 lg:w-8 rounded-2xl lg:rounded-md bg-white border border-gray-100 lg:border-none shadow-sm lg:shadow-none hover:bg-emerald-50 transition-all group/export" title="Unduh Excel">
                                <i class="ri-file-excel-2-line text-2xl lg:text-lg text-emerald-500 group-hover/export:scale-110 transition-transform"></i>
                                <span class="lg:hidden text-[10px] font-black text-gray-400 uppercase tracking-widest">Ekspor Excel</span>
                            </a>
                        </div>

                        <button @click="$wire.create()" class="btn btn-primary h-10 px-6 shadow-sm flex items-center justify-center gap-2 transition-all hover:translate-y-[-2px] hover:shadow-lg active:scale-95 w-full lg:w-auto">
                            <i class="ri-add-line text-xl"></i>
                            <span class="font-bold text-xs uppercase tracking-wider">Tambah Poli</span>
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50">
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Kode Poli</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Nama Poli</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Prefix Antrian</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Status</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($this->polis as $item)
                            <tr wire:key="poli-{{ $item->id }}" class="poli-row transition-all duration-200">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="kode-poli-chip shadow-sm font-bold">{{ $item->kode_poli }}</span>
                                </td>
                                <td class="px-6 py-4 min-w-[250px]">
                                    <div class="group relative">
                                        <div class="font-bold text-[#2c3e50] text-sm group-hover:text-[#405189] transition-colors line-clamp-1">{{ $item->nama_poli }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($item->prefix_antrian)
                                    <span class="kode-poli-chip shadow-sm font-bold text-[#405189]">{{ $item->prefix_antrian }}</span>
                                    @else
                                    <span class="text-xs text-gray-300 italic">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($item->status == 'Aktif')
                                    <span class="status-badge-modern bg-emerald-50 text-emerald-600 border border-emerald-100">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                                        Aktif
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
                                        <button wire:click="edit({{ $item->id }})" class="action-btn-soft bg-indigo-50 text-[#405189] hover:bg-[#405189] hover:text-white shadow-sm" title="Edit Data">
                                            <i class="ri-pencil-fill text-sm"></i>
                                        </button>
                                        <button @click="if('{{ $item->status }}'==='Tidak Aktif'){Swal.fire({title:'Informasi',text:'Poli ini sudah tidak aktif.',icon:'info',confirmButtonColor:'#405189'})}else{Swal.fire({title:'Konfirmasi Nonaktif',text:'Apakah Anda yakin ingin menonaktifkan poli {{ $item->nama_poli }}?',icon:'warning',showCancelButton:true,confirmButtonColor:'#f06548',cancelButtonColor:'#6c757d',confirmButtonText:'Ya, Nonaktifkan',cancelButtonText:'Batal',reverseButtons:true}).then((r)=>{if(r.isConfirmed){$wire.delete({{ $item->id }})}})}" 
                                                class="action-btn-soft bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white shadow-sm" title="Hapus/Nonaktif">
                                            <i class="ri-delete-bin-line text-sm"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="py-20 text-center">
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

                @if($this->polis->hasPages())
                <div class="px-6 py-5 sm:px-8 sm:py-6 bg-gray-50/50 border-t border-gray-100 pagination-custom">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-5">
                        <div class="text-[11px] font-bold text-[#878a99] tracking-tight text-center sm:text-left">
                            <i class="ri-list-check-2 text-[#405189] mr-1 hidden sm:inline"></i>
                            <span class="hidden sm:inline">Menampilkan</span> 
                            <span class="text-[#405189] font-black">{{ $this->polis->firstItem() }} - {{ $this->polis->lastItem() }}</span> 
                            dari <span class="text-[#405189] font-black">{{ number_format($this->polis->total()) }}</span> 
                            <span class="hidden sm:inline">poli terdaftar</span>
                            <span class="sm:hidden">total data</span>
                        </div>
                        {{ $this->polis->links() }}
                    </div>
                </div>
                @endif
            </div>
            @endif

            @if($activeTab === 'mapping')
            <!-- TAB: PEMETAAN (MAPPING) POLI KE DOKTER -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 animate-fade-in-up" x-data="{ dokterSearch: '' }">
                <!-- Select Poli Side -->
                <div class="card border-t-2 border-[#f7b84b] relative" style="overflow: visible !important;">
                    <div class="p-5 border-b border-[#eff2f7] bg-[#f3f6f9]/50">
                        <h6 class="text-sm font-bold text-[#f7b84b]"><i class="ri-hospital-line mr-2"></i>Pilih Poli Target</h6>
                        <p class="text-xs text-gray-500 mt-1">Pilih poli untuk mengatur dokter yang bertugas.</p>
                    </div>
                    <div class="p-5">
                        <x-custom-dropdown 
                            model="selectedPoliId" 
                            :options="collect($allPolisMapping)->map(fn($p) => ['value' => $p->id, 'label' => $p->nama_poli . ' (' . $p->kode_poli . ')', 'icon' => 'ri-building-3-line text-[#405189]'])->toArray()"
                            placeholder="Pilih Poli Target"
                            searchable="true"
                            icon="ri-hospital-line"
                            live="true"
                        />
                        
                        @if($selectedPoliId)
                            @php $selP = collect($allPolisMapping)->firstWhere('id', (int)$selectedPoliId); @endphp
                            <div class="mt-4 p-4 rounded-xl bg-orange-50 border border-orange-100">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="h-10 w-10 flex items-center justify-center rounded-full bg-orange-500 text-white font-bold">
                                        {{ strtoupper(substr($selP?->nama_poli ?? '', 0, 1)) }}
                                    </div>
                                    <div>
                                        <h6 class="font-bold text-[#495057] mb-0">{{ $selP?->nama_poli ?? '' }}</h6>
                                        <p class="text-xs text-gray-500">{{ $selP?->kode_poli ?? '' }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="badge bg-[#f7b84b] text-white px-2 py-1"><i class="ri-user-star-line mr-1"></i> {{ count($mappedDokters ?? []) }} Dokter Terpilih</span>
                                </div>
                            </div>
                        @else
                            <div class="mt-4 p-6 rounded-xl border border-dashed border-gray-300 flex flex-col items-center justify-center text-center">
                                <i class="ri-focus-3-line text-3xl text-gray-300 mb-2"></i>
                                <span class="text-sm text-gray-500">Gunakan dropdown di atas untuk memilih poli.</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Select Dokters Side -->
                <div class="card overflow-hidden lg:col-span-2 border-t-2 border-[#0ab39c] relative" style="overflow: visible !important;">
                    @if(!$selectedPoliId)
                    <div class="absolute inset-0 bg-white/60 backdrop-blur-sm z-50 flex flex-col items-center justify-center border border-gray-200 shadow-sm rounded-lg m-2">
                        <i class="ri-lock-2-line text-4xl text-gray-400 mb-3"></i>
                        <h5 class="text-gray-600 font-bold">Akses Pemetaan Terkunci</h5>
                        <p class="text-sm text-gray-500">Pilih Poli di panel sebelah kiri untuk membuka daftar dokter.</p>
                    </div>
                    @endif

                    <div class="p-5 border-b border-[#eff2f7] flex flex-col sm:flex-row justify-between sm:items-center gap-4 bg-[#f3f6f9]/50">
                        <div>
                            <h6 class="text-sm font-bold text-[#0ab39c]"><i class="ri-user-heart-line mr-2"></i>Daftar Dokter (Beri Centang)</h6>
                            <p class="text-xs text-gray-500 mt-1">Satu poli dapat memiliki lebih dari satu dokter.</p>
                        </div>
                        <div class="relative w-full sm:w-64">
                            <input type="text" x-model="dokterSearch" class="h-9 w-full rounded-lg border border-[#e9ecef] pl-9 pr-3 text-xs outline-none focus:border-[#0ab39c] focus:bg-white transition-all placeholder:text-[#adb5bd]" placeholder="Cari dokter...">
                            <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-[#878a99] text-sm"></i>
                        </div>
                    </div>
                    
                    <div class="p-0">
                        <div class="max-h-[500px] overflow-y-auto w-full grid grid-cols-1 sm:grid-cols-2 gap-0">
                            @foreach($allDokters as $dokter)
                                <label x-show="dokterSearch === '' || '{{ strtolower($dokter->nama_dokter) }}'.includes(dokterSearch.toLowerCase()) || '{{ strtolower($dokter->kode_dokter) }}'.includes(dokterSearch.toLowerCase())" class="border-b border-r border-[#eff2f7] p-4 flex items-center gap-4 cursor-pointer hover:bg-teal-50/30 transition-colors group">
                                    <div class="flex items-center justify-center w-6 h-6 shrink-0">
                                        <input type="checkbox" wire:model="mappedDokters" value="{{ $dokter->id }}" class="w-5 h-5 text-[#0ab39c] bg-gray-100 border-gray-300 rounded focus:ring-[#0ab39c] transition-all cursor-pointer">
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 flex items-center justify-center rounded-lg text-white font-bold text-xs" style="background-color: {{ $dokter->color ?? '#405189' }}">
                                            {{ strtoupper(substr($dokter->nama_dokter, 0, 1)) }}
                                        </div>
                                        <div>
                                            <h6 class="mb-0 text-sm font-bold text-[#495057] group-hover:text-[#0ab39c] transition-colors">{{ $dokter->nama_dokter }}</h6>
                                            <p class="text-xs text-gray-500 mb-0 mt-0.5">{{ $dokter->kode_dokter }}</p>
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    
                    <div class="p-5 border-t border-[#eff2f7] bg-gray-50 flex justify-end">
                        <button type="button" wire:click="saveMapping" class="btn bg-[#0d6efd] text-white px-8 h-10 shadow-md flex items-center gap-2 transition-all hover:bg-blue-600 hover:translate-y-[-2px] hover:shadow-lg">
                            <i class="ri-save-3-line text-lg"></i> <span class="font-bold">Simpan Pemetaan</span>
                        </button>
                    </div>
                </div>
            </div>
            @endif

            <div x-show="showModal" class="fixed inset-0 z-[1050] flex items-center justify-center p-4" x-transition.opacity style="display: none;">
                <div class="absolute inset-0 bg-[#0a192f]/60 backdrop-blur-md"></div>
                <div x-show="showModal" x-transition.scale.95 
                     class="relative w-full max-w-xl bg-white rounded-2xl sm:rounded-3xl shadow-2xl overflow-hidden border border-white/20 animate-in fade-in zoom-in duration-300 mx-2 sm:mx-0">
                    
                    <div class="px-5 py-4 sm:px-8 sm:py-6 bg-gradient-to-r from-gray-50 to-white border-b border-gray-100 flex items-center justify-between">
                        <div class="flex items-center gap-2 sm:gap-3">
                            <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-indigo-50 text-[#405189] flex items-center justify-center shadow-inner">
                                <i class="ri-hospital-line text-lg sm:text-xl"></i>
                            </div>
                            <div>
                                <h5 class="text-sm sm:text-base font-black text-[#2c3e50] tracking-tight">{{ $isEdit ? 'Update Data Poli' : 'Poli Baru' }}</h5>
                                <p class="text-[9px] sm:text-[10px] text-gray-400 font-bold uppercase tracking-widest hidden sm:block">Lengkapi informasi poli di bawah</p>
                            </div>
                        </div>
                        <button @click="showModal = false" class="w-8 h-8 rounded-full flex items-center justify-center text-gray-400 hover:bg-gray-100 transition-all"><i class="ri-close-line text-lg"></i></button>
                    </div>

                    <div class="px-5 py-6 sm:px-8 sm:py-8 max-h-[70vh] overflow-y-auto scrollbar-hide">
                        <form wire:submit.prevent="save" class="space-y-5 sm:space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 sm:gap-6">
                                <div class="space-y-1.5">
                                    <label class="text-[9px] sm:text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">Kode Poli <span class="text-rose-500">*</span></label>
                                    <input type="text" wire:model="kode_poli" x-ref="firstInput" 
                                           class="w-full bg-gray-50 border border-gray-100 rounded-xl sm:rounded-2xl py-2.5 sm:py-3 px-4 text-sm font-black text-[#405189] uppercase tracking-wider focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-[#405189] transition-all outline-none @error('kode_poli') border-rose-300 bg-rose-50/30 @enderror {{ ($isEdit || $kodeReadonly) ? 'bg-gray-100 cursor-not-allowed' : '' }}" 
                                           placeholder="POL001" {{ ($isEdit || $kodeReadonly) ? 'readonly' : '' }}>
                                    @error('kode_poli') <span class="text-[10px] text-rose-500 font-bold px-1">{{ $message }}</span> @enderror
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[9px] sm:text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">Status</label>
                                    <x-custom-dropdown 
                                        model="status" 
                                        :options="[
                                            ['value' => 'Aktif', 'label' => 'Aktif', 'icon' => 'ri-checkbox-circle-line text-emerald-500'],
                                            ['value' => 'Tidak Aktif', 'label' => 'Tidak Aktif', 'icon' => 'ri-close-circle-line text-rose-500']
                                        ]"
                                        placeholder="Pilih Status"
                                    />
                                </div>
                            </div>

                             <div class="space-y-1.5">
                                <label class="text-[9px] sm:text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">Nama Poli <span class="text-rose-500">*</span></label>
                                <input type="text" wire:model="nama_poli" 
                                       class="w-full bg-gray-50 border border-gray-100 rounded-xl sm:rounded-2xl py-2.5 sm:py-3 px-4 text-sm font-bold text-[#2c3e50] focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-[#405189] transition-all outline-none @error('nama_poli') border-rose-300 bg-rose-50/30 @enderror" 
                                       placeholder="Contoh: Poli Gigi & Mulut">
                                @error('nama_poli') <span class="text-[10px] text-rose-500 font-bold px-1">{{ $message }}</span> @enderror
                            </div>

                            <div class="space-y-1.5">
                                <label class="text-[9px] sm:text-[10px] font-black text-emerald-600 uppercase tracking-widest px-1">BPJS ID (Poli)</label>
                                <div class="flex gap-2">
                                    <div class="relative flex-1">
                                        <i class="ri-hospital-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                        <input type="text" wire:model="poli_bpjs_id" class="w-full pl-10 rounded-xl border-gray-200 text-sm py-2.5 focus:border-[#0ab39c] focus:ring focus:ring-[#0ab39c]/20 transition-all bg-emerald-50/30" placeholder="001">
                                    </div>
                                    <button type="button" wire:click="searchBpjsPoli" class="bg-[#0ab39c] text-white px-4 rounded-xl text-xs font-bold shadow-sm hover:bg-emerald-600 transition-colors">
                                        <i class="ri-search-line"></i>
                                    </button>
                                </div>
                                @error('poli_bpjs_id') <span class="text-[10px] text-rose-500 font-bold px-1">{{ $message }}</span> @enderror
                            </div>

                            <div class="space-y-1.5">
                                <label class="text-[9px] sm:text-[10px] font-black text-[#405189] uppercase tracking-widest px-1">Prefix Antrian</label>
                                <div class="relative">
                                    <i class="ri-hashtag absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                    <input type="text" wire:model="prefix_antrian" maxlength="5"
                                           class="w-full pl-10 bg-gray-50 border border-gray-100 rounded-xl sm:rounded-2xl py-2.5 sm:py-3 px-4 text-sm font-black text-[#405189] uppercase tracking-wider focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-[#405189] transition-all outline-none @error('prefix_antrian') border-rose-300 bg-rose-50/30 @enderror"
                                           placeholder="Contoh: A, B, POL">
                                </div>
                                <p class="text-[10px] text-gray-400 px-1">Huruf prefix untuk nomor antrian poli ini (misal: A → A-001). Kosongkan untuk menggunakan default.</p>
                                @error('prefix_antrian') <span class="text-[10px] text-rose-500 font-bold px-1">{{ $message }}</span> @enderror
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

            <!-- Search BPJS Poli Modal -->
            <div x-show="searchBpjsPoliModal" class="fixed inset-0 z-[1100] flex items-center justify-center p-4" x-transition.opacity style="display: none;">
                <div class="absolute inset-0 bg-[#0a192f]/80 backdrop-blur-sm"></div>
                <div x-show="searchBpjsPoliModal" x-transition.scale.95 
                     class="relative w-full max-w-2xl bg-white rounded-3xl shadow-2xl overflow-hidden animate-in zoom-in duration-300">
                    <div class="px-8 py-6 bg-emerald-600 text-white flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <i class="ri-hospital-line text-2xl"></i>
                            <div>
                                <h5 class="text-lg font-black tracking-tight">Cari Poli BPJS</h5>
                                <p class="text-[10px] text-emerald-100 font-bold uppercase tracking-widest">Referensi Poliklinik PCare BPJS</p>
                            </div>
                        </div>
                        <button @click="searchBpjsPoliModal = false" class="text-white/50 hover:text-white transition-colors"><i class="ri-close-line text-2xl"></i></button>
                    </div>
                    <div class="p-8">
                        <div class="relative mb-6">
                            <input type="text" wire:model.live.debounce.300ms="searchBpjsPoliQuery" 
                                   class="w-full bg-gray-50 border border-gray-100 rounded-2xl py-3 pl-12 pr-4 text-sm font-bold focus:bg-white transition-all outline-none" 
                                   placeholder="Cari Nama atau Kode Poli...">
                            <i class="ri-search-2-line absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        </div>
                        <div class="max-h-[400px] overflow-y-auto scrollbar-hide space-y-3">
                            @forelse($foundBpjsPolis as $bpjsPoli)
                            <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100 flex items-center justify-between hover:bg-white hover:border-emerald-200 transition-all cursor-pointer"
                                 wire:click="selectBpjsPoli('{{ $bpjsPoli['kdPoli'] ?? '' }}')">
                                <div>
                                    <h6 class="text-sm font-black text-gray-700">{{ $bpjsPoli['nmPoli'] ?? '-' }}</h6>
                                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Kode: {{ $bpjsPoli['kdPoli'] ?? '-' }}</p>
                                </div>
                                <button class="btn btn-sm bg-emerald-600 text-white px-4 rounded-xl text-[10px] font-bold">PILIH</button>
                            </div>
                            @empty
                            <div class="py-12 text-center text-gray-400 italic">Belum ada hasil pencarian.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>