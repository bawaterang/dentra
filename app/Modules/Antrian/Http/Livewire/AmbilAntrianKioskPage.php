<?php

namespace App\Modules\Antrian\Http\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\TrxAntrian;
use App\Models\MstPoli;

#[Layout('components.layouts.blank')]
class AmbilAntrianKioskPage extends Component
{
    public $nama_pasien;
    public $kode_poli;
    public $tanggal_antrian;

    public $poliList = [];
    public $generatedAntrian = null;

    public function mount()
    {
        $this->tanggal_antrian = now()->format('Y-m-d');
        $this->poliList = MstPoli::where('status', 'Aktif')->get();
    }

    public function setPoli($kode)
    {
        $this->kode_poli = $kode;
    }

    public function simpan()
    {
        $this->validate([
            'nama_pasien' => 'required|string|max:100',
            'kode_poli' => 'required',
        ], [
            'nama_pasien.required' => 'Silakan isi Nama Anda.',
            'kode_poli.required' => 'Silakan pilih Poli Tujuan.',
        ]);

        try {
            $duplicateCheck = clone TrxAntrian::query()
                ->where(['tanggal_antrian' => $this->tanggal_antrian])
                ->where(['nama_pasien_input_manual' => $this->nama_pasien])
                ->where(['kode_poli' => $this->kode_poli])
                ->exists();
                
            if ($duplicateCheck) {
                $this->addError('general', 'Anda sudah mengambil antrian untuk poli ini pada tanggal tersebut.');
                return;
            }

            $lastAntrian = TrxAntrian::where('tanggal_antrian', $this->tanggal_antrian)
                ->orderBy('nomor_antrian', 'desc')
                ->first();
            $nextNumber = $lastAntrian ? ((int)$lastAntrian->nomor_antrian + 1) : 1;
            $nomorAntrian = str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

            $antrian = TrxAntrian::create([
                'nomor_antrian' => $nomorAntrian,
                'tanggal_antrian' => $this->tanggal_antrian,
                'jenis_antrian' => 'offline',
                'nama_pasien_input_manual' => $this->nama_pasien,
                'kode_poli' => $this->kode_poli,
                'status' => 'menunggu',
            ]);

            $this->generatedAntrian = $antrian;

        } catch (\Exception $e) {
            $this->addError('general', 'Terjadi kesalahan sistem. Silakan coba lagi.');
        }
    }

    public function ambilLagi()
    {
        $this->reset(['nama_pasien', 'kode_poli', 'generatedAntrian']);
        $this->tanggal_antrian = now()->format('Y-m-d');
    }

    public function render()
    {
        return <<<'HTML'
        <div class="min-h-screen bg-gradient-to-br from-[#1a1d3e] via-[#2d3561] to-[#405189] flex flex-col items-center justify-center p-6 text-white" 
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
                <h1 class="text-4xl font-black tracking-wide uppercase">SIGI Dental Clinic</h1>
                <p class="text-lg opacity-70 tracking-widest uppercase mt-2">Self-Service Kiosk Antrian</p>
            </div>

            @if($generatedAntrian)
            <!-- Ticket Result -->
            <div class="w-full max-w-md animate-[bounce_0.5s_ease-out]">
                <!-- Screen UI (Hidden on Print) -->
                <div class="bg-white rounded-3xl shadow-2xl overflow-hidden text-center text-[#333] print:hidden">
                    <div class="bg-gradient-to-br from-[#405189] to-[#3577f1] p-8 text-white">
                        <p class="text-sm font-semibold uppercase tracking-widest opacity-80 mb-2">Nomor Antrian Anda</p>
                        <h1 class="text-8xl font-black my-4 leading-none">{{ $generatedAntrian->nomor_antrian }}</h1>
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
                
                <div class="flex gap-4 mt-8 print:hidden">
                    <button onclick="window.print()" class="flex-1 bg-white select-none text-[#405189] rounded-2xl py-5 text-lg font-bold shadow-xl hover:bg-gray-50 transition-all active:scale-95 flex items-center justify-center gap-3"><i class="ri-printer-line text-2xl"></i> CETAK TIKET</button>
                    <button wire:click="ambilLagi" class="flex-1 bg-[#0ab39c] select-none text-white rounded-2xl py-5 text-lg font-bold shadow-xl hover:bg-[#099885] transition-all active:scale-95 flex items-center justify-center gap-3"><i class="ri-add-line text-2xl"></i> AMBIL LAGI</button>
                </div>
            </div>
            @else
            <!-- Kiosk Form -->
            <div class="w-full max-w-2xl bg-white/10 backdrop-blur-xl border border-white/20 rounded-3xl p-10 shadow-2xl">
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
                        <label class="block text-white/80 font-bold mb-3 text-lg">Pilih Poli Tujuan</label>
                        <div class="grid grid-cols-2 gap-4">
                            @foreach($poliList as $poli)
                            <button type="button" wire:click="setPoli('{{ $poli->kode_poli }}')" class="flex items-center gap-4 p-5 rounded-2xl border-2 transition-all active:scale-95 select-none {{ $kode_poli === $poli->kode_poli ? 'bg-white text-[#405189] border-white' : 'bg-transparent text-white border-white/20 hover:bg-white/5' }}">
                                <div class="h-12 w-12 rounded-full flex items-center justify-center text-2xl {{ $kode_poli === $poli->kode_poli ? 'bg-[#405189]/10 text-[#405189]' : 'bg-white/10 text-white' }}">
                                    <i class="ri-hospital-line"></i>
                                </div>
                                <span class="font-bold text-xl">{{ $poli->nama_poli }}</span>
                            </button>
                            @endforeach
                        </div>
                        @error('kode_poli') <span class="text-red-300 block mt-2 font-medium"><i class="ri-alert-line mr-1"></i>{{ $message }}</span> @enderror
                    </div>

                    <!-- Tombol Ambil -->
                    <div class="pt-6">
                        <button type="submit" class="w-full bg-[#0ab39c] text-white rounded-2xl py-6 text-2xl font-black shadow-lg shadow-[#0ab39c]/30 hover:bg-[#099885] transition-all active:scale-[0.98] tracking-widest relative overflow-hidden group">
                            <span class="relative z-10 flex items-center justify-center gap-3">
                                <i class="ri-ticket-line text-3xl"></i> AMBIL NOMOR ANTRIAN
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
        HTML;
        HTML;
    }

    public function layout()
    {
        return 'components.layouts.blank';
    }
}
