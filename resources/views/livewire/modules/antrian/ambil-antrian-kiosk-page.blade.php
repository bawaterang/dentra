        <div class="min-h-screen bg-gradient-to-br from-[#1a1d3e] via-[#2d3561] to-[#405189] flex flex-col items-center py-10 md:py-20 px-4 sm:px-6 text-white" 
             x-data="{ 
                 isFullscreen: false,
                 toggleFullscreen() {
                     if (!document.fullscreenElement) {
                         document.documentElement.requestFullscreen().catch(err => {
                             console.log(`Batal fullscreen: ${err.message}`);
                         });
                     } else {
                         document.exitFullscreen();
                     }
                 }
             }"
             @fullscreenchange.window="isFullscreen = !!document.fullscreenElement">
            
            <!-- Fullscreen Toggle Button (visible only when not fullscreen) -->
            <button x-show="!isFullscreen" @click="toggleFullscreen" 
                    title="Layar Penuh (Fullscreen)"
                    class="fixed top-6 right-6 z-50 bg-white/10 hover:bg-white/25 backdrop-blur-md rounded-2xl w-14 h-14 flex items-center justify-center text-white border border-white/20 transition-all shadow-xl active:scale-95 group print:hidden" style="display: none;" x-transition>
                <i class="ri-fullscreen-line text-2xl group-hover:scale-110 transition-transform"></i>
            </button>
            
            <!-- Header Kiosk -->
            <div class="text-center mb-10">
                <div class="inline-flex h-20 w-20 items-center justify-center rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 text-white shadow-xl mb-4">
                    <i class="ri-hospital-line text-4xl"></i>
                </div>
                <h1 class="text-3xl md:text-4xl font-black tracking-wide uppercase">SIGI Dental Clinic</h1>
                <p class="text-base md:text-lg opacity-70 tracking-widest uppercase mt-2">Self-Service Kiosk Antrian</p>
            </div>

            @if($isHoliday)
            <!-- Holiday Screen -->
            <div class="w-full max-w-2xl bg-white rounded-3xl shadow-2xl p-12 text-center text-[#333] animate-[fadeIn_0.5s_ease-out]">
                <div class="w-32 h-32 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center mx-auto mb-8 animate-bounce">
                    <i class="ri-calendar-close-line text-6xl"></i>
                </div>
                <h2 class="text-4xl font-black text-orange-600 uppercase mb-4">MAAF, KLINIK LIBUR</h2>
                <div class="h-1 w-20 bg-orange-200 mx-auto mb-6"></div>
                <p class="text-xl font-bold text-gray-500 italic mb-8">"{{ $holidayMessage }}"</p>
                <div class="bg-gray-50 p-6 rounded-2xl border border-gray-100 flex items-center gap-4 text-left">
                    <i class="ri-information-line text-3xl text-orange-400"></i>
                    <p class="text-sm font-medium text-gray-600">Terima kasih atas pengertian Anda. Silakan kembali pada hari kerja berikutnya atau hubungi Admin untuk informasi lebih lanjut.</p>
                </div>
                <button onclick="window.location.reload()" class="mt-10 px-8 py-3 bg-gray-100 text-gray-600 rounded-full font-bold hover:bg-gray-200 transition-all flex items-center gap-2 mx-auto active:scale-95">
                    <i class="ri-refresh-line"></i> Refresh Halaman
                </button>
            </div>
            @elseif($generatedAntrian)
            <!-- Ticket Result -->
            <div class="w-full max-w-md animate-[bounce_0.5s_ease-out]">
                <!-- Screen UI (Hidden on Print) -->
                <div class="bg-white rounded-3xl shadow-2xl overflow-hidden text-center text-[#333] print:hidden">
                    <div class="bg-gradient-to-br from-[#405189] to-[#3577f1] p-8 text-white">
                        <p class="text-xs md:text-sm font-semibold uppercase tracking-widest opacity-80 mb-2">Nomor Antrian Anda</p>
                        <h1 class="text-6xl md:text-8xl font-black my-4 leading-none">{{ $generatedAntrian->nomor_antrian }}</h1>
                        <p class="text-base opacity-90 font-medium">{{ \Carbon\Carbon::parse($generatedAntrian->tanggal_antrian)->translatedFormat('l, d F Y') }}</p>
                    </div>
                    <div class="p-8 space-y-4">
                        <div>
                            <p class="text-xs text-[#878a99] uppercase tracking-wider font-bold mb-1">Nama Pasien</p>
                            <h3 class="text-xl font-bold text-[#495057]">{{ $generatedAntrian->nama_pasien_input_manual }}</h3>
                        </div>
                        <div class="h-px bg-gray-100 w-full"></div>
                        <div>
                            <p class="text-xs text-[#878a99] uppercase tracking-wider font-bold mb-1">Poli Tujuan</p>
                            <h3 class="text-xl font-bold text-[#0ab39c]">{{ $generatedAntrian->kode_poli }}</h3>
                        </div>
                        <div class="mt-6 p-4 bg-orange-50 rounded-xl border border-orange-100">
                            <p class="text-sm font-medium text-orange-800">Silakan cetak tiket dan tunggu panggilan di ruang tunggu.</p>
                        </div>
                    </div>
                </div>

                <!-- Thermal Print UI (Hidden on Screen) -->
                <div id="printArea" class="hidden text-black bg-white w-full">
                    <div class="text-center font-bold text-lg border-b border-dashed border-black pb-2 mb-2">
                        SIGI DENTAL CLINIC
                    </div>
                    <div class="text-center text-sm mb-1">Nomor Antrian</div>
                    <div class="text-center text-5xl font-black my-2">{{ $generatedAntrian->nomor_antrian }}</div>
                    <div class="text-center text-xs mb-3">{{ \Carbon\Carbon::parse($generatedAntrian->tanggal_antrian)->translatedFormat('l, d M Y') }}</div>
                    
                    <div class="text-sm border-t border-b border-dashed border-black py-2 mb-3">
                        <div class="flex justify-between my-1"><span>Nama:</span><span class="font-bold text-right ml-2 truncate">{{ $generatedAntrian->nama_pasien_input_manual }}</span></div>
                        @if($generatedAntrian->kode_poli)<div class="flex justify-between my-1"><span>Poli:</span><span class="font-bold text-right ml-2">{{ $generatedAntrian->kode_poli }}</span></div>@endif
                        <div class="flex justify-between my-1"><span>Jenis:</span><span class="font-bold text-right ml-2">{{ ucfirst($generatedAntrian->jenis_antrian) }}</span></div>
                    </div>
                    
                    <div class="text-center text-[10px]">
                        Simpan tiket ini.<br>Harap menunggu giliran Anda.
                    </div>
                </div>
                
                <div class="flex flex-col sm:flex-row gap-4 mt-8 print:hidden">
                    <button onclick="window.print()" class="flex-1 bg-white select-none text-[#405189] rounded-2xl py-4 md:py-5 text-lg font-bold shadow-xl hover:bg-gray-50 transition-all active:scale-95 flex items-center justify-center gap-3"><i class="ri-printer-line text-2xl"></i> CETAK TIKET</button>
                    <button wire:click="ambilLagi" class="flex-1 bg-[#0ab39c] select-none text-white rounded-2xl py-4 md:py-5 text-lg font-bold shadow-xl hover:bg-[#099885] transition-all active:scale-95 flex items-center justify-center gap-3"><i class="ri-add-line text-2xl"></i> AMBIL LAGI</button>
                </div>
            </div>
            @else
            <!-- Kiosk Form -->
            <div class="w-full max-w-2xl bg-white/10 backdrop-blur-xl border border-white/20 rounded-3xl p-6 md:p-10 shadow-2xl">
                <form wire:submit.prevent="simpan" class="space-y-8">
                    @error('general') <div class="bg-red-500/20 border border-red-500/50 text-white px-4 py-3 rounded-xl text-center">{{ $message }}</div> @enderror

                    <!-- Input Nama -->
                    <div>
                        <label class="block text-white/80 font-bold mb-3 text-lg">Nama Lengkap</label>
                        <div class="relative">
                            <i class="ri-user-line absolute left-5 top-1/2 -translate-y-1/2 text-white/50 text-2xl"></i>
                            <input type="text" wire:model="nama_pasien" class="w-full bg-white/5 border border-white/10 text-white rounded-2xl py-5 pl-14 pr-6 text-xl focus:border-white/50 focus:bg-white/10 transition-all placeholder:text-white/30 outline-none" placeholder="Masukkan nama Anda di sini..." autocomplete="off">
                        </div>
                        @error('nama_pasien') <span class="text-red-300 block mt-2 font-medium"><i class="ri-alert-line mr-1"></i>{{ $message }}</span> @enderror
                    </div>

                    <!-- Pilih Poli -->
                    <div>
                        <label class="block text-white/80 font-bold mb-3 text-lg">1. Pilih Poli Tujuan</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @foreach($poliList as $poli)
                            <button type="button" wire:click="setPoli('{{ $poli->kode_poli }}')" class="flex items-center gap-3 md:gap-4 p-4 md:p-5 rounded-2xl border-2 transition-all active:scale-95 select-none {{ $kode_poli === $poli->kode_poli ? 'bg-white text-[#405189] border-white' : 'bg-transparent text-white border-white/20 hover:bg-white/5' }}">
                                <div class="h-10 w-10 md:h-12 md:w-12 rounded-full flex items-center justify-center text-xl md:text-2xl {{ $kode_poli === $poli->kode_poli ? 'bg-[#405189]/10 text-[#405189]' : 'bg-white/10 text-white' }}">
                                    <i class="ri-hospital-line"></i>
                                </div>
                                <span class="font-bold text-lg md:text-xl">{{ $poli->nama_poli }}</span>
                            </button>
                            @endforeach
                        </div>
                        @error('kode_poli') <span class="text-red-300 block mt-2 font-medium"><i class="ri-alert-line mr-1"></i>{{ $message }}</span> @enderror
                    </div>

                    @if($kode_poli)
                    <!-- Pilih Dokter -->
                    <div class="animate-[fadeIn_0.3s_ease-out]">
                        <label class="block text-white/80 font-bold mb-3 text-lg">2. Pilih Dokter</label>
                        @if(count($dokterList) > 0)
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @foreach($dokterList as $doc)
                            <button type="button" wire:click="setDokter('{{ $doc['kode_dokter'] }}')" class="flex items-center gap-3 md:gap-4 p-4 md:p-5 rounded-2xl border-2 transition-all active:scale-95 select-none {{ $kode_dokter === $doc['kode_dokter'] ? 'bg-white text-[#405189] border-white shadow-lg' : 'bg-transparent text-white border-white/20 hover:bg-white/5' }}">
                                <div class="h-10 w-10 md:h-12 md:w-12 rounded-full flex items-center justify-center text-xl md:text-2xl" style="background-color: {{ $kode_dokter === $doc['kode_dokter'] ? '#40518920' : ($doc['color'] ?? '#ffffff20') }}; color: {{ $kode_dokter === $doc['kode_dokter'] ? '#405189' : 'white' }}">
                                    <i class="ri-user-star-line"></i>
                                </div>
                                <div class="text-left">
                                    <span class="font-bold text-lg block leading-tight">{{ $doc['nama_dokter'] }}</span>
                                    <span class="text-xs opacity-60 uppercase tracking-widest font-black">{{ $doc['spesialisasi'] ?? 'Umum' }}</span>
                                </div>
                            </button>
                            @endforeach
                        </div>
                        @else
                        <div class="bg-orange-500/20 border border-orange-500/50 p-6 rounded-2xl flex items-center gap-4 text-white">
                            <i class="ri-error-warning-line text-3xl"></i>
                            <div>
                                <p class="font-bold text-xl">Dokter Tidak Tersedia</p>
                                <p class="opacity-80">Maaf, saat ini belum ada dokter yang ditugaskan di poli {{ collect($poliList)->where('kode_poli', $kode_poli)->first()->nama_poli ?? '' }}.</p>
                            </div>
                        </div>
                        @endif
                        @error('kode_dokter') <span class="text-red-300 block mt-2 font-medium"><i class="ri-alert-line mr-1"></i>{{ $message }}</span> @enderror
                    </div>
                    @endif

                    @if($mode_antrian !== 'Nomor Urut' && $kode_dokter)
                    <!-- Pilih Waktu -->
                    <div class="animate-[fadeIn_0.3s_ease-out]">
                        <label class="block text-white/80 font-bold mb-3 text-lg">3. Pilih Slot Waktu Kehadiran</label>
                        @if(count($availableTimeSlots) > 0)
                        <div class="flex gap-3 overflow-x-auto pb-4 no-scrollbar snap-x snap-mandatory">
                            @foreach($availableTimeSlots as $slot)
                            <button type="button" wire:click="setTimeSlot('{{ $slot['value'] }}')" class="flex-shrink-0 snap-center p-4 rounded-2xl border-2 transition-all active:scale-95 select-none text-center min-w-[120px] {{ $time_slot === $slot['value'] ? 'bg-white text-[#405189] border-white shadow-[0_0_20px_rgba(255,255,255,0.3)]' : 'bg-white/5 text-white border-white/20 hover:bg-white/10' }}">
                                <i class="ri-time-line text-2xl mb-1 block {{ $time_slot === $slot['value'] ? 'text-[#0ab39c]' : 'text-white/70' }}"></i>
                                <span class="font-black text-xl">{{ $slot['label'] }}</span>
                            </button>
                            @endforeach
                        </div>
                        @else
                        <div class="bg-red-500/20 border border-red-500/50 p-4 rounded-xl flex items-center gap-3 text-white">
                            <i class="ri-error-warning-line text-2xl"></i>
                            <div>
                                <p class="font-bold">Slot Habis</p>
                                <p class="text-sm opacity-80">Maaf, semua slot waktu untuk dokter ini pada hari ini sudah penuh dipesan.</p>
                            </div>
                        </div>
                        @endif
                        @error('time_slot') <span class="text-red-300 block mt-2 font-medium"><i class="ri-alert-line mr-1"></i>{{ $message }}</span> @enderror
                    </div>
                    @endif

                    <!-- Tombol Ambil -->
                    <div class="pt-6">
                        <button type="submit" class="w-full bg-[#0ab39c] text-white rounded-2xl py-5 md:py-6 text-xl md:text-2xl font-black shadow-lg shadow-[#0ab39c]/30 hover:bg-[#099885] transition-all active:scale-[0.98] tracking-widest relative overflow-hidden group">
                            <span class="relative z-10 flex items-center justify-center gap-3">
                                <i class="ri-ticket-line text-2xl md:text-3xl"></i> AMBIL NOMOR ANTRIAN
                            </span>
                            <div class="absolute inset-0 h-full w-full bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
                        </button>
                    </div>
                </form>
            </div>
            @endif

            <!-- Footer -->
            <div class="mt-12 text-center text-white/40 text-sm font-medium print:hidden">
                <p>&copy; {{ date('Y') }} Sistem Informasi Manajemen Klinik SIGI</p>
                <p x-show="!isFullscreen" x-transition>Tekan tombol di pojok kanan atas atau <kbd class="px-2 py-1 bg-white/10 rounded-md mx-1 font-sans text-xs">F11</kbd> untuk tampilan layar penuh</p>
            </div>

            <style>
                @keyframes fadeIn {
                    from { opacity: 0; transform: translateY(10px); }
                    to { opacity: 1; transform: translateY(0); }
                }
                @media print {
                    @page { margin: 0; }
                    body * { visibility: hidden; }
                    #printArea, #printArea * { visibility: visible; color: black !important; }
                    #printArea {
                        display: block !important;
                        position: absolute;
                        left: 0;
                        top: 0;
                        width: 100%;
                        max-width: 100%;
                        margin: 0;
                        padding: 5mm;
                        font-family: monospace;
                        background: white !important;
                    }
                }
            </style>
        </div>