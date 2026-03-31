<div>
    <div class="page-header">
        <div class="page-header-title">
            <div class="page-header-icon"><i class="ri-links-line"></i></div>
            <h1>Setting API Bridging</h1>
        </div>
        <div class="page-header-breadcrumb">
            <a href="/dashboard" wire:navigate><i class="ri-home-line"></i></a>
            <span class="sep">/</span>
            <span>Bridging</span>
            <span class="sep">/</span>
            <span>Setting API</span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        <!-- BPJS Configuration -->
        <div class="card shadow-sm rounded-xl border-t-4 border-[#0d6efd] overflow-hidden">
            <div class="p-5 border-b border-gray-100 flex items-center justify-between bg-[#f3f6f9]/50">
                <h5 class="text-sm font-extrabold text-[#495057] m-0 uppercase tracking-wider">
                    <i class="ri-shield-user-line mr-2 text-[#0d6efd] text-lg"></i> Konfigurasi BPJS (V-Claim / P-Care)
                </h5>
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
                        <label class="block text-xs font-bold text-gray-500 mb-1.5 uppercase tracking-tight">Base URL API <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="bpjs_base_url" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#0d6efd] transition-all shadow-sm @error('bpjs_base_url') border-red-500 @enderror" placeholder="https://apijkn.bpjs-kesehatan.go.id/vclaim-rest/">
                        @error('bpjs_base_url') <span class="text-red-500 text-[10px] mt-1 font-bold">{{ $message }}</span> @enderror
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
            <div class="p-5 border-b border-gray-100 flex items-center justify-between bg-[#f3f6f9]/50">
                <h5 class="text-sm font-extrabold text-[#495057] m-0 uppercase tracking-wider">
                    <i class="ri-heart-pulse-line mr-2 text-[#0ab39c] text-lg"></i> Konfigurasi SATUSEHAT (Kemenkes)
                </h5>
            </div>
            <form wire:submit.prevent="saveSatuSehat">
                <div class="p-6 space-y-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1.5 uppercase tracking-tight">Client ID <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="ss_client_id" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#0ab39c] transition-all shadow-sm @error('ss_client_id') border-red-500 @enderror" placeholder="Masukkan Client ID">
                        @error('ss_client_id') <span class="text-red-500 text-[10px] mt-1 font-bold">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1.5 uppercase tracking-tight">Client Secret <span class="text-red-500">*</span></label>
                        <textarea wire:model="ss_client_secret" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2 focus:border-[#0ab39c] transition-all shadow-sm @error('ss_client_secret') border-red-500 @enderror" rows="2" placeholder="Masukkan Client Secret"></textarea>
                        @error('ss_client_secret') <span class="text-red-500 text-[10px] mt-1 font-bold">{{ $message }}</span> @enderror
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1.5 uppercase tracking-tight">Organization ID <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="ss_organization_id" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#0ab39c] transition-all shadow-sm @error('ss_organization_id') border-red-500 @enderror" placeholder="ID Organisasi">
                            @error('ss_organization_id') <span class="text-red-500 text-[10px] mt-1 font-bold">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1.5 uppercase tracking-tight">Organization Name <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="ss_organization_name" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#0ab39c] transition-all shadow-sm @error('ss_organization_name') border-red-500 @enderror" placeholder="Nama Klinik">
                            @error('ss_organization_name') <span class="text-red-500 text-[10px] mt-1 font-bold">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1.5 uppercase tracking-tight">Practitioner ID <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="ss_practitioner_id" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#0ab39c] transition-all shadow-sm @error('ss_practitioner_id') border-red-500 @enderror" placeholder="ID Praktisi">
                            @error('ss_practitioner_id') <span class="text-red-500 text-[10px] mt-1 font-bold">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1.5 uppercase tracking-tight">Location ID <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="ss_location_id" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#0ab39c] transition-all shadow-sm @error('ss_location_id') border-red-500 @enderror" placeholder="ID Lokasi">
                            @error('ss_location_id') <span class="text-red-500 text-[10px] mt-1 font-bold">{{ $message }}</span> @enderror
                        </div>
                    </div>
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
                <div class="p-5 bg-gray-50/80 flex justify-end gap-3 border-t border-gray-100">
                    <button type="submit" class="btn bg-[#0d6efd] text-white font-bold text-xs uppercase tracking-widest px-6 h-10 shadow-md hover:bg-[#099885] hover:translate-y-[-2px] transition-all active:scale-95">
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
