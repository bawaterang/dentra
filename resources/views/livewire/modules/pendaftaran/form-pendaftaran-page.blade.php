        <div>
            <div class="page-header">
                <div class="page-header-title">
                    <div class="page-header-icon bg-gradient-to-br from-[#405189] to-[#2a3a6a] text-white shadow-lg animate-pulse" style="animation-duration: 3s;">
                        <i class="ri-file-add-line"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold tracking-tight text-[#2c3e50]">Form Pendaftaran</h1>
                        <p class="text-xs text-[#878a99] font-medium mt-0.5">Formulir pendaftaran pasien untuk mendapatkan layanan kesehatan.</p>
                    </div>
                </div>
                <div class="page-header-breadcrumb">
                    <a href="/dashboard" wire:navigate class="hover:text-[#405189] transition-colors"><i class="ri-home-4-line"></i></a>
                    <span class="sep text-gray-300">/</span>
                    <a href="{{ route('pendaftaran.index') }}" wire:navigate class="hover:text-[#405189] transition-colors text-gray-400 font-medium">Pendaftaran</a>
                    <span class="sep text-gray-300">/</span>
                    <span class="text-[#405189] font-bold">Buat Baru</span>
                </div>
            </div>

            <div class="max-w-4xl mx-auto">
                <form wire:submit.prevent="save">
                @error('general') <div class="bg-red-500/10 border border-red-500/50 text-red-500 px-4 py-3 rounded-xl mb-4 text-sm font-semibold"><i class="ri-alert-line mr-2"></i>{{ $message }}</div> @enderror
                <!-- Mode Toggle -->
                <div class="card border-t-2 border-[#405189] mb-6" style="overflow: visible !important;">
                    <div class="p-5">
                        <div class="flex items-center gap-3 mb-4">
                            <button type="button" wire:click="$set('modePasien','lama')" class="flex-1 p-4 rounded-xl border-2 transition-all {{ $modePasien === 'lama' ? 'border-[#405189] bg-[#405189]/5' : 'border border-gray-300 hover:border-gray-300' }}">
                                <div class="flex items-center gap-3"><div class="h-10 w-10 rounded-lg flex items-center justify-center {{ $modePasien === 'lama' ? 'bg-[#405189] text-white' : 'bg-gray-100 text-gray-400' }}"><i class="ri-user-search-line text-lg"></i></div><div class="text-left"><p class="font-bold text-sm {{ $modePasien === 'lama' ? 'text-[#405189]' : 'text-gray-500' }}">Pasien Lama</p><p class="text-[11px] text-[#878a99]">Cari dari data master pasien</p></div></div>
                            </button>
                            <button type="button" wire:click="$set('modePasien','baru')" class="flex-1 p-4 rounded-xl border-2 transition-all {{ $modePasien === 'baru' ? 'border-[#0ab39c] bg-[#0ab39c]/5' : 'border border-gray-300 hover:border-gray-300' }}">
                                <div class="flex items-center gap-3"><div class="h-10 w-10 rounded-lg flex items-center justify-center {{ $modePasien === 'baru' ? 'bg-[#0ab39c] text-white' : 'bg-gray-100 text-gray-400' }}"><i class="ri-user-add-line text-lg"></i></div><div class="text-left"><p class="font-bold text-sm {{ $modePasien === 'baru' ? 'text-[#0ab39c]' : 'text-gray-500' }}">Pasien Baru</p><p class="text-[11px] text-[#878a99]">Daftarkan pasien baru</p></div></div>
                            </button>
                        </div>

                        @if($modePasien === 'lama')
                        <!-- Pasien Lama Search -->
                        @if($selectedPasien)
                        <div class="p-4 rounded-xl bg-[#405189]/5 border border-[#405189]/20">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="h-12 w-12 rounded-full bg-[#405189] text-white flex items-center justify-center font-bold text-lg">{{ strtoupper(substr($selectedPasien['nama_pasien'],0,1)) }}</div>
                                    <div><p class="font-bold text-[#405189]">{{ $selectedPasien['nama_pasien'] }}</p><p class="text-xs text-[#878a99]">{{ $selectedPasien['no_rm'] }} · {{ $selectedPasien['nik'] ?? '-' }} · {{ $selectedPasien['jenis_kelamin'] }}</p></div>
                                </div>
                                <button type="button" wire:click="resetPasien" class="text-xs text-red-500 hover:text-red-700 font-bold"><i class="ri-close-line"></i> Ganti</button>
                            </div>
                        </div>
                        @else
                        <div class="relative"><input type="text" wire:model.live.debounce.300ms="searchPasien" class="w-full rounded-lg border border-gray-300 text-sm pl-10 pr-4 h-[42px] focus:border-[#405189] transition-all" placeholder="Cari pasien berdasarkan Nama, NIK, No RM, atau No HP..."><i class="ri-search-line absolute left-3.5 top-1/2 -translate-y-1/2 text-[#878a99]"></i></div>
                        @if(count($pasienResults) > 0)
                        <div class="mt-2 max-h-[200px] overflow-y-auto space-y-1 border rounded-lg p-2">
                            @foreach($pasienResults as $p)
                            <button type="button" wire:click="pilihPasienLama({{ $p['id'] }})" class="w-full text-left p-2.5 rounded-lg hover:bg-[#405189]/5 transition-all text-sm"><span class="font-semibold">{{ $p['nama_pasien'] }}</span> <span class="text-[11px] text-[#878a99]">· {{ $p['no_rm'] }} · NIK: {{ $p['nik'] ?? '-' }}</span></button>
                            @endforeach
                        </div>
                        @endif
                        @endif
                        @else
                        <!-- Pasien Baru Form -->
                        <div class="space-y-4 mt-2">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div><label class="block text-xs font-semibold text-gray-500 mb-1">Nama Pasien <span class="text-red-500">*</span></label><input type="text" wire:model="nama_pasien" class="w-full rounded-lg border border-gray-300 text-sm px-4 h-[42px] focus:border-[#405189] transition-all" placeholder="Nama lengkap">@error('nama_pasien')<span class="text-[11px] text-red-500 italic">{{ $message }}</span>@enderror</div>
                                <div><label class="block text-xs font-semibold text-gray-500 mb-1">NIK</label><input type="text" wire:model="nik" class="w-full rounded-lg border border-gray-300 text-sm px-4 h-[42px] focus:border-[#405189] transition-all" placeholder="Nomor Induk Kependudukan"></div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div><label class="block text-xs font-semibold text-gray-500 mb-1">Jenis Kelamin <span class="text-red-500">*</span></label><x-custom-dropdown model="jenis_kelamin" :options="$jkList" placeholder="Pilih JK" /></div>
                                <div><label class="block text-xs font-semibold text-gray-500 mb-1">Tempat Lahir</label><input type="text" wire:model="tempat_lahir" class="w-full rounded-lg border border-gray-300 text-sm px-4 h-[42px] focus:border-[#405189] transition-all" placeholder="Contoh: Jakarta"></div>
                                <div><label class="block text-xs font-semibold text-gray-500 mb-1">Tanggal Lahir</label><input type="date" wire:model="tanggal_lahir" class="w-full rounded-lg border border-gray-300 text-sm px-4 h-[42px] focus:border-[#405189] transition-all"></div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div><label class="block text-xs font-semibold text-gray-500 mb-1">Agama</label><x-custom-dropdown model="agama" :options="$agamaList" placeholder="Pilih Agama" /></div>
                                <div><label class="block text-xs font-semibold text-gray-500 mb-1">Gol. Darah</label><x-custom-dropdown model="golongan_darah" :options="$golDarahList" placeholder="Pilih" /></div>
                                <div><label class="block text-xs font-semibold text-gray-500 mb-1">No Telepon</label><input type="text" wire:model="no_telepon" class="w-full rounded-lg border border-gray-300 text-sm px-4 h-[42px] focus:border-[#405189] transition-all"></div>
                            </div>
                            <div><label class="block text-xs font-semibold text-gray-500 mb-1">Alamat</label><textarea wire:model="alamat" rows="2" class="w-full rounded-lg border border-gray-300 text-sm px-4 py-2.5 focus:border-[#405189] transition-all" placeholder="Alamat lengkap pasien..."></textarea></div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Profil Pasien (Hanya tampil jika Pasien Lama dipilih) -->
                @if($modePasien === 'lama' && $selectedPasien)
                <div class="card border-t-2 border-[#f7b84b] mb-6 relative z-10" style="overflow: visible !important;">
                    <div class="px-6 py-4 border-b border-gray-100 bg-[#f3f6f9]/50 flex justify-between items-center">
                        <h6 class="text-sm font-bold text-[#f7b84b]"><i class="ri-user-heart-line mr-2"></i>Data Profil Pasien</h6>
                        <button type="button" wire:click="editPasien" class="btn bg-white border border-gray-300 text-gray-600 hover:bg-gray-50 hover:text-[#405189] px-3 py-1.5 text-xs font-bold rounded-lg flex items-center gap-1 transition-all shadow-sm"><i class="ri-edit-2-line"></i> Edit Data</button>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div><p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider mb-1">NIK</p><p class="text-sm font-semibold text-gray-800">{{ $selectedPasien['nik'] ?? '-' }}</p></div>
                            <div><p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider mb-1">Tempat, Tanggal Lahir</p><p class="text-sm font-semibold text-gray-800">{{ $selectedPasien['tempat_lahir'] ?? '-' }}, {{ $selectedPasien['tanggal_lahir'] ? \Carbon\Carbon::parse($selectedPasien['tanggal_lahir'])->format('d M Y') : '-' }}</p></div>
                            <div><p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider mb-1">Gol. Darah</p><p class="text-sm font-semibold text-gray-800">{{ $selectedPasien['golongan_darah'] ?? '-' }}</p></div>
                            <div><p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider mb-1">Agama</p><p class="text-sm font-semibold text-gray-800">{{ $selectedPasien['agama'] ?? '-' }}</p></div>
                            <div><p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider mb-1">Pekerjaan</p><p class="text-sm font-semibold text-gray-800">{{ $selectedPasien['pekerjaan'] ?? '-' }}</p></div>
                            <div><p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider mb-1">No Telepon</p><p class="text-sm font-semibold text-gray-800">{{ $selectedPasien['no_telepon'] ?? '-' }}</p></div>
                            <div class="col-span-2"><p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider mb-1">Alamat Lengkap</p><p class="text-sm font-semibold text-gray-800">{{ $selectedPasien['alamat'] ?? '-' }}</p></div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Informasi Kunjungan -->
                <div class="card border-t-2 border-[#0ab39c] mb-6 relative z-50" style="overflow: visible !important;">
                    <div class="px-6 py-4 border-b border-gray-100 bg-[#f3f6f9]/50"><h6 class="text-sm font-bold text-[#0ab39c]"><i class="ri-hospital-line mr-2"></i>Informasi Kunjungan</h6></div>
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div><label class="block text-xs font-semibold text-gray-500 mb-1">Tanggal Kunjungan <span class="text-red-500">*</span></label><input type="date" wire:model.live="tanggal_antrian" class="w-full rounded-lg border border-gray-300 text-sm px-4 h-[42px] focus:border-[#405189] transition-all"></div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Jenis Kunjungan</label>
                                <x-custom-dropdown model="jenis_antrian" :options="[
                                    ['value' => 'offline', 'label' => 'Offline (Datang Langsung)', 'icon' => 'ri-walk-line text-blue-500'],
                                    ['value' => 'online', 'label' => 'Online (Booking)', 'icon' => 'ri-global-line text-green-500'],
                                    ['value' => 'mobile_jkn', 'label' => 'Mobile JKN', 'icon' => 'ri-smartphone-line text-purple-500']
                                ]" placeholder="Pilih Jenis" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div><label class="block text-xs font-semibold text-gray-500 mb-1">Poli Tujuan <span class="text-red-500">*</span></label><x-custom-dropdown model="poli_id" :options="$poliList" placeholder="Pilih Poli" searchable="true" live="true" />@error('poli_id')<span class="text-[11px] text-red-500 italic">{{ $message }}</span>@enderror</div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Dokter <span class="text-red-500">*</span></label>
                                <x-custom-dropdown model="dokter_id" :options="$dokterList" placeholder="{{ $poli_id && empty($dokterList) ? 'Tidak ada dokter di poli ini' : 'Pilih Dokter' }}" searchable="true" live="true" />
                                @if($poli_id && empty($dokterList))
                                    <span class="text-[10px] text-orange-500 font-bold italic mt-1 flex items-center gap-1"><i class="ri-information-line"></i> Tidak ada dokter tersedia di poli pilihan.</span>
                                @endif
                                @error('dokter_id')<span class="text-[11px] text-red-500 italic">{{ $message }}</span>@enderror
                            </div>
                        </div>

                        @if($mode_antrian !== 'Nomor Urut')
                        <div class="p-4 bg-blue-50 border border-blue-100 rounded-lg">
                            <label class="block text-xs font-bold text-[#405189] mb-2">Slot Waktu Periksa <span class="text-red-500">*</span></label>
                            @if(count($availableTimeSlots) > 0)
                                <x-custom-dropdown model="time_slot" :options="$availableTimeSlots" placeholder="Pilih Slot Waktu..." searchable="true" :disabled="empty($dokter_id)" />
                                @error('time_slot') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                            @else
                                <div class="text-xs text-orange-600 font-bold flex items-center gap-2"><i class="ri-error-warning-line"></i> Tidak ada slot waktu tersedia (Pastikan Poli & Dokter telah dipilih).</div>
                            @endif
                        </div>
                        @endif

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div><label class="block text-xs font-semibold text-gray-500 mb-1">Asuransi</label><x-custom-dropdown model="asuransi_id" :options="$asuransiList" placeholder="Umum (tanpa asuransi)" searchable="true" /></div>
                            <div class="flex gap-2 items-end">
                                <div class="flex-1"><label class="block text-xs font-semibold text-gray-500 mb-1">No Kartu Asuransi</label><input type="text" wire:model="no_kartu_asuransi" class="w-full rounded-lg border border-gray-300 text-sm px-4 h-[42px] focus:border-[#405189] transition-all" placeholder="Nomor kartu"></div>
                                <button type="button" wire:click="cekBpjs" class="h-[42px] px-4 rounded-lg bg-[#0ab39c] text-white text-xs font-bold hover:bg-[#099885] transition-all whitespace-nowrap flex items-center gap-1"><i class="ri-search-eye-line"></i> Cek BPJS</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Data Medis Awal -->
                <div class="card border-t-2 border-[#f7b84b] mb-6 relative z-10" style="overflow: visible !important;">
                    <div class="px-6 py-4 border-b border-gray-100 bg-[#f3f6f9]/50"><h6 class="text-sm font-bold text-[#f7b84b]"><i class="ri-heart-pulse-line mr-2"></i>Data Medis Awal</h6></div>
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            <div><label class="block text-xs font-semibold text-gray-500 mb-1">Kesadaran</label><x-custom-dropdown model="kesadaran" :options="$kesadaranList" placeholder="Pilih" /></div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Tekanan Darah (mmHg)</label>
                                <div x-data="{ 
                                    sys: '', 
                                    dia: '',
                                    sync() { $wire.tekanan_darah = (this.sys || '') + '/' + (this.dia || '') }
                                }" x-init="
                                    const update = (val) => {
                                        if (!val) { sys = ''; dia = ''; return; }
                                        let p = val.split('/');
                                        sys = p[0] || '';
                                        dia = p[1] || '';
                                    };
                                    update($wire.tekanan_darah);
                                    $watch('$wire.tekanan_darah', val => update(val));
                                " class="flex items-center w-full rounded-lg border border-gray-300 text-sm h-[42px] focus-within:border-[#405189] focus-within:ring-1 focus-within:ring-[#405189] transition-all bg-white px-3">
                                    <input type="text" x-model="sys" x-ref="sysInput" maxlength="3" 
                                        @input="sys = sys.replace(/\D/g, ''); if(sys.length >= 3) $refs.diaInput.focus(); sync();" 
                                        @keydown.slash.prevent="$refs.diaInput.focus()"
                                        class="w-10 text-center border-none focus:ring-0 p-0 bg-transparent" placeholder="120">
                                    <span class="text-gray-400 font-bold mx-1">/</span>
                                    <input type="text" x-model="dia" x-ref="diaInput" maxlength="3" 
                                        @input="dia = dia.replace(/\D/g, ''); sync();" 
                                        @keydown.backspace="if (dia.length === 0) { $refs.sysInput.focus(); }"
                                        class="w-10 text-center border-none focus:ring-0 p-0 bg-transparent" placeholder="80">
                                    <span class="ml-auto text-gray-400 text-[10px] font-bold uppercase">mmHg</span>
                                </div>
                            </div>
                            <div><label class="block text-xs font-semibold text-gray-500 mb-1">Nadi</label><input type="number" wire:model="nadi" class="w-full rounded-lg border border-gray-300 text-sm px-4 h-[42px] focus:border-[#405189] transition-all" placeholder="x/menit"></div>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div><label class="block text-xs font-semibold text-gray-500 mb-1">Suhu (°C)</label><input type="number" step="0.1" wire:model="suhu" class="w-full rounded-lg border border-gray-300 text-sm px-4 h-[42px] focus:border-[#405189] transition-all" placeholder="36.5"></div>
                            <div><label class="block text-xs font-semibold text-gray-500 mb-1">Berat Badan (kg)</label><input type="number" step="0.1" wire:model="berat_badan" class="w-full rounded-lg border border-gray-300 text-sm px-4 h-[42px] focus:border-[#405189] transition-all" placeholder="60"></div>
                            <div><label class="block text-xs font-semibold text-gray-500 mb-1">Tinggi Badan (cm)</label><input type="number" step="0.1" wire:model="tinggi_badan" class="w-full rounded-lg border border-gray-300 text-sm px-4 h-[42px] focus:border-[#405189] transition-all" placeholder="170"></div>
                            <div><label class="block text-xs font-semibold text-gray-500 mb-1">Lingkar Perut (cm)</label><input type="number" step="0.1" wire:model="lingkar_perut" class="w-full rounded-lg border border-gray-300 text-sm px-4 h-[42px] focus:border-[#405189] transition-all" placeholder="80"></div>
                        </div>
                        <div><label class="block text-xs font-semibold text-gray-500 mb-1">Riwayat Penyakit</label><textarea wire:model="riwayat_penyakit" rows="2" class="w-full rounded-lg border border-gray-300 text-sm px-4 py-2.5 focus:border-[#405189] transition-all" placeholder="Riwayat penyakit sebelumnya..."></textarea></div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div><label class="block text-xs font-semibold text-gray-500 mb-1">Alergi (Master)</label><x-custom-dropdown model="kode_alergi" :options="$alergiList" placeholder="Pilih Alergi" searchable="true" /></div>
                            <div><label class="block text-xs font-semibold text-gray-500 mb-1">Keterangan Alergi</label><textarea wire:model="alergi" rows="2" class="w-full rounded-lg border border-gray-300 text-sm px-4 py-2.5 focus:border-[#405189] transition-all" placeholder="Keterangan tambahan alergi..."></textarea></div>
                            <div><label class="block text-xs font-semibold text-gray-500 mb-1">Keterangan Lain</label><textarea wire:model="keterangan_lain" rows="2" class="w-full rounded-lg border border-gray-300 text-sm px-4 py-2.5 focus:border-[#405189] transition-all" placeholder="Catatan tambahan..."></textarea></div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-between gap-3 mb-8">
                    <a href="{{ route('pendaftaran.index') }}" wire:navigate class="btn bg-orange-500 text-white px-6 h-10 flex items-center gap-2 transition-all hover:bg-orange-600"><i class="ri-arrow-go-back-line"></i> Kembali</a>
                    <button type="submit" wire:loading.attr="disabled" class="btn bg-[#0d6efd] text-white px-8 h-10 shadow-md flex items-center justify-center gap-2 transition-all hover:bg-[#0b5ed7] hover:translate-y-[-2px] disabled:opacity-70"><svg wire:loading wire:target="save" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><i wire:loading.remove wire:target="save" class="ri-save-line"></i><span wire:loading.remove wire:target="save">Simpan Pendaftaran</span><span wire:loading wire:target="save">Memproses...</span></button>
                </div>
                </form>
            </div>

            <!-- Modal Edit Pasien Lama -->
            @if($showEditPasienModal)
            <div class="fixed inset-0 z-[1050] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
                <div class="w-full max-w-3xl bg-white rounded-xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
                    <div class="px-6 py-4 flex items-center justify-between border-b border-gray-100 bg-[#f3f6f9]/50">
                        <h5 class="text-lg font-bold text-[#495057]"><i class="ri-edit-2-line mr-2 text-[#405189]"></i>Edit Profil Pasien</h5>
                        <button type="button" wire:click="$set('showEditPasienModal', false)" class="text-gray-400 hover:text-gray-600"><i class="ri-close-line text-2xl"></i></button>
                    </div>
                    <div class="px-8 py-6 overflow-y-auto flex-1">
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div><label class="block text-xs font-semibold text-gray-500 mb-1">Nama Pasien <span class="text-red-500">*</span></label><input type="text" wire:model="nama_pasien" class="w-full rounded-lg border border-gray-300 text-sm px-4 h-[42px] focus:border-[#405189] transition-all">@error('nama_pasien')<span class="text-[11px] text-red-500 italic">{{ $message }}</span>@enderror</div>
                                <div><label class="block text-xs font-semibold text-gray-500 mb-1">NIK</label><input type="text" wire:model="nik" class="w-full rounded-lg border border-gray-300 text-sm px-4 h-[42px] focus:border-[#405189] transition-all"></div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div><label class="block text-xs font-semibold text-gray-500 mb-1">Jenis Kelamin <span class="text-red-500">*</span></label><x-custom-dropdown model="jenis_kelamin" :options="$jkList" placeholder="Pilih JK" /></div>
                                <div><label class="block text-xs font-semibold text-gray-500 mb-1">Tempat Lahir</label><input type="text" wire:model="tempat_lahir" class="w-full rounded-lg border border-gray-300 text-sm px-4 h-[42px] focus:border-[#405189] transition-all" placeholder="Contoh: Jakarta"></div>
                                <div><label class="block text-xs font-semibold text-gray-500 mb-1">Tanggal Lahir</label><input type="date" wire:model="tanggal_lahir" class="w-full rounded-lg border border-gray-300 text-sm px-4 h-[42px] focus:border-[#405189] transition-all"></div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div><label class="block text-xs font-semibold text-gray-500 mb-1">Agama</label><x-custom-dropdown model="agama" :options="$agamaList" placeholder="Pilih Agama" /></div>
                                <div><label class="block text-xs font-semibold text-gray-500 mb-1">Gol. Darah</label><x-custom-dropdown model="golongan_darah" :options="$golDarahList" placeholder="Pilih" /></div>
                                <div><label class="block text-xs font-semibold text-gray-500 mb-1">No Telepon</label><input type="text" wire:model="no_telepon" class="w-full rounded-lg border border-gray-300 text-sm px-4 h-[42px] focus:border-[#405189] transition-all"></div>
                            </div>
                            <div><label class="block text-xs font-semibold text-gray-500 mb-1">Alamat</label><textarea wire:model="alamat" rows="2" class="w-full rounded-lg border border-gray-300 text-sm px-4 py-2.5 focus:border-[#405189] transition-all" placeholder="Alamat lengkap pasien..."></textarea></div>
                            <div><label class="block text-xs font-semibold text-gray-500 mb-1">Pekerjaan</label><input type="text" wire:model="pekerjaan" class="w-full rounded-lg border border-gray-300 text-sm px-4 h-[42px] focus:border-[#405189] transition-all"></div>
                        </div>
                    </div>
                    <div class="px-8 py-5 bg-gray-50/80 flex justify-end gap-3 border-t border-gray-100">
                        <button type="button" wire:click="$set('showEditPasienModal', false)" class="btn bg-gray-500 text-white px-6 h-10 flex items-center gap-2 transition-all hover:bg-gray-600"><i class="ri-close-line"></i> Batal</button>
                        <button type="button" wire:click="updatePasienLama" class="btn bg-[#0d6efd] text-white px-8 h-10 shadow-md flex items-center justify-center gap-2 transition-all hover:bg-[#0b5ed7]"><i class="ri-save-line"></i> Simpan Perubahan</button>
                    </div>
                </div>
            </div>
            @endif
        </div>