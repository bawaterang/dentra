<?php

namespace App\Modules\Antrian\Http\Livewire;

use Livewire\Component;
use App\Models\TrxAntrian;

class MonitorAntrianPage extends Component
{
    public $tanggalAntrian;
    public $currentCalled = null;
    public $waitingList = [];
    public $isOpen = true;
    public $runningText = '';

    public function mount()
    {
        $this->tanggalAntrian = now()->format('Y-m-d');
        $this->refreshData();
    }

    public function refreshData()
    {
        $this->currentCalled = TrxAntrian::with('pasien')
            ->where('tanggal_antrian', $this->tanggalAntrian)
            ->where('status', 'dipanggil')
            ->orderBy('waktu_panggil', 'desc')
            ->first();

        $this->waitingList = TrxAntrian::with('pasien')
            ->where('tanggal_antrian', $this->tanggalAntrian)
            ->where('status', 'menunggu')
            ->orderBy('nomor_antrian')
            ->limit(10)
            ->get();

        $setting = \App\Models\MstSettingAntrian::first();
        if ($setting && $setting->running_text) {
            $this->runningText = $setting->running_text;
        } else {
            $this->runningText = "Selamat datang di SIGI Dental Clinic! Mohon menunggu antrian Anda dipanggil.";
        }
    }

    public function getListeners()
    {
        return ['refresh-monitor' => 'refreshData'];
    }

    public function render()
    {
        $this->refreshData();

        return <<<'HTML'
        <div wire:poll.3s="refreshData" class="min-h-screen bg-gradient-to-br from-[#1a1d3e] via-[#2d3561] to-[#405189] text-white flex flex-col" x-data="{ fullscreen: false }">
            <!-- Header -->
            <div class="flex items-center justify-between px-8 py-4 bg-black/20 backdrop-blur-sm">
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <div class="h-3 w-3 rounded-full {{ $isOpen ? 'bg-green-400 animate-pulse' : 'bg-red-400' }}"></div>
                        <span class="text-sm font-semibold uppercase tracking-wider opacity-80">{{ $isOpen ? 'Antrian Dibuka' : 'Antrian Ditutup' }}</span>
                    </div>
                </div>
                <div class="text-center">
                    <h1 class="text-2xl font-black tracking-wide">SIGI DENTAL CLINIC</h1>
                    <p class="text-xs opacity-60 tracking-widest uppercase">Sistem Antrian Digital</p>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold" x-data="{ time: '' }" x-init="setInterval(()=>{ time = new Date().toLocaleTimeString('id-ID') }, 1000)" x-text="time"></div>
                    <p class="text-xs opacity-60">{{ \Carbon\Carbon::parse($tanggalAntrian)->translatedFormat('l, d F Y') }}</p>
                </div>
            </div>

            <!-- Main Content -->
            <div class="flex-1 grid grid-cols-5 gap-6 p-8">
                <!-- Current Called - Large Display -->
                <div class="col-span-3 flex items-center justify-center">
                    @if($currentCalled)
                    <div class="text-center animate-pulse-slow">
                        <p class="text-xl font-semibold uppercase tracking-[0.3em] text-white/60 mb-4">Antrian Dipanggil</p>
                        <div class="bg-white/10 backdrop-blur-md rounded-3xl p-12 border border-white/20 shadow-2xl">
                            <h1 class="text-[10rem] font-black leading-none bg-gradient-to-b from-white to-white/70 bg-clip-text text-transparent">{{ $currentCalled->nomor_antrian }}</h1>
                            <p class="text-3xl font-bold mt-4 text-white/90">{{ $currentCalled->pasien?->nama_pasien ?? $currentCalled->nama_pasien_input_manual }}</p>
                            @if($currentCalled->kode_poli)
                            <p class="text-lg mt-2 text-white/60">Poli: {{ $currentCalled->kode_poli }}</p>
                            @endif
                        </div>
                    </div>
                    @else
                    <div class="text-center">
                        <i class="ri-time-line text-8xl text-white/20 mb-4 block"></i>
                        <p class="text-2xl text-white/40 font-semibold">Menunggu Panggilan...</p>
                    </div>
                    @endif
                </div>

                <!-- Waiting List -->
                <div class="col-span-2 flex flex-col">
                    <div class="bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 overflow-hidden flex-1 flex flex-col">
                        <div class="px-6 py-4 bg-white/5 border-b border-white/10">
                            <h2 class="text-lg font-bold tracking-wider uppercase"><i class="ri-list-ordered mr-2"></i>Antrian Menunggu</h2>
                        </div>
                        <div class="flex-1 overflow-y-auto p-4 space-y-2">
                            @forelse($waitingList as $item)
                            <div class="flex items-center gap-4 p-3 rounded-xl bg-white/5 hover:bg-white/10 transition-all border border-white/5">
                                <div class="flex items-center justify-center h-12 w-12 rounded-xl bg-white/10 text-xl font-black">{{ $item->nomor_antrian }}</div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-sm truncate">{{ $item->pasien?->nama_pasien ?? $item->nama_pasien_input_manual }}</p>
                                    @if($item->kode_poli)<p class="text-xs text-white/50">{{ $item->kode_poli }}</p>@endif
                                </div>
                                <span class="px-3 py-1 rounded-full text-[10px] font-bold bg-yellow-500/20 text-yellow-300 uppercase">Menunggu</span>
                            </div>
                            @empty
                            <div class="flex-1 flex items-center justify-center text-white/30 py-12">
                                <div class="text-center"><i class="ri-inbox-line text-4xl mb-2 block"></i><p>Tidak ada antrian</p></div>
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <!-- Running Text -->
            <div class="bg-[#405189]/50 border-t border-b border-white/10 text-white overflow-hidden py-3 flex relative whitespace-nowrap overflow-x-hidden">
                <div class="animate-marquee inline-block text-xl font-bold tracking-widest text-[#f7b84b]">
                    *** {{ $runningText }} ***
                </div>
            </div>

            <!-- Footer -->
            <div class="px-8 py-3 bg-black/20 text-center">
                <p class="text-xs text-white/40">SIGI Dental EMR © {{ date('Y') }} — Sistem Antrian Digital</p>
            </div>

            <style>
                @keyframes pulse-slow { 0%, 100% { opacity: 1; } 50% { opacity: 0.85; } }
                .animate-pulse-slow { animation: pulse-slow 3s ease-in-out infinite; }
                
                @keyframes marquee {
                    0% { transform: translateX(100vw); }
                    100% { transform: translateX(-100%); }
                }
                .animate-marquee { animation: marquee 25s linear infinite; }
            </style>
        </div>
        HTML;
    }

    public function layout()
    {
        return 'components.layouts.blank';
    }
}
