<div>
    <div class="page-header">
        <div class="page-header-title">
            <div class="page-header-icon"><i class="ri-shield-user-line"></i></div>
            <h1>Data Pasien BPJS</h1>
        </div>
        <div class="page-header-breadcrumb">
            <a href="/dashboard" wire:navigate><i class="ri-home-line"></i></a>
            <span class="sep">/</span>
            <span>Bridging</span>
            <span class="sep">/</span>
            <span>Data Pasien BPJS</span>
        </div>
    </div>

    <!-- Stats / Overview (Optional but looks premium) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
        <div class="card shadow-sm rounded-xl p-4 bg-gradient-to-br from-blue-50 to-white border-l-4 border-blue-500" style="border-top: 3px solid #405189;">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-blue-100 flex items-center justify-center text-blue-600">
                    <i class="ri-group-line text-xl"></i>
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-tight">Total Pasien Lokal</p>
                    <h3 class="text-xl font-black text-gray-800">{{ \App\Models\TrxPasienBpjs::count() }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card shadow-sm rounded-xl mt-6 border border-gray-100 overflow-hidden border-t-2 border-[#405189]">
        <div class="p-6 bg-[#f3f6f9]/50 border-b border-gray-100">
            <h5 class="text-xs font-bold text-[#405189] mb-4 uppercase tracking-widest flex items-center gap-2">
                <i class="ri-filter-3-line text-lg"></i> Filter Pencarian API BPJS
            </h5>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-5 items-end">
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1.5 uppercase tracking-tight">Jenis Pencarian</label>
                    <x-custom-dropdown 
                        model="filter_type" 
                        :options="[
                            ['value' => 'semua', 'label' => 'Semua (By Provider)', 'icon' => 'ri-global-line text-blue-500'],
                            ['value' => 'nomor_urut', 'label' => 'Nomor Urut Pcare', 'icon' => 'ri-list-ordered text-orange-500']
                        ]"
                        placeholder="Pilih Jenis Pencarian"
                        :live="true"
                    />
                </div>
                
                @if($filter_type === 'nomor_urut')
                <div x-transition>
                    <label class="block text-xs font-bold text-gray-500 mb-1.5 uppercase tracking-tight">Nomor Urut</label>
                    <input type="text" wire:model="nomor_urut" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#405189] transition-all shadow-sm" placeholder="Contoh: 12">
                </div>
                @endif

                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1.5 uppercase tracking-tight">Tanggal</label>
                    <input type="date" wire:model="search_date" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#405189] transition-all shadow-sm">
                </div>

                <div class="flex gap-2">
                    <button wire:click="cari" class="btn bg-[#ebab0c] text-white font-bold text-xs uppercase tracking-widest h-11 px-6 shadow-md hover:bg-[#997009] hover:translate-y-[-2px] transition-all active:scale-95 flex-1 flex items-center justify-center gap-2">
                        <i class="ri-search-line"></i> Cari Data
                    </button>
                    <button wire:click="$refresh" class="btn bg-gray-100 text-gray-600 font-bold text-sm h-11 px-4 hover:bg-gray-200 transition-all border border-gray-200">
                        <i class="ri-refresh-line"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="p-6">
            <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                <div class="relative w-full md:w-80">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                        <i class="ri-search-line text-gray-400"></i>
                    </div>
                    <input type="text" wire:model.live.debounce.300ms="search" class="w-full h-10 pl-10 pr-4 rounded-lg border border-gray-200 text-sm focus:border-[#405189] focus:bg-white transition-all shadow-sm placeholder:text-gray-400" placeholder="Cari nama, no kartu, nik...">
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500 font-medium">Tampilkan:</span>
                    <select wire:model.live="perPage" class="rounded-lg border-gray-200 text-xs h-9 focus:border-[#405189] focus:ring-[#405189]/20">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50 border-b border-gray-100">
                            <th class="px-4 py-3 font-bold text-gray-700 uppercase tracking-widest text-[10px]">No. Kartu / NIK</th>
                            <th class="px-4 py-3 font-bold text-gray-700 uppercase tracking-widest text-[10px]">Nama Pasien</th>
                            <th class="px-4 py-3 font-bold text-gray-700 uppercase tracking-widest text-[10px]">L/P</th>
                            <th class="px-4 py-3 font-bold text-gray-700 uppercase tracking-widest text-[10px]">Tgl Lahir</th>
                            <th class="px-4 py-3 font-bold text-gray-700 uppercase tracking-widest text-[10px]">Provider</th>
                            <th class="px-4 py-3 font-bold text-gray-700 uppercase tracking-widest text-[10px]">Status</th>
                            <th class="px-4 py-3 font-bold text-gray-700 uppercase tracking-widest text-[10px] text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse ($pasiens as $pasien)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-4 py-4">
                                <span class="block font-bold text-[#405189]">{{ $pasien->no_kartu }}</span>
                                <span class="text-[11px] text-gray-500 font-medium">{{ $pasien->nik }}</span>
                            </td>
                            <td class="px-4 py-4">
                                <div class="font-bold text-gray-800">{{ $pasien->nama }}</div>
                                <div class="text-[10px] text-[#0ab39c] font-black uppercase">{{ $pasien->jns_peserta }}</div>
                            </td>
                            <td class="px-4 py-4">
                                <span class="badge {{ $pasien->sex == 'L' ? 'bg-blue-100 text-blue-600' : 'bg-pink-100 text-pink-600' }} px-2.5 py-0.5 rounded text-[10px] font-bold">
                                    {{ $pasien->sex }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-gray-600 font-medium">
                                {{ \Carbon\Carbon::parse($pasien->tgl_lahir)->translatedFormat('d M Y') }}
                            </td>
                            <td class="px-4 py-4">
                                <div class="text-xs font-semibold text-gray-700">{{ $pasien->nm_provider }}</div>
                                <div class="text-[10px] text-gray-400">{{ $pasien->nm_cabang }}</div>
                            </td>
                            <td class="px-4 py-4">
                                <span class="badge bg-green-100 text-green-700 px-2.5 py-0.5 rounded text-[10px] font-bold uppercase">
                                    {{ $pasien->status_peserta }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <div class="flex justify-center gap-1.5">
                                    <button wire:click="riwayat({{ $pasien->id }})" class="h-8 w-8 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition-all shadow-sm flex items-center justify-center border border-blue-100" title="Riwayat">
                                        <i class="ri-history-line text-sm"></i>
                                    </button>
                                    <button wire:click="delete({{ $pasien->id }})" wire:confirm="Anda yakin ingin menghapus data lokal ini?" class="h-8 w-8 rounded-lg bg-red-50 text-red-600 hover:bg-red-600 hover:text-white transition-all shadow-sm flex items-center justify-center border border-red-100" title="Hapus">
                                        <i class="ri-delete-bin-line text-sm"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <i class="ri-user-search-line text-5xl text-gray-200 mb-3"></i>
                                    <p class="text-gray-400 font-medium">Belum ada data pasien BPJS lokal. Silakan gunakan fitur pencarian API.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $pasiens->links() }}
            </div>
        </div>
    </div>
</div>
