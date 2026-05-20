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
                    font-size: 8px !important;
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
                        <i class="ri-file-add-line"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold tracking-tight text-[#2c3e50]">Pendaftaran Pasien</h1>
                        <p class="text-xs text-[#878a99] font-medium mt-0.5">Kelola pendaftaran kunjungan pasien baru dan lama.</p>
                    </div>
                </div>
                <div class="page-header-breadcrumb">
                    <a href="/dashboard" wire:navigate class="hover:text-[#405189] transition-colors"><i class="ri-home-4-line"></i></a>
                    <span class="sep text-gray-300">/</span>
                    <span class="text-gray-400 font-medium">Pendaftaran</span>
                    <span class="sep text-gray-300">/</span>
                    <span class="text-[#405189] font-bold">List Pendaftaran</span>
                </div>
            </div>

            <!-- Info Cards -->
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-8">
                <div class="group relative overflow-hidden bg-white rounded-2xl p-5 shadow-sm hover:shadow-xl transition-all duration-500 border-l-4 border-[#405189]">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-[#405189] group-hover:bg-indigo-500 group-hover:text-white transition-all duration-300 shadow-inner">
                            <i class="ri-file-add-line text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-[#878a99] font-bold text-[10px] uppercase tracking-[0.1em]">Total Pendaftaran</p>
                            <h4 class="text-2xl font-black text-[#2c3e50] leading-none mt-1">{{ number_format($totalPendaftaran) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="group relative overflow-hidden bg-white rounded-2xl p-5 shadow-sm hover:shadow-xl transition-all duration-500 border-l-4 border-[#0ab39c]">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-[#0ab39c] group-hover:bg-emerald-500 group-hover:text-white transition-all duration-300 shadow-inner">
                            <i class="ri-checkbox-circle-line text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-[#878a99] font-bold text-[10px] uppercase tracking-[0.1em]">Terdaftar</p>
                            <h4 class="text-2xl font-black text-[#2c3e50] leading-none mt-1 text-[#0ab39c]">{{ number_format($terdaftar) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="group relative overflow-hidden bg-white rounded-2xl p-5 shadow-sm hover:shadow-xl transition-all duration-500 border-l-4 border-[#f7b84b]">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 text-[#f7b84b] group-hover:bg-amber-500 group-hover:text-white transition-all duration-300 shadow-inner">
                            <i class="ri-time-line text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-[#878a99] font-bold text-[10px] uppercase tracking-[0.1em]">Menunggu Screening</p>
                            <h4 class="text-2xl font-black text-[#2c3e50] leading-none mt-1 text-[#f7b84b]">{{ number_format($menungguScreening) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="group relative overflow-hidden bg-white rounded-2xl p-5 shadow-sm hover:shadow-xl transition-all duration-500 border-l-4 border-[#3577f1]">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-[#3577f1] group-hover:bg-blue-500 group-hover:text-white transition-all duration-300 shadow-inner">
                            <i class="ri-check-double-line text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-[#878a99] font-bold text-[10px] uppercase tracking-[0.1em]">Selesai</p>
                            <h4 class="text-2xl font-black text-[#2c3e50] leading-none mt-1 text-[#3577f1]">{{ number_format($selesai) }}</h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Calendar Sidebar -->
                <div class="lg:col-span-1">
                    <div class="card shadow-sm border-t-2 border-[#405189]">
                        <div class="p-4 border-b border-[#eff2f7]"><h6 class="text-xs font-bold text-[#405189] uppercase tracking-widest mb-0"><i class="ri-calendar-line mr-1"></i>Pilih Tanggal</h6></div>
                        <div class="p-4">
                            <div class="flex items-center gap-2">
                                <button wire:click="prevDate" class="flex h-[42px] w-10 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 hover:border-[#405189] hover:text-[#405189] hover:bg-indigo-50 transition-all group" title="Hari Sebelumnya">
                                    <i class="ri-arrow-left-s-line text-xl group-hover:scale-110 transition-transform"></i>
                                </button>
                                
                                <div class="relative flex-grow">
                                    <input type="date" wire:model.live="selectedDate" class="w-full rounded-lg border-gray-200 text-sm px-4 h-[42px] focus:border-[#405189] transition-all text-center font-bold text-[#405189] appearance-none cursor-pointer">
                                </div>

                                <button wire:click="nextDate" class="flex h-[42px] w-10 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 hover:border-[#405189] hover:text-[#405189] hover:bg-indigo-50 transition-all group" title="Hari Berikutnya">
                                    <i class="ri-arrow-right-s-line text-xl group-hover:scale-110 transition-transform"></i>
                                </button>
                            </div>
                            <div class="mt-3 text-center p-2 bg-indigo-50/50 rounded-xl border border-indigo-100/50">
                                <p class="text-[10px] text-[#878a99] font-bold uppercase tracking-widest mb-0.5">Tanggal Terpilih</p>
                                <p class="font-black text-[#405189] text-xs">{{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('l, d F Y') }}</p>
                            </div>
                        </div>
                        <div class="p-4 border-t border-[#eff2f7]">
                            <a href="{{ route('pendaftaran.create') }}" wire:navigate class="btn btn-primary w-full h-10 flex items-center justify-center gap-2 text-xs font-bold uppercase tracking-wider"><i class="ri-add-circle-line text-lg"></i> Pendaftaran Baru</a>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="lg:col-span-3">
                    <div class="card overflow-hidden border-t-2 border-[#405189]">
                        <div class="p-4 border-b border-[#eff2f7]"><div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                            <div class="flex overflow-x-auto scrollbar-hide -mx-2 px-2 lg:mx-0 lg:px-0"><ul class="nav-pills-custom">
                                <li class="nav-item"><a class="nav-link {{ $selectedStatus === 'all' ? 'active active-pill-primary' : '' }}" wire:click="setStatus('all')" role="button"><i class="ri-layout-grid-line"></i><span>Semua</span></a></li>
                                <li class="nav-item"><a class="nav-link {{ $selectedStatus === 'terdaftar' ? 'active active-pill-success' : '' }}" wire:click="setStatus('terdaftar')" role="button"><i class="ri-checkbox-circle-line"></i><span>Terdaftar</span></a></li>
                                <li class="nav-item"><a class="nav-link {{ $selectedStatus === 'menunggu_screening' ? 'active active-pill-warning' : '' }}" wire:click="setStatus('menunggu_screening')" role="button"><i class="ri-time-line"></i><span>Menunggu Screening</span></a></li>
                                <li class="nav-item"><a class="nav-link {{ $selectedStatus === 'selesai' ? 'active active-pill-success' : '' }}" wire:click="setStatus('selesai')" role="button"><i class="ri-check-double-line"></i><span>Selesai</span></a></li>
                            </ul></div>

                            <div class="flex items-center gap-3 w-full lg:w-auto">
                                <div class="bg-gray-100 p-1 rounded-xl flex items-center shrink-0">
                                    <button wire:click="$set('viewMode', 'table')" class="w-9 h-8 flex items-center justify-center rounded-lg transition-all {{ $viewMode === 'table' ? 'bg-white shadow-sm text-[#405189]' : 'text-gray-500 hover:text-gray-700' }}" title="Tampilan Tabel">
                                        <i class="ri-list-check"></i>
                                    </button>
                                    <button wire:click="$set('viewMode', 'grid')" class="w-9 h-8 flex items-center justify-center rounded-lg transition-all {{ $viewMode === 'grid' ? 'bg-white shadow-sm text-[#405189]' : 'text-gray-500 hover:text-gray-700' }}" title="Tampilan Grup Waktu">
                                        <i class="ri-grid-fill"></i>
                                    </button>
                                </div>
                                <div class="relative flex-grow max-w-[320px] lg:w-80">
                                    <i class="ri-search-2-line absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                                    <input type="text" wire:model.live.debounce.300ms="search" class="w-full bg-gray-50 border border-gray-200 rounded-2xl py-2 pl-11 pr-4 text-sm font-medium outline-none transition-all focus:border-[#405189] focus:ring-4 focus:ring-[#405189]/5 placeholder:text-gray-300" placeholder="Cari pasien atau kunjungan...">
                                </div>
                            </div>
                        </div></div>
                        @if($viewMode === 'table')
                        <div class="card-body p-0"><div class="overflow-x-auto dark:bg-transparent">
                            <table class="w-full text-left border-collapse">
                                <thead class="bg-gray-50/50">
                                    <tr>
                                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 text-center">No Kunjungan</th>
                                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Pasien</th>
                                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Poli & Dokter</th>
                                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Asuransi</th>
                                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">Status</th>
                                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    @forelse($this->pendaftaranList as $item)
                                    <tr wire:key="daftar-{{ $item->id }}" class="custom-row transition-all duration-200">
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <span class="font-mono font-bold text-[#405189] text-xs px-2 py-1 bg-[#405189]/5 rounded">{{ $item->nomor_kunjungan }}</span>
                                            @if($item->antrian && $item->antrian->time_slot)
                                                <div class="text-[10px] text-emerald-600 font-bold mt-1.5 flex items-center justify-center gap-1">
                                                    <i class="ri-time-line"></i> Jam: {{ substr($item->antrian->time_slot, 0, 5) }} WIB
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="font-bold text-[#2c3e50] text-sm">{{ $item->pasien->nama_pasien ?? '-' }}</div>
                                            <span class="text-[11px] font-mono text-gray-400 mt-0.5 inline-block">{{ $item->pasien->no_rm ?? '' }}</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="font-bold text-[#495057] text-sm">{{ $item->poli->nama_poli ?? '-' }}</div>
                                            <div class="text-[10px] text-gray-400 font-medium italic mt-0.5"><i class="ri-user-star-line mr-0.5"></i> {{ $item->dokter->nama_dokter ?? '-' }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-600">{{ $item->asuransi?->nama_asuransi ?? 'Umum' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @php $sc = ['terdaftar'=>'bg-info-subtle text-info','menunggu_screening'=>'bg-warning-subtle text-amber-600','selesai'=>'bg-success-subtle text-emerald-600']; @endphp
                                            <span class="px-2.5 py-1.5 rounded-lg text-xs font-bold w-max gap-1.5 flex items-center {{ $sc[$item->status] ?? 'bg-secondary-subtle' }}">
                                                {{ ucfirst(str_replace('_',' ',$item->status)) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center justify-center gap-2">
                                                <button wire:click="editPendaftaran({{ $item->id }})" class="w-8 h-8 rounded-full flex items-center justify-center bg-orange-50 text-orange-500 hover:bg-orange-500 hover:text-white transition-all shadow-sm" title="Edit"><i class="ri-edit-line"></i></button>
                                                <a href="{{ URL::signedRoute('pendaftaran.print', ['id' => $item->id]) }}" target="_blank" class="w-8 h-8 rounded-full flex items-center justify-center bg-indigo-50 text-[#405189] hover:bg-[#405189] hover:text-white transition-all shadow-sm" title="Cetak"><i class="ri-printer-line"></i></a>
                                                <button @click="
                                                    Swal.fire({
                                                        title: 'Konfirmasi',
                                                        text: 'Apakah Anda yakin ingin membatalkan pendaftaran pasien ini?',
                                                        icon: 'warning',
                                                        showCancelButton: true,
                                                        confirmButtonColor: '#f06548',
                                                        cancelButtonColor: '#6c757d',
                                                        confirmButtonText: 'Ya, Batalkan!',
                                                        cancelButtonText: 'Kembali',
                                                        reverseButtons: true
                                                    }).then((result) => {
                                                        if (result.isConfirmed) {
                                                            $wire.deletePendaftaran({{ $item->id }})
                                                        }
                                                    })
                                                " class="w-8 h-8 rounded-full flex items-center justify-center bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white transition-all shadow-sm" title="Batal/Hapus"><i class="ri-delete-bin-line"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="py-16 text-center">
                                            <div class="flex flex-col items-center justify-center">
                                                <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                                    <i class="ri-file-add-line text-4xl text-gray-300"></i>
                                                </div>
                                                <p class="text-base font-bold text-gray-500">Belum ada pendaftaran</p>
                                                <p class="text-xs text-gray-400 mt-1">Belum ada pasien yang melakukan registrasi hari ini.</p>
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
                                        <span class="hidden sm:inline">pendaftaran</span>
                                    </div>
                                    {{ $this->pendaftaranList->links() }}
                                </div>
                            </div>
                            @endif
                        </div></div>
                        @else
                        <div class="p-6 bg-gray-50/50 min-h-[400px]">
                            @php $groupedData = $this->groupedPendaftaranList; @endphp
                            
                            @if(count($groupedData) === 0)
                                <div class="flex flex-col items-center justify-center py-16">
                                    <div class="w-20 h-20 bg-white shadow-sm rounded-full flex items-center justify-center mb-4">
                                        <i class="ri-file-add-line text-4xl text-gray-300"></i>
                                    </div>
                                    <p class="text-base font-bold text-gray-500">Belum ada data pendaftaran</p>
                                    <p class="text-xs text-gray-400 mt-1">Gunakan filter pencarian atau ubah tanggal.</p>
                                </div>
                            @else
                                <div class="space-y-10">
                                    @foreach($groupedData as $slot => $items)
                                        <div>
                                            <div class="flex items-center gap-4 mb-5">
                                                <div class="h-9 px-5 rounded-full bg-white border-2 border-[#405189] text-[#405189] flex items-center justify-center font-bold text-sm shadow-sm">
                                                    @if($slot === 'Walk-in')
                                                        <i class="ri-walk-line mr-2 text-lg"></i> Walk-in (Tanpa Waktu)
                                                    @else
                                                        <i class="ri-time-line mr-2 text-lg"></i> {{ $slot }} WIB
                                                    @endif
                                                </div>
                                                <div class="h-px bg-gray-200 flex-1"></div>
                                                <div class="text-xs font-bold text-gray-400 uppercase tracking-widest bg-white px-3 py-1 rounded-md border border-gray-100 shadow-sm">{{ count($items) }} Pasien</div>
                                            </div>
                                            
                                            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                                                @foreach($items as $item)
                                                     <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm hover:shadow-lg transition-all duration-300 group relative overflow-hidden flex flex-col" style="{{ $item->dokter?->color ? 'background-color: ' . $item->dokter->color . '08;' : '' }}">
                                                          @php 
                                                             $sc = ['terdaftar'=>'bg-info-subtle text-info','menunggu_screening'=>'bg-warning-subtle text-amber-600','selesai'=>'bg-success-subtle text-emerald-600']; 
                                                             $borderColors = ['terdaftar'=>'bg-info','menunggu_screening'=>'bg-amber-500','selesai'=>'bg-emerald-500'];
                                                             $doctorColor = $item->dokter?->color ?? '#405189';
                                                          @endphp
                                                          <div class="absolute left-0 top-0 bottom-0 w-1.5" style="background-color: {{ $doctorColor }}"></div>
                                                          
                                                          <div class="flex justify-between items-start mb-3 pl-2">
                                                               <div class="flex items-center gap-2">
                                                                  <span class="font-mono font-bold text-[#405189] text-xs px-2 py-1 bg-[#405189]/5 rounded">{{ $item->nomor_kunjungan }}</span>
                                                                  <span class="px-2 py-1 rounded-md text-[9px] font-black uppercase tracking-widest bg-gray-100 text-gray-500 border border-gray-200">{{ $item->asuransi?->nama_asuransi ?? 'Umum' }}</span>
                                                               </div>
                                                               <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold flex items-center gap-1.5 {{ $sc[$item->status] ?? 'bg-secondary-subtle' }}">
                                                                   <span class="h-1.5 w-1.5 rounded-full {{ str_contains($borderColors[$item->status] ?? '', '#405189') ? 'bg-current animate-ping' : 'bg-current' }}"></span>
                                                                   {{ ucfirst(str_replace('_',' ',$item->status)) }}
                                                               </span>
                                                          </div>
                                                          <div class="mb-4 pl-2 flex-grow">
                                                               <h5 class="text-base font-black text-[#2c3e50] mb-1 leading-tight">{{ $item->pasien?->nama_pasien ?? '-' }}</h5>
                                                               <div class="text-[11px] font-mono text-gray-400">RM: {{ $item->pasien?->no_rm }}</div>
                                                          </div>
                                                          
                                                          <div class="pt-3 border-t border-gray-50 pl-2">
                                                               <div class="flex items-center gap-3">
                                                                   <div class="h-8 w-8 rounded-full flex items-center justify-center shrink-0" style="background-color: {{ $doctorColor }}15; color: {{ $doctorColor }}">
                                                                       <i class="ri-stethoscope-line text-lg"></i>
                                                                   </div>
                                                                   <div>
                                                                       <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">{{ $item->poli?->nama_poli ?? '-' }}</p>
                                                                       <p class="text-xs font-semibold" style="color: {{ $doctorColor }}">{{ $item->dokter?->nama_dokter ?? '-' }}</p>
                                                                   </div>
                                                               </div>
                                                          </div>
                                                          
                                                          <!-- Hover Actions Overlay -->
                                                          <div class="absolute inset-0 bg-white rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center gap-2 z-10">
                                                              <button wire:click="editPendaftaran({{ $item->id }})" class="w-10 h-10 rounded-full flex items-center justify-center bg-orange-50 text-orange-500 hover:bg-orange-500 hover:text-white hover:-translate-y-1 transition-all shadow-sm" title="Edit"><i class="ri-edit-line text-lg"></i></button>
                                                              <a href="{{ URL::signedRoute('pendaftaran.print', ['id' => $item->id]) }}" target="_blank" class="w-10 h-10 rounded-full flex items-center justify-center bg-indigo-50 text-[#405189] hover:bg-[#405189] hover:text-white hover:-translate-y-1 transition-all shadow-sm" title="Cetak"><i class="ri-printer-line text-lg"></i></a>
                                                              <button @click="
                                                                    Swal.fire({
                                                                        title: 'Konfirmasi',
                                                                        text: 'Apakah Anda yakin ingin membatalkan pendaftaran pasien ini?',
                                                                        icon: 'warning',
                                                                        showCancelButton: true,
                                                                        confirmButtonColor: '#f06548',
                                                                        cancelButtonColor: '#6c757d',
                                                                        confirmButtonText: 'Ya, Batalkan!',
                                                                        cancelButtonText: 'Kembali',
                                                                        reverseButtons: true
                                                                    }).then((result) => {
                                                                        if (result.isConfirmed) {
                                                                            $wire.deletePendaftaran({{ $item->id }})
                                                                        }
                                                                    })
                                                                " class="w-10 h-10 rounded-full flex items-center justify-center bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white hover:-translate-y-1 transition-all shadow-sm" title="Batal/Hapus"><i class="ri-delete-bin-line text-lg"></i></button>
                                                         </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        @endif
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
                            <h3 class="text-white font-bold flex items-center gap-2"><i class="ri-edit-box-line text-xl"></i> Edit Pendaftaran</h3>
                            <button wire:click="closeEditModal" type="button" class="text-white/80 hover:text-white transition-colors"><i class="ri-close-line text-2xl"></i></button>
                        </div>
                        <form wire:submit.prevent="updatePendaftaran">
                            <div class="px-8 py-6 max-h-[70vh] overflow-y-auto custom-scrollbar">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                                    <!-- Kolom Kiri: Informasi Kunjungan -->
                                    <div class="space-y-6">
                                        <div class="flex items-center gap-3 mb-6">
                                            <div class="w-10 h-10 rounded-xl bg-[#405189]/10 text-[#405189] flex items-center justify-center text-xl shadow-inner"><i class="ri-information-line"></i></div>
                                            <div>
                                                <h6 class="text-xs font-black text-[#405189] uppercase tracking-[2.5px] m-0">Informasi Kunjungan</h6>
                                                <p class="text-[10px] text-gray-400 font-medium">Pilih poli, dokter, dan asuransi</p>
                                            </div>
                                        </div>
                                        
                                        <div class="bg-gray-50/50 p-6 rounded-2xl border border-gray-100 shadow-sm space-y-5">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div><label class="block text-xs font-bold text-gray-500 ml-1 uppercase tracking-wider mb-1.5">Tanggal Kunjungan <span class="text-red-500">*</span></label><input type="date" wire:model.live="editTanggalAntrian" class="w-full rounded-lg border-gray-200 text-sm px-4 h-[42px] focus:border-[#405189] transition-all"></div>
                                                <div>
                                                    <label class="block text-xs font-bold text-gray-500 ml-1 uppercase tracking-wider mb-1.5">Jenis Kunjungan</label>
                                                    <x-custom-dropdown model="editJenisAntrian" :options="[
                                                        ['value' => 'offline', 'label' => 'Offline (Datang Langsung)', 'icon' => 'ri-walk-line text-blue-500'],
                                                        ['value' => 'online', 'label' => 'Online (Booking)', 'icon' => 'ri-global-line text-green-500'],
                                                        ['value' => 'mobile_jkn', 'label' => 'Mobile JKN', 'icon' => 'ri-smartphone-line text-purple-500']
                                                    ]" placeholder="Pilih Jenis" />
                                                </div>
                                            </div>
                                            
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-xs font-bold text-gray-500 ml-1 uppercase tracking-wider mb-1.5">Poli Tujuan <span class="text-red-500">*</span></label>
                                                    <x-custom-dropdown model="editPoliId" :options="$poliList" placeholder="Pilih Poli..." live="true" />
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-bold text-gray-500 ml-1 uppercase tracking-wider mb-1.5">Dokter <span class="text-red-500">*</span></label>
                                                    <x-custom-dropdown model="editDokterId" :options="$dokterList" placeholder="Pilih Dokter..." live="true" />
                                                </div>
                                            </div>

                                            @if($editModeAntrian !== 'Nomor Urut')
                                            <div class="p-4 bg-blue-50 border border-blue-100 rounded-lg">
                                                <label class="block text-xs font-bold text-[#405189] mb-2">Slot Waktu Periksa <span class="text-red-500">*</span></label>
                                                @if(count($editAvailableTimeSlots) > 0)
                                                    <x-custom-dropdown model="editTimeSlot" :options="$editAvailableTimeSlots" placeholder="Pilih Slot Waktu..." />
                                                @else
                                                    <div class="text-xs text-orange-600 font-bold flex items-center gap-2"><i class="ri-error-warning-line"></i> Tidak ada slot waktu tersedia.</div>
                                                @endif
                                            </div>
                                            @endif
                                            
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-xs font-bold text-gray-500 ml-1 uppercase tracking-wider mb-1.5">Asuransi / Penjamin</label>
                                                    <x-custom-dropdown model="editAsuransiId" :options="$asuransiList" placeholder="Pilih Asuransi..." />
                                                </div>
                                                <div class="space-y-1.5">
                                                    <label class="block text-xs font-bold text-gray-500 ml-1 uppercase tracking-wider">No Kartu Asuransi</label>
                                                    <div class="relative group">
                                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none group-focus-within:text-[#405189] transition-colors text-gray-400">
                                                            <i class="ri-id-card-line"></i>
                                                        </div>
                                                        <input type="text" wire:model="editNoKartuAsuransi" wire:key="edit-nokartu" class="block w-full pl-10 pr-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm transition-all focus:ring-4 focus:ring-[#405189]/10 focus:border-[#405189]" placeholder="Masukkan nomor kartu...">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Kolom Kanan: Data Klinis & SOAP -->
                                    <div class="space-y-6">
                                        <div class="flex items-center gap-3 mb-6">
                                            <div class="w-10 h-10 rounded-xl bg-[#0ab39c]/10 text-[#0ab39c] flex items-center justify-center text-xl shadow-inner"><i class="ri-heart-pulse-line"></i></div>
                                            <div>
                                                <h6 class="text-xs font-black text-[#0ab39c] uppercase tracking-[2.5px] m-0">Pemeriksaan Awal</h6>
                                                <p class="text-[10px] text-gray-400 font-medium">Vitals & riwayat medis dasar</p>
                                            </div>
                                        </div>

                                        <div class="bg-white border border-gray-100 rounded-3xl shadow-sm p-6 space-y-6">
                                            <!-- Vitals Grid -->
                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="col-span-2">
                                                    <label class="block text-xs font-bold text-gray-500 mb-1.5 ml-1 uppercase tracking-wider text-[10px]">Tingkat Kesadaran</label>
                                                    <x-custom-dropdown model="editKesadaran" :options="$kesadaranList" placeholder="Pilih Kesadaran" />
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-bold text-gray-500 mb-1.5 ml-1 uppercase tracking-wider text-[10px]">Tekanan Darah</label>
                                                    <div class="relative">
                                                        <input type="text" wire:model="editTd" wire:key="edit-td" class="block w-full pl-4 pr-16 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:border-[#0ab39c] transition-all" placeholder="120/80">
                                                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-xs font-bold text-gray-400">mmHg</div>
                                                    </div>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-bold text-gray-500 mb-1.5 ml-1 uppercase tracking-wider text-[10px]">Nadi</label>
                                                    <div class="relative">
                                                        <input type="text" wire:model="editNadi" wire:key="edit-nadi" class="block w-full pl-4 pr-12 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:border-[#0ab39c] transition-all" placeholder="80">
                                                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-xs font-bold text-gray-400">bpm</div>
                                                    </div>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-bold text-gray-500 mb-1.5 ml-1 uppercase tracking-wider text-[10px]">Suhu</label>
                                                    <div class="relative">
                                                        <input type="number" step="0.1" wire:model="editSuhu" wire:key="edit-suhu" class="block w-full pl-4 pr-12 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:border-[#0ab39c] transition-all" placeholder="36.5">
                                                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-xs font-bold text-gray-400">°C</div>
                                                    </div>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-bold text-gray-500 mb-1.5 ml-1 uppercase tracking-wider text-[10px]">Berat Badan</label>
                                                    <div class="relative">
                                                        <input type="number" step="0.1" wire:model="editBb" wire:key="edit-bb" class="block w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:border-[#0ab39c] transition-all" placeholder="60">
                                                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-xs font-bold text-gray-400">kg</div>
                                                    </div>
                                                </div>
                                                <div class="col-span-2">
                                                    <label class="block text-xs font-bold text-gray-500 mb-1.5 ml-1 uppercase tracking-wider text-[10px]">Tinggi Badan</label>
                                                    <div class="relative">
                                                        <input type="number" step="0.1" wire:model="editTb" wire:key="edit-tb" class="block w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:border-[#0ab39c] transition-all" placeholder="170">
                                                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-xs font-bold text-gray-400">cm</div>
                                                    </div>
                                                </div>
                                                <div class="col-span-2">
                                                    <label class="block text-xs font-bold text-gray-500 mb-1.5 ml-1 uppercase tracking-wider text-[10px]">Lingkar Perut</label>
                                                    <div class="relative">
                                                        <input type="number" step="0.1" wire:model="editLp" wire:key="edit-lp" class="block w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:border-[#0ab39c] transition-all" placeholder="80">
                                                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-xs font-bold text-gray-400">cm</div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="border-t border-gray-100 pt-6 space-y-4">
                                                <div>
                                                    <label class="block text-xs font-bold text-red-500 mb-1.5 ml-1 uppercase tracking-wider text-[10px] flex items-center gap-1"><i class="ri-error-warning-line"></i> Alergi Master</label>
                                                    <x-custom-dropdown model="editKodeAlergi" :options="$alergiList" placeholder="Pilih Alergi" />
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-bold text-red-500 mb-1.5 ml-1 uppercase tracking-wider text-[10px] flex items-center gap-1"><i class="ri-error-warning-line"></i> Keterangan Alergi</label>
                                                    <textarea wire:model="editAlergi" wire:key="edit-alergi" rows="2" class="block w-full px-4 py-3 bg-red-50/20 border border-red-100 rounded-xl text-sm focus:ring-4 focus:ring-red-500/5 focus:border-red-500 transition-all shadow-sm" placeholder="Catatan alergi..."></textarea>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-bold text-gray-500 mb-1.5 ml-1 uppercase tracking-wider text-[10px] flex items-center gap-1"><i class="ri-history-line"></i> Riwayat Penyakit</label>
                                                    <textarea wire:model="editRiwayat" wire:key="edit-riwayat" rows="2" class="block w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-4 focus:ring-[#0ab39c]/5 focus:border-[#0ab39c] transition-all shadow-sm" placeholder="Riwayat medis sebelumnya..."></textarea>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-bold text-gray-500 mb-1.5 ml-1 uppercase tracking-wider text-[10px] flex items-center gap-1"><i class="ri-chat-4-line"></i> Keterangan Lain</label>
                                                    <textarea wire:model="editKet" wire:key="edit-ket" rows="2" class="block w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-4 focus:ring-[#0ab39c]/5 focus:border-[#0ab39c] transition-all shadow-sm" placeholder="Catatan tambahan..."></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
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