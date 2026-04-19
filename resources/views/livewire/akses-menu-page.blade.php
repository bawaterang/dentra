<div class="page-content bg-gray-50/50 min-h-screen">
    <div class="page-header mb-8">
        <div class="page-header-title">
            <div class="page-header-icon bg-gradient-to-br from-[#405189] to-[#2a3a6a] text-white shadow-lg animate-pulse" style="animation-duration: 3s;">
                <i class="ri-shield-keyhole-line"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold tracking-tight text-[#2c3e50]">Manajemen Akses Menu</h1>
                <p class="text-xs text-[#878a99] font-medium mt-0.5">Atur hak akses perizinan fitur untuk setiap role pengguna.</p>
            </div>
        </div>
        <div class="page-header-breadcrumb">
            <a href="/dashboard" wire:navigate class="hover:text-[#405189] transition-colors"><i class="ri-home-4-line"></i></a>
            <span class="sep text-gray-300">/</span>
            <span class="text-gray-400 font-medium">Pengaturan</span>
            <span class="sep text-gray-300">/</span>
            <span class="text-[#405189] font-bold">Akses Menu</span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 animate-fade-in-up">
        <!-- Select Role Side -->
        <div class="card border-t-2 border-[#f7b84b] lg:col-span-1 h-fit relative" style="overflow: visible !important;">
            <div class="p-5 border-b border-[#eff2f7] bg-[#f3f6f9]/50">
                <h6 class="text-sm font-bold text-[#f7b84b]"><i class="ri-shield-flash-line mr-2"></i>Pilih Role Target</h6>
                <p class="text-xs text-gray-500 mt-1">Hak akses akan diterapkan pada role ini.</p>
            </div>
            <div class="p-5">
                <x-custom-dropdown 
                    model="selectedRoleId" 
                    :options="collect($allRoles)->map(fn($r) => ['value' => $r->id, 'label' => $r->nama_role, 'icon' => 'ri-shield-user-fill text-[#405189]'])->toArray()"
                    placeholder="Pilih Role Target"
                    searchable="true"
                    icon="ri-shield-star-line"
                    live="true"
                />
                
                @if($selectedRoleId)
                    @php $selR = collect($allRoles)->firstWhere('id', (int)$selectedRoleId); @endphp
                    <div class="mt-4 p-4 rounded-xl bg-orange-50 border border-orange-100">
                        <h6 class="font-bold text-[#495057] mb-1">{{ $selR->nama_role ?? '' }}</h6>
                        <p class="text-xs text-gray-600 mb-0">{{ ($selR->deskripsi ?? '') ?: 'Tidak ada deskripsi' }}</p>
                    </div>
                @else
                    <div class="mt-4 p-6 rounded-xl border border-dashed border-gray-300 flex flex-col items-center justify-center text-center bg-gray-50/50">
                        <i class="ri-focus-3-line text-3xl text-gray-300 mb-2"></i>
                        <span class="text-sm text-gray-500">Gunakan dropdown di atas untuk memilih role.</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Access Mapping Side -->
        <div class="card overflow-hidden lg:col-span-3 border-t-2 border-[#0ab39c] relative" style="overflow: visible !important;">
            @if(!$selectedRoleId)
            <div class="absolute inset-0 bg-white/70 backdrop-blur-sm z-50 flex flex-col items-center justify-center border border-gray-200 shadow-sm rounded-lg m-2">
                <i class="ri-lock-password-line text-4xl text-gray-400 mb-3"></i>
                <h5 class="text-gray-600 font-bold">Akses Menu Terkunci</h5>
                <p class="text-sm text-gray-500">Pilih Role di panel sebelah kiri untuk mengatur hak akses menu.</p>
            </div>
            @endif

            <div class="p-5 border-b border-[#eff2f7] flex flex-col sm:flex-row justify-between sm:items-center gap-4 bg-[#f3f6f9]/50">
                <div>
                    <h6 class="text-sm font-bold text-[#0ab39c]"><i class="ri-list-check mr-2"></i>Pemetaan Hak Akses Menu</h6>
                    <p class="text-xs text-gray-500 mt-1">Centang kotak untuk memberikan izin (View, Create, Update, Delete) pada setiap menu.</p>
                </div>
            </div>
            
            <div class="p-0 overflow-x-auto">
                <table class="table align-middle table-nowrap mb-0 w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600 font-semibold border-b border-gray-200 uppercase text-[11px] tracking-wider">
                        <tr>
                            <th class="py-3 px-4 w-1/3">Menu / Sub Menu</th>
                            <th class="py-3 px-4 text-center cursor-pointer hover:bg-gray-100 transition" @click="$wire.toggleAllColumn('can_view', true)" title="Check All View">View</th>
                            <th class="py-3 px-4 text-center cursor-pointer hover:bg-gray-100 transition" @click="$wire.toggleAllColumn('can_create', true)" title="Check All Create">Create</th>
                            <th class="py-3 px-4 text-center cursor-pointer hover:bg-gray-100 transition" @click="$wire.toggleAllColumn('can_update', true)" title="Check All Update">Update</th>
                            <th class="py-3 px-4 text-center cursor-pointer hover:bg-gray-100 transition" @click="$wire.toggleAllColumn('can_delete', true)" title="Check All Delete">Delete</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($menus as $menu)
                            <!-- Parent Menu -->
                            <tr class="hover:bg-gray-50/50 transition border-b border-gray-100 group bg-gray-50/30">
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-2">
                                        <i class="{{ $menu->menu_icon ?? 'ri-folder-line' }} text-[#405189] text-lg"></i>
                                        <span class="font-bold text-[#495057]">{{ $menu->menu_name }}</span>
                                    </div>
                                </td>
                                <td class="text-center align-middle">
                                    <input type="checkbox" wire:model="access.{{$menu->id}}.can_view" class="w-4 h-4 text-[#0ab39c] bg-white border-gray-300 rounded focus:ring-[#0ab39c] cursor-pointer">
                                </td>
                                <td class="text-center align-middle">
                                    <input type="checkbox" wire:model="access.{{$menu->id}}.can_create" class="w-4 h-4 text-[#0ab39c] bg-white border-gray-300 rounded focus:ring-[#0ab39c] cursor-pointer">
                                </td>
                                <td class="text-center align-middle">
                                    <input type="checkbox" wire:model="access.{{$menu->id}}.can_update" class="w-4 h-4 text-[#0ab39c] bg-white border-gray-300 rounded focus:ring-[#0ab39c] cursor-pointer">
                                </td>
                                <td class="text-center align-middle">
                                    <input type="checkbox" wire:model="access.{{$menu->id}}.can_delete" class="w-4 h-4 text-[#f06548] bg-white border-gray-300 rounded focus:ring-[#f06548] cursor-pointer">
                                </td>
                            </tr>
                            
                            <!-- Sub Menus -->
                            @foreach($menu->submenus as $sub)
                                <tr class="hover:bg-gray-50/50 transition border-b border-gray-50 group">
                                    <td class="py-2.5 px-4 pl-10">
                                        <div class="flex items-center gap-2 text-gray-500 group-hover:text-gray-700 transition">
                                            <i class="ri-corner-down-right-line opacity-50"></i>
                                            <i class="{{ $sub->menu_icon ?? 'ri-file-list-3-line' }} opacity-70"></i>
                                            <span class="text-[13px]">{{ $sub->menu_name }}</span>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <input type="checkbox" wire:model="access.{{$sub->id}}.can_view" class="w-4 h-4 text-[#0ab39c] bg-white border-gray-300 rounded focus:ring-[#0ab39c] cursor-pointer">
                                    </td>
                                    <td class="text-center align-middle">
                                        <input type="checkbox" wire:model="access.{{$sub->id}}.can_create" class="w-4 h-4 text-[#0ab39c] bg-white border-gray-300 rounded focus:ring-[#0ab39c] cursor-pointer">
                                    </td>
                                    <td class="text-center align-middle">
                                        <input type="checkbox" wire:model="access.{{$sub->id}}.can_update" class="w-4 h-4 text-[#0ab39c] bg-white border-gray-300 rounded focus:ring-[#0ab39c] cursor-pointer">
                                    </td>
                                    <td class="text-center align-middle">
                                        <input type="checkbox" wire:model="access.{{$sub->id}}.can_delete" class="w-4 h-4 text-[#f06548] bg-white border-gray-300 rounded focus:ring-[#f06548] cursor-pointer">
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="p-5 border-t border-[#eff2f7] bg-gray-50 flex justify-end gap-3">
                <button type="button" @click="location.reload()" class="btn bg-gray-500 text-white px-6 h-10 flex items-center gap-2 transition-all hover:bg-gray-600"><i class="ri-refresh-line"></i> Reset</button>
                <button type="button" wire:click="saveAccess" class="btn bg-[#0d6efd] text-white px-8 h-10 shadow-md flex items-center gap-2 transition-all hover:bg-blue-600 hover:translate-y-[-2px] hover:shadow-lg">
                    <i class="ri-save-3-line text-lg"></i> <span class="font-bold">Simpan Akses</span>
                </button>
            </div>
        </div>
    </div>
    
    <div class="mt-6 p-4 rounded-xl bg-blue-50 border border-blue-100 flex items-start gap-4 animate-fade-in-up">
        <i class="ri-information-line text-blue-500 text-xl"></i>
        <div>
            <h6 class="text-sm font-bold text-blue-700 mb-1">Informasi Hak Akses Majemuk</h6>
            <p class="text-xs text-blue-600/80 leading-relaxed mb-0">Karena satu user dapat memiliki banyak Role, maka hak akses yang didapat oleh user adalah kombinasi (gabungan) dari seluruh perizinan Role yang ia miliki. Jika salah satu Role memiliki akses 'View', maka user bisa mengakses halaman tersebut.</p>
        </div>
    </div>
</div>
