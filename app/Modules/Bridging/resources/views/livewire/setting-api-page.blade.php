<div>
    <div class="page-header">
        <div class="page-header-title">
            <div class="page-header-icon"><i class="ri-links-line"></i></div>
            <h1>Setting API Bridging</h1>
        </div>
        <div class="page-header-breadcrumb">
            <a href="/dashboard" wire:navigate class="hover:text-[#405189] transition-colors"><i class="ri-home-4-line"></i></a>
            <span class="sep text-gray-300">/</span>
            <span class="text-gray-400 font-medium">Bridging</span>
            <span class="sep text-gray-300">/</span>
            <span class="text-[#405189] font-bold">Setting API</span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        <!-- BPJS Configuration -->
        <div class="card shadow-sm rounded-xl border-t-4 border-[#0d6efd] overflow-hidden">
            <div class="p-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-[#f3f6f9]/50">
                <h5 class="text-sm font-extrabold text-[#495057] m-0 uppercase tracking-wider flex items-center">
                    <i class="ri-shield-user-line mr-2 text-[#0d6efd] text-lg"></i> Konfigurasi BPJS (V-Claim / P-Care)
                </h5>
                <div class="flex items-center justify-between sm:justify-start gap-3 bg-white px-4 py-2 rounded-xl sm:rounded-full border border-gray-100 shadow-sm transition-all duration-300 w-full sm:w-auto">
                    <span class="text-[10px] font-bold {{ $bpjs_bridging ? 'text-[#0d6efd]' : 'text-gray-400' }} uppercase tracking-widest transition-colors duration-300">
                        Bridging: <span class="ml-1">{{ $bpjs_bridging ? 'ON' : 'OFF' }}</span>
                    </span>
                    <label class="relative inline-flex items-center cursor-pointer group">
                        <input type="checkbox" wire:model.live="bpjs_bridging" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#0d6efd] shadow-inner transition-all duration-300"></div>
                    </label>
                </div>
            </div>
            <form wire:submit.prevent="saveBpjs">
                <div class="p-6 space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1.5 uppercase tracking-tight">Consumer ID (ConsID) <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="bpjs_consid" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#0d6efd] transition-all shadow-sm @error('bpjs_consid') border-red-500 @enderror" placeholder="Masukkan ConsID">
                            @error('bpjs_consid') <span class="text-red-500 text-[10px] mt-1 font-bold">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1.5 uppercase tracking-tight">Secret Key <span class="text-red-500">*</span></label>
                            <input type="password" wire:model="bpjs_secret_key" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#0d6efd] transition-all shadow-sm @error('bpjs_secret_key') border-red-500 @enderror" placeholder="Masukkan Secret Key">
                            @error('bpjs_secret_key') <span class="text-red-500 text-[10px] mt-1 font-bold">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1.5 uppercase tracking-tight">Username Pcare <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="bpjs_username" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#0d6efd] transition-all shadow-sm @error('bpjs_username') border-red-500 @enderror" placeholder="Username Pcare">
                            @error('bpjs_username') <span class="text-red-500 text-[10px] mt-1 font-bold">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1.5 uppercase tracking-tight">Password Pcare <span class="text-red-500">*</span></label>
                            <input type="password" wire:model="bpjs_password" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#0d6efd] transition-all shadow-sm @error('bpjs_password') border-red-500 @enderror" placeholder="Password Pcare">
                            @error('bpjs_password') <span class="text-red-500 text-[10px] mt-1 font-bold">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1.5 uppercase tracking-tight">Kode Aplikasi <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="bpjs_kd_aplikasi" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#0d6efd] transition-all shadow-sm @error('bpjs_kd_aplikasi') border-red-500 @enderror" placeholder="Contoh: 001">
                        @error('bpjs_kd_aplikasi') <span class="text-red-500 text-[10px] mt-1 font-bold">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1.5 uppercase tracking-tight">User Key <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="bpjs_user_key" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#0d6efd] transition-all shadow-sm @error('bpjs_user_key') border-red-500 @enderror" placeholder="Masukkan User Key">
                        @error('bpjs_user_key') <span class="text-red-500 text-[10px] mt-1 font-bold">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1.5 uppercase tracking-tight">Base URL API PCARE <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="bpjs_base_url_pcare" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#0d6efd] transition-all shadow-sm @error('bpjs_base_url_pcare') border-red-500 @enderror" placeholder="https://apijkn.bpjs-kesehatan.go.id/vclaim-rest/">
                        @error('bpjs_base_url_pcare') <span class="text-red-500 text-[10px] mt-1 font-bold">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1.5 uppercase tracking-tight">BASE URL API ANTRIAN BPJS</label>
                        <input type="text" wire:model="bpjs_base_url_antrian" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#0d6efd] transition-all shadow-sm @error('bpjs_base_url_antrian') border-red-500 @enderror" placeholder="https://apijkn.bpjs-kesehatan.go.id/antrianrs/">
                        @error('bpjs_base_url_antrian') <span class="text-red-500 text-[10px] mt-1 font-bold">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="p-5 bg-gray-50/80 flex justify-end gap-3 border-t border-gray-100">
                    <button type="submit" class="btn bg-[#0d6efd] text-white font-bold text-xs uppercase tracking-widest px-6 h-10 shadow-md hover:bg-[#0b5ed7] hover:translate-y-[-2px] transition-all active:scale-95">
                        <i class="ri-save-line mr-2"></i> Simpan BPJS
                    </button>
                </div>
            </form>
        </div>

        <!-- SATUSEHAT Configuration -->
        <div class="card shadow-sm rounded-xl border-t-4 border-[#0ab39c] overflow-hidden">
            <div class="p-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-[#f3f6f9]/50">
                <h5 class="text-sm font-extrabold text-[#495057] m-0 uppercase tracking-wider">
                    <i class="ri-heart-pulse-line mr-2 text-[#0ab39c] text-lg"></i> Konfigurasi SATUSEHAT (Kemenkes)
                </h5>
            </div>
            <form wire:submit.prevent="saveSatuSehat">
                <div class="p-6 space-y-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1.5 uppercase tracking-tight">Mode Bridging SATUSEHAT <span class="text-red-500">*</span></label>
                        <div class="flex items-center gap-6 mt-2">
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input type="radio" wire:model.live="ss_mode_bridging" value="klinik" class="w-4 h-4 text-[#0ab39c] focus:ring-[#0ab39c] border-gray-300">
                                <span class="text-sm font-bold text-[#495057] group-hover:text-[#0ab39c] transition-colors">Kredensial Klinik (Global)</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input type="radio" wire:model.live="ss_mode_bridging" value="dokter" class="w-4 h-4 text-[#0ab39c] focus:ring-[#0ab39c] border-gray-300">
                                <span class="text-sm font-bold text-[#495057] group-hover:text-[#0ab39c] transition-colors">Kredensial Tiap Dokter</span>
                            </label>
                        </div>
                    </div>

                    <div class="bg-[#f3f6f9]/30 p-4 rounded-xl border border-gray-100 space-y-5">
                        <h6 class="text-xs font-extrabold text-[#0ab39c] uppercase tracking-wider border-b border-gray-200 pb-2 mb-3">Konfigurasi URL & Endpoint</h6>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1.5 uppercase tracking-tight">FHIR Base URL <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="ss_url" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#0ab39c] transition-all shadow-sm @error('ss_url') border-red-500 @enderror" placeholder="https://api-satusehat.kemkes.go.id/fhir-r4/v1">
                                @error('ss_url') <span class="text-red-500 text-[10px] mt-1 font-bold">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1.5 uppercase tracking-tight">Auth Token URL <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="ss_token_url" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#0ab39c] transition-all shadow-sm @error('ss_token_url') border-red-500 @enderror" placeholder="https://api-satusehat.kemkes.go.id/oauth2/v1/token">
                                @error('ss_token_url') <span class="text-red-500 text-[10px] mt-1 font-bold">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="bg-indigo-50/50 p-4 rounded-xl border border-indigo-100 space-y-5">
                        <h6 class="text-xs font-extrabold text-indigo-600 uppercase tracking-wider border-b border-indigo-200 pb-2 mb-3">
                            <i class="ri-shield-keyhole-line"></i> Kredensial Klinik (Global / Default)
                        </h6>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1.5 uppercase tracking-tight">Client ID <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="ss_client_id" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#0ab39c] transition-all shadow-sm @error('ss_client_id') border-red-500 @enderror" placeholder="Masukkan Client ID Klinik">
                            @error('ss_client_id') <span class="text-red-500 text-[10px] mt-1 font-bold">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1.5 uppercase tracking-tight">Client Secret <span class="text-red-500">*</span></label>
                            <textarea wire:model="ss_client_secret" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2 focus:border-[#0ab39c] transition-all shadow-sm @error('ss_client_secret') border-red-500 @enderror" rows="2" placeholder="Masukkan Client Secret Klinik"></textarea>
                            @error('ss_client_secret') <span class="text-red-500 text-[10px] mt-1 font-bold">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    @if($ss_mode_bridging === 'dokter')
                    <div class="bg-amber-50/50 p-4 rounded-xl border border-amber-100 mt-5 transition-all duration-300">
                        <h6 class="text-xs font-extrabold text-amber-600 uppercase tracking-wider border-b border-amber-200 pb-2 mb-4">
                            <i class="ri-team-line"></i> Kredensial Tiap Dokter
                        </h6>
                        
                        <div class="space-y-4">
                            @forelse($dokterList as $dokter)
                                <div class="bg-white p-4 rounded-lg border border-gray-100 shadow-sm" wire:key="dokter-{{ $dokter->id }}">
                                    <div class="font-bold text-[#495057] text-sm mb-3 pb-2 border-b border-gray-50 flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center text-[10px]">
                                            <i class="ri-user-heart-line"></i>
                                        </div>
                                        {{ $dokter->nama_dokter }} 
                                        <span class="text-[10px] text-gray-400 font-medium">({{ $dokter->spesialisasi ?: 'Dokter Umum' }})</span>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-[10px] font-bold text-gray-500 mb-1 uppercase tracking-tight">Client ID</label>
                                            <input type="text" wire:model="doctorCredentials.{{ $dokter->id }}.client_id" class="w-full rounded-md border-gray-200 text-xs px-3 py-2 bg-gray-50 focus:bg-white focus:border-amber-400 transition-all shadow-sm" placeholder="Client ID Dokter">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold text-gray-500 mb-1 uppercase tracking-tight">Client Secret</label>
                                            <input type="text" wire:model="doctorCredentials.{{ $dokter->id }}.client_secret" class="w-full rounded-md border-gray-200 text-xs px-3 py-2 bg-gray-50 focus:bg-white focus:border-amber-400 transition-all shadow-sm" placeholder="Client Secret Dokter">
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-4 text-xs font-medium text-gray-400">Belum ada data dokter aktif.</div>
                            @endforelse
                        </div>
                    </div>
                    @endif
                </div>
                <div class="p-5 bg-gray-50/80 flex justify-end gap-3 border-t border-gray-100">
                    <button type="button" wire:click="testConnection" wire:loading.attr="disabled" class="btn bg-white text-[#0ab39c] border-[#0ab39c] font-bold text-xs uppercase tracking-widest px-6 h-10 shadow-sm hover:bg-[#0ab39c] hover:text-white hover:translate-y-[-2px] transition-all active:scale-95">
                        <span wire:loading.remove wire:target="testConnection">
                            <i class="ri-signal-tower-line mr-2"></i> Test Koneksi
                        </span>
                        <span wire:loading wire:target="testConnection">
                            <i class="ri-loader-4-line mr-2 animate-spin"></i> Testing...
                        </span>
                    </button>
                    <button type="submit" class="btn bg-[#0ab39c] text-white font-bold text-xs uppercase tracking-widest px-6 h-10 shadow-md hover:bg-[#099885] hover:translate-y-[-2px] transition-all active:scale-95">
                        <i class="ri-save-line mr-2"></i> Simpan SATUSEHAT
                    </button>
                </div>

            </form>
        </div>
    </div>
            </form>
        </div>
    </div>
</div>
