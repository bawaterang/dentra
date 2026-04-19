<?php

namespace App\Modules\Screening\Http\Livewire;

use Livewire\Component;
use App\Models\TrxPendaftaran;
use App\Models\TrxScreening;
use App\Models\MstSurvei;

class FormScreeningPage extends Component
{
    public $pendaftaranId;
    public $pendaftaran;
    public $pertanyaanList = [];
    public $jawaban = [];
    public $keterangan = [];
    public $isCompleted = false;

    public function mount($pendaftaranId)
    {
        $this->pendaftaranId = $pendaftaranId;
        $this->pendaftaran = TrxPendaftaran::with(['pasien', 'poli', 'dokter'])->findOrFail($pendaftaranId);
        $this->pertanyaanList = MstSurvei::where('status', 'Aktif')->where('jenis_survei', 'screening')->get();

        // Check if already screened
        $existing = TrxScreening::where('pendaftaran_id', $pendaftaranId)->get();
        if ($existing->count() > 0) {
            $this->isCompleted = true;
            foreach ($existing as $scr) {
                $this->jawaban[$scr->survei_id] = $scr->jawaban;
                $this->keterangan[$scr->survei_id] = $scr->keterangan;
            }
        } else {
            foreach ($this->pertanyaanList as $p) {
                $this->jawaban[$p->id] = 'tidak';
                $this->keterangan[$p->id] = '';
            }
        }
    }

    public function save()
    {
        try {
            foreach ($this->pertanyaanList as $p) {
                TrxScreening::updateOrCreate(
                    ['pendaftaran_id' => $this->pendaftaranId, 'survei_id' => $p->id],
                    [
                        'pasien_id' => $this->pendaftaran->pasien_id,
                        'jawaban' => $this->jawaban[$p->id] ?? 'tidak',
                        'keterangan' => $this->keterangan[$p->id] ?? null,
                    ]
                );
            }

            // Update pendaftaran status
            $this->pendaftaran->update(['status' => 'selesai']);
            $this->isCompleted = true;

            $this->dispatch('alert', ['type' => 'success', 'message' => 'Screening berhasil disimpan!']);
        } catch (\Exception $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Gagal menyimpan: ' . $e->getMessage()]);
        }
    }

    public function render()
    {
        return <<<'HTML'
        <div>
            <div class="page-header">
                <div class="page-header-title">
                    <div class="page-header-icon"><i class="ri-shield-check-line"></i></div>
                    <h1>Form Screening</h1>
                </div>
                <div class="page-header-breadcrumb">
                    <a href="/dashboard" wire:navigate class="hover:text-[#405189] transition-colors"><i class="ri-home-4-line"></i></a>
                    <span class="sep text-gray-300">/</span>
                    <a href="{{ route('screening.index') }}" wire:navigate class="text-gray-400 font-medium hover:text-[#405189] transition-colors">Screening</a>
                    <span class="sep text-gray-300">/</span>
                    <span class="text-[#405189] font-bold">Form</span>
                </div>
            </div>

            <div class="max-w-4xl mx-auto">
                <!-- Patient Info Card -->
                <div class="card overflow-hidden border-t-2 border-[#405189] mb-6">
                    <div class="p-5">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <div class="h-14 w-14 rounded-full bg-gradient-to-br from-[#405189] to-[#3577f1] text-white flex items-center justify-center font-bold text-xl">{{ strtoupper(substr($pendaftaran->pasien->nama_pasien ?? 'P', 0, 1)) }}</div>
                                <div>
                                    <h4 class="font-bold text-lg text-[#495057]">{{ $pendaftaran->pasien->nama_pasien ?? '-' }}</h4>
                                    <div class="flex flex-wrap gap-3 text-xs text-[#878a99]">
                                        <span><i class="ri-hashtag mr-1"></i>{{ $pendaftaran->pasien->no_rm }}</span>
                                        <span><i class="ri-hospital-line mr-1"></i>{{ $pendaftaran->poli->nama_poli ?? '-' }}</span>
                                        <span><i class="ri-user-star-line mr-1"></i>{{ $pendaftaran->dokter->nama_dokter ?? '-' }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="font-mono font-bold text-[#405189] text-sm">{{ $pendaftaran->nomor_kunjungan }}</span>
                                @if($isCompleted)<span class="badge bg-success-subtle ml-2"><i class="ri-checkbox-circle-line mr-1"></i>Screening Selesai</span>@endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Screening Form -->
                <div class="card overflow-hidden border-t-2 border-[#0ab39c] mb-6">
                    <div class="px-6 py-4 border-b border-gray-100 bg-[#f3f6f9]/50">
                        <h6 class="text-sm font-bold text-[#0ab39c]"><i class="ri-questionnaire-line mr-2"></i>Pertanyaan Screening</h6>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            @foreach($pertanyaanList as $index => $p)
                            <div class="p-4 rounded-xl border border-gray-100 hover:border-[#405189]/20 transition-all {{ isset($jawaban[$p->id]) && $jawaban[$p->id] === 'ya' ? 'bg-red-50 border-red-200' : 'bg-white' }}">
                                <div class="flex items-start gap-4">
                                    <span class="flex-shrink-0 h-7 w-7 rounded-lg bg-[#405189] text-white flex items-center justify-center text-xs font-bold mt-0.5">{{ $index + 1 }}</span>
                                    <div class="flex-1">
                                        <p class="font-medium text-[#495057] text-sm mb-3">{{ $p->pertanyaan }}</p>
                                        <div class="flex items-center gap-6">
                                            <label class="flex items-center gap-2 cursor-pointer group">
                                                <input type="radio" wire:model="jawaban.{{ $p->id }}" value="ya" {{ $isCompleted ? 'disabled' : '' }} class="w-4 h-4 text-red-500 border-gray-300 focus:ring-red-400">
                                                <span class="text-sm font-semibold {{ isset($jawaban[$p->id]) && $jawaban[$p->id] === 'ya' ? 'text-red-600' : 'text-gray-500' }} group-hover:text-red-500">Ya</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer group">
                                                <input type="radio" wire:model="jawaban.{{ $p->id }}" value="tidak" {{ $isCompleted ? 'disabled' : '' }} class="w-4 h-4 text-green-500 border-gray-300 focus:ring-green-400">
                                                <span class="text-sm font-semibold {{ isset($jawaban[$p->id]) && $jawaban[$p->id] === 'tidak' ? 'text-green-600' : 'text-gray-500' }} group-hover:text-green-500">Tidak</span>
                                            </label>
                                        </div>
                                        <div class="mt-4">
                                            <input type="text" wire:model="keterangan.{{ $p->id }}" {{ $isCompleted ? 'disabled' : '' }} class="w-full rounded-lg border-gray-200 text-sm px-4 h-11 focus:border-[#405189] focus:ring focus:ring-[#405189]/20 transition-all bg-gray-50/50" placeholder="Tambahkan keterangan rincian (opsional)...">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-between gap-3 mb-8">
                    <a href="{{ route('screening.index') }}" wire:navigate class="btn bg-orange-500 text-white px-6 h-10 flex items-center gap-2 transition-all hover:bg-orange-600"><i class="ri-arrow-go-back-line"></i> Kembali</a>
                    <div class="flex gap-3">
                        @if($isCompleted)
                        <a href="{{ route('screening.print', $pendaftaranId) }}" target="_blank" class="btn bg-[#405189] text-white px-6 h-10 flex items-center gap-2 hover:bg-[#364574] transition-all"><i class="ri-printer-line"></i> Cetak Hasil</a>
                        @else
                        <button type="button" wire:click="save" wire:loading.attr="disabled" class="btn bg-[#0d6efd] text-white px-8 h-10 shadow-md flex items-center justify-center gap-2 transition-all hover:bg-[#0b5ed7] hover:translate-y-[-2px] disabled:opacity-70"><svg wire:loading wire:target="save" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><i wire:loading.remove wire:target="save" class="ri-save-line"></i><span wire:loading.remove wire:target="save">Simpan Screening</span><span wire:loading wire:target="save">Memproses...</span></button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        HTML;
    }
}
