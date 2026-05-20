        <div x-data="{ 
            activeTab: @entangle('activeTab'),
            searchOrgModal: false, 
            locationModal: false,
            searchLocModal: false 
        }" 
        @open-modal.window="
            let modalId = Array.isArray($event.detail) ? $event.detail[0] : (typeof $event.detail === 'string' ? $event.detail : $event.detail.name);
            if(modalId === 'search-org-modal') searchOrgModal = true;
            if(modalId === 'location-modal') locationModal = true;
            if(modalId === 'search-loc-modal') searchLocModal = true;
        " 
        @close-modal.window="
            let modalId = Array.isArray($event.detail) ? $event.detail[0] : (typeof $event.detail === 'string' ? $event.detail : $event.detail.name);
            if(modalId === 'search-org-modal') searchOrgModal = false;
            if(modalId === 'location-modal') locationModal = false;
            if(modalId === 'search-loc-modal') searchLocModal = false;
        ">


            <div class="page-header">
                <div class="page-header-title">
                    <div class="page-header-icon bg-gradient-to-br from-[#405189] to-[#2a3a6a] text-white shadow-lg animate-pulse" style="animation-duration: 3s;">
                        <i class="ri-hospital-line"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold tracking-tight text-[#2c3e50]">Pengaturan Klinik</h1>
                        <p class="text-xs text-[#878a99] font-medium mt-0.5">Kelola informasi profil, kontak, dan identitas legal instansi klinik.</p>
                    </div>
                </div>
                <div class="page-header-breadcrumb">
                    <a href="/dashboard" wire:navigate class="hover:text-[#405189] transition-colors"><i class="ri-home-4-line"></i></a>
                    <span class="sep text-gray-300">/</span>
                    <span class="text-gray-400 font-medium">Pengaturan</span>
                    <span class="sep text-gray-300">/</span>
                    <span class="text-[#405189] font-bold">Setting Klinik</span>
                </div>
            </div>

            <div class="max-w-5xl mx-auto mt-6">
                <!-- Tab Menu -->
                <div class="flex items-center gap-1 mb-6 p-1 bg-gray-100 rounded-2xl w-fit">
                    <button @click="activeTab = 'profil'" 
                            :class="activeTab === 'profil' ? 'bg-white text-[#405189] shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                            class="px-6 py-2.5 rounded-xl text-sm font-black transition-all duration-200">
                        <i class="ri-hospital-line mr-2"></i> Profil Klinik
                    </button>
                    <button @click="activeTab = 'lokasi'" 
                            :class="activeTab === 'lokasi' ? 'bg-white text-[#405189] shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                            class="px-6 py-2.5 rounded-xl text-sm font-black transition-all duration-200">
                        <i class="ri-map-pin-line mr-2"></i> Lokasi / Ruangan
                    </button>
                </div>

                <!-- Tab content: Profil Klinik -->
                <div x-show="activeTab === 'profil'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4">
                    <form wire:submit.prevent="save">
                        <div class="card overflow-hidden border-t-4 border-[#405189] shadow-lg rounded-xl">
                            <div class="p-6 border-b border-[#eff2f7] bg-gray-50/50 flex justify-between items-center">
                                <div>
                                    <h5 class="text-lg font-bold text-[#495057] flex items-center gap-2">
                                        <i class="ri-hospital-fill text-[#405189]"></i> Profil Instansi
                                    </h5>
                                    <p class="text-sm text-[#878a99] mt-1">Kelola identitas dan informasi kontak rekam medis klinik Anda.</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($organization_id)
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-100 text-emerald-700 text-xs font-bold border border-emerald-200" title="Terhubung: {{ $organization_id }}">
                                            <i class="ri-checkbox-circle-fill text-sm"></i> SatuSehat Synced
                                        </span>
                                        <button type="button" wire:click="syncSatuSehat" class="btn btn-sm bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white shadow-sm transition-all" title="Update ke SatuSehat">
                                            <i class="ri-refresh-line"></i> Update
                                        </button>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-gray-100 text-gray-500 text-xs font-bold border border-gray-200">
                                            <i class="ri-cloud-off-line text-sm"></i> Not Synced
                                        </span>
                                        <button type="button" wire:click="syncSatuSehat" class="btn btn-sm bg-emerald-500 text-white hover:bg-emerald-600 shadow-sm transition-all" title="Daftarkan ke SatuSehat">
                                            <i class="ri-cloud-line"></i> Register
                                        </button>
                                    @endif
                                </div>
                            </div>
                            <!-- ... Existing Profile Form Content (Alamat, Logo, etc) ... -->
                            <div class="p-6 border-b border-[#eff2f7]">
                                <div class="flex flex-col sm:flex-row items-center gap-6">
                                    <div class="h-24 w-24 shrink-0 rounded-xl bg-gray-50 border-2 border-dashed border-gray-300 flex items-center justify-center relative overflow-hidden group">
                                        @if($logo_file)
                                            <img src="{{ $logo_file->temporaryUrl() }}" class="w-full h-full object-cover">
                                        @elseif($logo)
                                            <img src="{{ asset('storage/'.$logo) }}" class="w-full h-full object-cover">
                                        @else
                                            <i class="ri-image-add-line text-3xl text-gray-400 group-hover:text-[#405189] transition-colors"></i>
                                        @endif
                                        <label class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center cursor-pointer">
                                            <i class="ri-camera-switch-line text-white text-xl"></i>
                                            <input type="file" wire:model="logo_file" class="hidden" accept="image/*">
                                        </label>
                                    </div>
                                    <div class="text-center sm:text-left">
                                        <h6 class="text-sm font-bold text-gray-700 mb-1">Logo Klinik</h6>
                                        <p class="text-xs text-gray-500 mb-2">Format yang didukung: JPG, PNG, atau GIF. Ukuran maksimal 2MB.</p>
                                        @error('logo_file') <span class="text-[11px] text-red-500 font-medium">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="p-6 space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Klinik</label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <i class="ri-hospital-line text-gray-400"></i>
                                                </div>
                                                <input type="text" wire:model="nama_klinik" class="w-full pl-10 rounded-lg border-gray-200 text-sm h-11 focus:border-[#405189] focus:ring focus:ring-[#405189]/20 transition-all font-semibold">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Pimpinan / Penanggung Jawab</label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <i class="ri-user-star-line text-gray-400"></i>
                                                </div>
                                                <input type="text" wire:model="pimpinan" class="w-full pl-10 rounded-lg border-gray-200 text-sm h-11 focus:border-[#405189] focus:ring focus:ring-[#405189]/20 transition-all">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-1">Telepon Utama</label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <i class="ri-phone-fill text-gray-400"></i>
                                                </div>
                                                <input type="text" wire:model="telepon" class="w-full pl-10 rounded-lg border-gray-200 text-sm h-11 focus:border-[#0ab39c] focus:ring focus:ring-[#0ab39c]/20 transition-all">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-1">Email Resmi</label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <i class="ri-mail-fill text-gray-400"></i>
                                                </div>
                                                <input type="email" wire:model="email" class="w-full pl-10 rounded-lg border-gray-200 text-sm h-11 focus:border-[#0ab39c] focus:ring focus:ring-[#0ab39c]/20 transition-all">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-1">Website URL</label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <i class="ri-global-line text-gray-400"></i>
                                                </div>
                                                <input type="text" wire:model="website" class="w-full pl-10 rounded-lg border-gray-200 text-sm h-11 focus:border-[#0ab39c] focus:ring focus:ring-[#0ab39c]/20 transition-all">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-1">Organization ID Fasyankes (Kemenkes)</label>
                                            <div class="flex gap-2">
                                                <div class="relative flex-1">
                                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                        <i class="ri-qr-code-line text-gray-400"></i>
                                                    </div>
                                                    <input type="text" wire:model="organization_id" class="w-full pl-10 rounded-lg border-gray-200 text-sm h-11 focus:border-[#405189] focus:ring focus:ring-[#405189]/20 transition-all bg-yellow-50" placeholder="Cth: R220001">
                                                </div>
                                                <button type="button" wire:click="openSearchSSOrg" class="btn bg-indigo-50 text-indigo-600 px-4 rounded-lg font-bold hover:bg-indigo-600 hover:text-white transition-colors">

                                                    <i class="ri-search-line"></i> Cari
                                                </button>
                                            </div>
                                        </div>
                                        <p class="text-[10px] text-gray-500 mt-1">Wajib diisi untuk registrasi poli/departemen ke SatuSehat.</p>
                                    </div>
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-1">Alamat Lengkap</label>
                                            <textarea wire:model="alamat" class="w-full rounded-lg border-gray-200 text-sm p-3 h-20 focus:border-[#405189] focus:ring focus:ring-[#405189]/20 transition-all resize-none shadow-sm"></textarea>
                                        </div>
                                        <div class="grid grid-cols-2 gap-3">
                                            <div class="space-y-1">
                                                <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Provinsi</label>
                                                <x-custom-dropdown model="provinsi_id" :options="$this->provinsiOptions->map(fn($v) => ['value' => $v->kode, 'label' => $v->nama])->toArray()" placeholder="Pilih Provinsi" searchable="true" live="true"/>
                                            </div>
                                            <div class="space-y-1">
                                                <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Kabupaten</label>
                                                <x-custom-dropdown model="kabupaten_id" :options="$this->kabupatenOptions->map(fn($v) => ['value' => $v->kode, 'label' => $v->nama])->toArray()" placeholder="Pilih Kabupaten" searchable="true" live="true"/>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-2 gap-3">
                                            <div class="space-y-1">
                                                <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Kecamatan</label>
                                                <x-custom-dropdown model="kecamatan_id" :options="$this->kecamatanOptions->map(fn($v) => ['value' => $v->kode, 'label' => $v->nama])->toArray()" placeholder="Pilih Kecamatan" searchable="true" live="true"/>
                                            </div>
                                            <div class="space-y-1">
                                                <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Kelurahan</label>
                                                <x-custom-dropdown model="kelurahan_id" :options="$this->kelurahanOptions->map(fn($v) => ['value' => $v->kode, 'label' => $v->nama])->toArray()" placeholder="Pilih Kelurahan" searchable="true"/>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-2 gap-3">
                                            <div class="space-y-1">
                                                <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest px-1">Kode Pos</label>
                                                <input type="text" wire:model="kode_pos" class="w-full bg-white border border-gray-200 rounded-lg py-2 px-3 text-sm focus:border-[#405189] focus:ring focus:ring-[#405189]/20" placeholder="Kode Pos">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="p-5 bg-gray-50 flex justify-end gap-3 border-t border-[#eff2f7]">
                                <button type="button" wire:click="mount" class="btn bg-gray-500 text-white font-bold text-sm px-6 hover:bg-gray-600 transition-all">Reset</button>
                                <button type="submit" class="btn bg-[#0d6efd] text-white font-bold text-sm px-8 shadow-md hover:bg-[#0b5ed7] transition-all"><i class="ri-check-double-line mr-1"></i> Simpan Pengaturan Klinik</button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Tab content: Lokasi / Ruangan -->
                <div x-show="activeTab === 'lokasi'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4">
                    <div class="card overflow-hidden border-t-4 border-[#0ab39c] shadow-lg rounded-xl">
                        <div class="p-6 border-b border-[#eff2f7] bg-gray-50/50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                            <div>
                                <h5 class="text-lg font-bold text-[#495057] flex items-center gap-2">
                                    <i class="ri-map-pin-fill text-[#0ab39c]"></i> Manajemen Lokasi
                                </h5>
                                <p class="text-sm text-[#878a99] mt-1 line-clamp-2 sm:line-clamp-none">Kelola data ruangan dan lokasi fasyankes yang terdaftar di SatuSehat.</p>
                            </div>

                            <div class="flex items-center gap-2">
                                <button wire:click="openSearchSSLoc" class="btn bg-indigo-50 text-indigo-600 px-5 h-10 rounded-xl font-bold text-sm shadow-sm hover:bg-indigo-600 hover:text-white transition-all flex items-center gap-2">
                                    <i class="ri-search-line"></i> Cari SatuSehat
                                </button>

                                <button wire:click="createLoc" class="btn bg-[#0ab39c] text-white px-5 h-10 rounded-xl font-bold text-sm shadow-md hover:bg-emerald-600 transition-all flex items-center gap-2">
                                    <i class="ri-add-line text-lg"></i> Tambah Lokasi
                                </button>
                            </div>


                        </div>

                        <div class="p-0 overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-gray-50/50 border-b border-gray-100">
                                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">ID Lokasi</th>
                                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Nama Lokasi / Ruangan</th>
                                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">SatuSehat Sync</th>
                                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Status</th>
                                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Aksi</th>
                                    </tr>

                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    @forelse($locations as $loc)
                                    <tr class="hover:bg-gray-50/80 transition-color">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded text-[11px] font-black font-mono">{{ $loc->location_id }}</span>
                                        </td>

                                        <td class="px-6 py-4">
                                            <div class="font-bold text-gray-800 text-sm">{{ $loc->location_name }}</div>
                                            <div class="text-[10px] text-gray-400 font-medium truncate max-w-[200px]">{{ $loc->description }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            @if($loc->location_id)
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-600 text-[10px] font-bold border border-emerald-100">
                                                    <i class="ri-checkbox-circle-fill"></i> Synced
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-gray-50 text-gray-400 text-[10px] font-bold border border-gray-100">
                                                    <i class="ri-cloud-off-line"></i> Unsynced
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="px-2 py-1 rounded text-[10px] font-black uppercase tracking-widest {{ $loc->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                                {{ $loc->status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <button wire:click="syncLocToSS({{ $loc->id }})" class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition-all flex items-center justify-center p-0" title="Sync SatuSehat">
                                                    <i class="ri-cloud-line text-sm"></i>
                                                </button>
                                                <button wire:click="editLoc({{ $loc->id }})" class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white transition-all flex items-center justify-center p-0" title="Edit">
                                                    <i class="ri-pencil-line text-sm"></i>
                                                </button>
                                                <button @click="Swal.fire({
                                                    title: 'Konfirmasi Hapus',
                                                    text: 'Apakah Anda yakin ingin menghapus lokasi {{ $loc->location_name }}?',
                                                    icon: 'warning',
                                                    showCancelButton: true,
                                                    confirmButtonColor: '#f06548',
                                                    cancelButtonColor: '#6c757d',
                                                    confirmButtonText: 'Ya, Hapus',
                                                    cancelButtonText: 'Batal',
                                                    reverseButtons: true
                                                }).then((result) => {
                                                    if (result.isConfirmed) {
                                                        $wire.deleteLoc({{ $loc->id }})
                                                    }
                                                })" class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition-all flex items-center justify-center p-0" title="Hapus">
                                                    <i class="ri-delete-bin-line text-sm"></i>
                                                </button>

                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="py-20 text-center">
                                            <div class="flex flex-col items-center justify-center opacity-40">
                                                <i class="ri-map-pin-line text-6xl text-gray-300 mb-4 scale-x-[-1]"></i>
                                                <p class="text-sm font-bold text-gray-400">Belum ada data lokasi / ruangan.</p>
                                                <p class="text-[10px] text-gray-400 uppercase tracking-widest mt-1">Daftarkan lokasimu untuk integrasi SatuSehat</p>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modals Area -->

            <!-- Search Organization Modal -->
            <div x-show="searchOrgModal" class="fixed inset-0 z-[1040] flex items-center justify-center p-4" x-transition.opacity style="display: none;">

                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="searchOrgModal = false"></div>
                <!-- ... Same Search Org Modal Content ... -->
                <div x-show="searchOrgModal" x-transition.scale.95 class="bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden p-6 relative z-10">
                    <div class="absolute top-4 right-4">
                        <button type="button" @click="searchOrgModal = false" class="text-gray-400 hover:text-rose-500 transition-colors">
                            <i class="ri-close-circle-fill text-2xl"></i>
                        </button>
                    </div>

                    <h3 class="text-xl font-bold text-[#405189] mb-1">Cari Organization SatuSehat</h3>
                    <p class="text-sm text-gray-500 mb-6">Mencari data instansi berdasarkan nama yang terdaftar di Kemenkes.</p>

                    <form wire:submit.prevent="searchSatuSehatOrg" class="flex gap-2 mb-6">
                        <input type="text" wire:model="searchOrgQuery" class="flex-1 rounded-xl border-gray-200 text-sm py-2 px-4 focus:border-[#405189] focus:ring-4 focus:ring-indigo-100 placeholder:text-gray-400 font-medium" placeholder="Masukkan nama instansi / puskemas...">
                        <button type="submit" class="bg-[#405189] text-white px-5 rounded-xl text-sm font-bold shadow-sm hover:bg-indigo-600 transition-colors">
                            <span wire:loading.remove wire:target="searchSatuSehatOrg">Cari</span>
                            <span wire:loading wire:target="searchSatuSehatOrg"><i class="ri-loader-4-line animate-spin"></i></span>
                        </button>
                    </form>

                    @if(!empty($foundOrganizations))
                    <div class="max-h-80 overflow-y-auto space-y-3 pr-2 scrollbar-thin scrollbar-thumb-gray-200">
                        @foreach($foundOrganizations as $org)
                            @php
                                $resource = $org['resource'];
                            @endphp
                            <div class="border border-gray-200 rounded-xl p-4 flex items-center justify-between hover:border-indigo-300 hover:bg-indigo-50/50 transition-colors group">
                                <div>
                                    <h4 class="font-bold text-gray-800 text-sm group-hover:text-[#405189] transition-colors">{{ $resource['name'] ?? 'No Name' }}</h4>
                                    <div class="flex gap-3 mt-1.5 text-xs text-gray-500">
                                        <span><i class="ri-qr-code-line mr-1"></i> ID: {{ $resource['id'] ?? 'No ID' }}</span>
                                    </div>
                                </div>
                                @if(isset($resource['id']))
                                    <button type="button" wire:click="selectSSOrg('{{ $resource['id'] }}', '{{ addslashes($resource['name'] ?? '') }}')" class="btn btn-sm bg-emerald-50 text-emerald-600 hover:bg-emerald-500 hover:text-white rounded-lg">Pilih</button>
                                @endif
                            </div>

                        @endforeach
                    </div>
                    @endif
                </div>
            </div>

            <!-- Location Entry/Edit Modal -->
            <div x-show="locationModal" class="fixed inset-0 z-[1040] flex items-center justify-center p-4" x-transition.opacity style="display: none;">

                <div class="absolute inset-0 bg-[#0a192f]/60 backdrop-blur-md" @click="locationModal = false"></div>
                <div x-show="locationModal" x-transition.scale.95 class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden border border-white/20 relative z-10">
                    <div class="px-8 py-6 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-gray-50 to-white">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-[#0ab39c] flex items-center justify-center shadow-inner">
                                <i class="ri-map-pin-line text-xl"></i>
                            </div>
                            <h5 class="text-base font-black text-[#2c3e50] tracking-tight">{{ $isEditLoc ? 'Edit Lokasi' : 'Tambah Lokasi Baru' }}</h5>
                        </div>
                        <button @click="locationModal = false" class="text-gray-400 hover:text-rose-500 transition-colors"><i class="ri-close-line text-xl"></i></button>
                    </div>

                    <form wire:submit.prevent="saveLoc" class="p-8 space-y-5">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">ID Lokasi <span class="text-rose-500">*</span></label>
                                <input type="text" wire:model="ss_location_id" 
                                       class="w-full rounded-xl border-gray-100 bg-gray-50 text-sm py-3 px-5 font-bold focus:bg-white focus:border-[#405189] focus:ring-4 focus:ring-indigo-100 transition-all outline-none" 
                                       placeholder="SatuSehat UUID">
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">Tipe / Status</label>
                                <select wire:model="loc_status" 
                                        class="w-full rounded-xl border-gray-100 bg-gray-50 text-sm py-3 px-5 font-bold focus:bg-white focus:border-[#405189] focus:ring-4 focus:ring-indigo-100 transition-all outline-none appearance-none">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="suspended">Suspended</option>
                                </select>
                            </div>
                        </div>


                        <div class="space-y-1.5">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">Nama Lokasi <span class="text-rose-500">*</span></label>
                            <input type="text" wire:model="location_name" 
                                   class="w-full rounded-xl border-gray-100 bg-gray-50 text-sm py-3 px-5 font-bold focus:bg-white focus:border-[#405189] focus:ring-4 focus:ring-indigo-100 transition-all outline-none" 
                                   placeholder="E.g. Ruang Periksa 1">
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">Deskripsi</label>
                            <textarea wire:model="location_description" 
                                      class="w-full rounded-xl border-gray-100 bg-gray-50 text-sm py-3 px-5 h-20 focus:bg-white focus:border-[#405189] focus:ring-4 focus:ring-indigo-100 transition-all outline-none resize-none" 
                                      placeholder="Detail lokasi..."></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">Longitude</label>
                                <input type="text" wire:model="longitude" 
                                       class="w-full rounded-xl border-gray-100 bg-gray-50 text-sm py-3 px-5 font-mono focus:bg-white focus:border-[#405189] focus:ring-4 focus:ring-indigo-100 transition-all outline-none" 
                                       placeholder="-6.231...">
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">Latitude</label>
                                <input type="text" wire:model="latitude" 
                                       class="w-full rounded-xl border-gray-100 bg-gray-50 text-sm py-3 px-5 font-mono focus:bg-white focus:border-[#405189] focus:ring-4 focus:ring-indigo-100 transition-all outline-none" 
                                       placeholder="106.832...">
                            </div>
                        </div>


                        <div class="pt-4 flex flex-col sm:flex-row justify-between items-center gap-4">
                            <p class="text-[9px] text-gray-400 italic max-w-full sm:max-w-[180px] leading-tight text-center sm:text-left">Pastikan data sesuai dengan standar penamaan ruangan fasyankes.</p>
                            <button type="submit" 
                                    class="btn bg-[#405189] text-white w-full sm:w-auto px-10 h-11 rounded-xl font-black text-xs uppercase tracking-widest shadow-xl shadow-indigo-200 hover:shadow-indigo-300 hover:-translate-y-0.5 active:translate-y-0 transition-all flex items-center justify-center gap-2">
                                <i class="ri-save-3-line text-lg"></i> Simpan Lokasi
                            </button>
                        </div>

                    </form>
                </div>
            </div>

            <!-- Search Location SatuSehat Modal -->
            <div x-show="searchLocModal" class="fixed inset-0 z-[1040] flex items-center justify-center p-4" x-transition.opacity style="display: none;">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="searchLocModal = false"></div>
                <div x-show="searchLocModal" x-transition.scale.95 class="bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden p-6 relative z-10">

                    <div class="absolute top-4 right-4">
                        <button type="button" @click="searchLocModal = false" class="text-gray-400 hover:text-rose-500 transition-colors">
                            <i class="ri-close-circle-fill text-2xl"></i>
                        </button>
                    </div>

                    <div class="mb-6">
                        <h3 class="text-xl font-bold text-[#405189] mb-1">Cari Lokasi SatuSehat</h3>
                        <p class="text-sm text-gray-500">Mencari data lokasi/ruangan yang terdaftar di organisasi Anda.</p>
                    </div>

                    <form wire:submit.prevent="searchSSLocation" class="flex flex-col sm:flex-row gap-2 mb-6">
                        <select wire:model.live="searchLocFilter" class="rounded-xl border-gray-200 text-sm py-2 px-4 focus:border-[#405189] focus:ring-4 focus:ring-indigo-100 font-medium bg-gray-50">
                            <option value="name">By Nama</option>
                            <option value="organization">By Org ID</option>
                            <option value="id">By ID Location</option>
                        </select>
                        <input type="text" wire:model="searchLocQuery" 
                               class="flex-1 rounded-xl border-gray-200 text-sm py-2 px-4 focus:border-[#405189] focus:ring-4 focus:ring-indigo-100 placeholder:text-gray-400 font-medium" 
                               placeholder="{{ $searchLocFilter === 'name' ? 'Masukkan nama ruangan...' : ($searchLocFilter === 'organization' ? 'Masukkan Organization ID...' : 'Masukkan Location ID...') }}">
                        <button type="submit" class="bg-[#405189] text-white px-5 rounded-xl text-sm font-bold shadow-sm hover:bg-indigo-600 transition-colors">
                            <span wire:loading.remove wire:target="searchSSLocation">Cari</span>
                            <span wire:loading wire:target="searchSSLocation"><i class="ri-loader-4-line animate-spin"></i></span>
                        </button>
                    </form>


                    @if(!empty($foundLocations))
                    <div class="max-h-80 overflow-y-auto space-y-3 pr-2 scrollbar-thin scrollbar-thumb-gray-200">
                        @foreach($foundLocations as $item)
                            @php
                                $res = $item['resource'];
                            @endphp
                            <div class="border border-gray-200 rounded-xl p-4 flex items-center justify-between hover:border-indigo-300 hover:bg-indigo-50/50 transition-colors group">
                                <div>
                                    <h4 class="font-bold text-gray-800 text-sm group-hover:text-[#405189] transition-colors uppercase tracking-tight">{{ $res['name'] ?? 'No Name' }}</h4>
                                    <div class="flex flex-wrap gap-4 mt-1.5 text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                        <span><i class="ri-key-line mr-1 text-indigo-400"></i> ID: <span class="text-indigo-600">{{ $res['id'] ?? 'No ID' }}</span></span>
                                        <span><i class="ri-information-line mr-1 text-emerald-400"></i> DESKRIPSI: {{ $res['description'] ?? '-' }}</span>

                                    </div>
                                </div>
                                @if(isset($res['id']))
                                    <button type="button" 
                                            wire:click="selectSSLoc({{ json_encode($res) }})" 
                                            class="btn btn-sm bg-emerald-50 text-emerald-600 hover:bg-emerald-500 hover:text-white rounded-lg">
                                        Pilih
                                    </button>
                                @endif
                            </div>

                        @endforeach
                    </div>
                    @endif
                </div>
            </div>

                </div>
            </div>

        </div>