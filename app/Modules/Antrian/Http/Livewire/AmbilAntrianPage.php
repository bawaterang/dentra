<?php

namespace App\Modules\Antrian\Http\Livewire;

use Livewire\Component;
use App\Models\TrxAntrian;
use App\Models\MstPasien;
use App\Models\MstPoli;
use App\Models\MstDokter;
use App\Models\MstAsuransi;

class AmbilAntrianPage extends Component
{
    public $nama_pasien, $no_telepon, $nik;
    public $kode_poli, $kode_dokter, $asuransi, $no_asuransi;
    public $tanggal_antrian, $jenis_antrian = 'offline';
    public $time_slot;

    // Search properties
    public $searchPasien = '';
    public $pasienResults = [];
    public $showSearchModal = false;

    // Dropdown data
    public $poliList = [];
    public $dokterList = [];
    public $asuransiList = [];

    // Generated ticket
    public $generatedAntrian = null;

    public function mount()
    {
        $this->tanggal_antrian = now()->format('Y-m-d');
    }

    protected function rules()
    {
        return [
            'nama_pasien' => 'required|string|max:100',
            'tanggal_antrian' => 'required|date',
            'jenis_antrian' => 'required|in:online,offline',
        ];
    }

    public function resetForm()
    {
        $this->reset(['nama_pasien', 'no_telepon', 'nik', 'kode_poli', 'kode_dokter', 'asuransi', 'no_asuransi', 'time_slot', 'generatedAntrian']);
        $this->tanggal_antrian = now()->format('Y-m-d');
        $this->jenis_antrian = 'offline';
        $this->resetErrorBag();
    }

    public function save()
    {
        try {
            $this->validate($this->rules());

            // Auto-sinkronisasi pasien
            $pasienId = null;
            if ($this->nik) {
                $pasien = MstPasien::where('nik', $this->nik)->first();
                if ($pasien) { $pasienId = $pasien->id; }
            }
            if (!$pasienId && $this->nama_pasien) {
                $pasien = MstPasien::where('nama_pasien', $this->nama_pasien)
                    ->when($this->no_telepon, fn($q) => $q->where('no_telepon', $this->no_telepon))
                    ->first();
                if ($pasien) { $pasienId = $pasien->id; }
            }

            // Duplicate Validation
            $duplicateCheck = clone TrxAntrian::query()
                ->where(['tanggal_antrian' => $this->tanggal_antrian])
                ->where(['asuransi' => $this->asuransi]);
                
            if ($pasienId) {
                $duplicateCheck->where('pasien_id', $pasienId);
            } else {
                $duplicateCheck->where('nama_pasien_input_manual', $this->nama_pasien);
            }

            if ($duplicateCheck->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'general' => 'Pasien ini sudah memiliki antrian dengan asuransi yang sama pada tanggal tersebut.'
                ]);
            }

            // Generate nomor antrian
            $lastAntrian = TrxAntrian::where('tanggal_antrian', $this->tanggal_antrian)
                ->orderBy('nomor_antrian', 'desc')
                ->first();
            $nextNumber = $lastAntrian ? ((int)$lastAntrian->nomor_antrian + 1) : 1;
            $nomorAntrian = str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

            $antrian = TrxAntrian::create([
                'nomor_antrian' => $nomorAntrian,
                'tanggal_antrian' => $this->tanggal_antrian,
                'jenis_antrian' => $this->jenis_antrian,
                'pasien_id' => $pasienId,
                'nama_pasien_input_manual' => $this->nama_pasien,
                'no_telepon_manual' => $this->no_telepon,
                'nik_manual' => $this->nik,
                'kode_dokter' => $this->kode_dokter,
                'kode_poli' => $this->kode_poli,
                'asuransi' => $this->asuransi,
                'no_asuransi' => $this->no_asuransi,
                'time_slot' => $this->time_slot,
                'status' => 'menunggu',
            ]);

            $this->generatedAntrian = $antrian;
            $this->dispatch('alert', ['type' => 'success', 'message' => 'Antrian berhasil diambil! Nomor: ' . $nomorAntrian]);

        } catch (\Illuminate\Validation\ValidationException $e) { throw $e;
        } catch (\Exception $e) { $this->dispatch('alert', ['type' => 'error', 'message' => 'Gagal: ' . $e->getMessage()]); }
    }

    public function ambilLagi()
    {
        $this->resetForm();
    }

    public function updatedSearchPasien($value)
    {
        if (strlen($value) >= 2) {
            $this->pasienResults = MstPasien::where(function ($q) use ($value) {
                    $q->where('nama_pasien', 'like', '%' . $value . '%')
                      ->orWhere('nik', 'like', '%' . $value . '%')
                      ->orWhere('no_telepon', 'like', '%' . $value . '%')
                      ->orWhere('no_rm', 'like', '%' . $value . '%');
                })
                ->limit(10)
                ->get()
                ->toArray();
        } else {
            $this->pasienResults = [];
        }
    }

    public function pilihPasien($pasienId)
    {
        $pasien = MstPasien::findOrFail($pasienId);
        $this->nama_pasien = $pasien->nama_pasien;
        $this->no_telepon = $pasien->no_telepon;
        $this->nik = $pasien->nik;
        $this->no_asuransi = $pasien->no_penjamin;
        $this->showSearchModal = false;
        $this->dispatch('alert', ['type' => 'info', 'message' => 'Pasien terpilih: ' . $pasien->nama_pasien]);
    }

    public function render()
    {
        $this->poliList = MstPoli::where('status', 'Aktif')->get()->map(fn($p) => ['value' => $p->kode_poli, 'label' => $p->nama_poli, 'icon' => 'ri-hospital-line text-blue-500'])->toArray();
        $this->dokterList = MstDokter::where('status', 'Aktif')->get()->map(fn($d) => ['value' => $d->kode_dokter, 'label' => $d->nama_dokter, 'icon' => 'ri-user-star-line text-purple-500'])->toArray();
        $this->asuransiList = MstAsuransi::where('status', 'Aktif')->get()->map(fn($a) => ['value' => $a->nama_asuransi, 'label' => $a->nama_asuransi, 'icon' => 'ri-shield-check-line text-green-500'])->toArray();

        return <<<'HTML'
        <div x-data="{ showSearchModal: @entangle('showSearchModal') }">
            <div class="page-header"><div class="page-header-title"><div class="page-header-icon"><i class="ri-ticket-line"></i></div><h1>Ambil Antrian</h1></div><div class="page-header-breadcrumb"><a href="/dashboard" wire:navigate><i class="ri-home-line"></i></a><span class="sep">/</span><a href="{{ route('antrian.index') }}" wire:navigate>Antrian</a><span class="sep">/</span><span>Ambil Antrian</span></div></div>

            @if($generatedAntrian)
            <!-- Print Style -->
            <style>
                @media print {
                    @page {
                        margin: 0;
                    }
                    body * {
                        visibility: hidden;
                    }
                    #printArea, #printArea * {
                        visibility: visible;
                        color: black !important;
                    }
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
            <!-- Ticket Result -->
            <div class="max-w-md mx-auto">
                <!-- Screen UI (Hidden on Print) -->
                <div class="card shadow-xl border-2 border-[#405189] overflow-hidden print:hidden" id="screenTicket">
                    <div class="bg-gradient-to-br from-[#405189] to-[#3577f1] p-6 text-center text-white">
                        <p class="text-xs font-semibold uppercase tracking-widest opacity-80 mb-2">SIGI Dental Clinic</p>
                        <p class="text-sm opacity-70">Nomor Antrian</p>
                        <h1 class="text-6xl font-black my-3">{{ $generatedAntrian->nomor_antrian }}</h1>
                        <p class="text-sm opacity-80">{{ \Carbon\Carbon::parse($generatedAntrian->tanggal_antrian)->translatedFormat('l, d F Y') }}</p>
                    </div>
                    <div class="p-5 space-y-2 text-sm">
                        <div class="flex justify-between"><span class="text-[#878a99]">Nama</span><span class="font-semibold">{{ $generatedAntrian->nama_pasien_input_manual }}</span></div>
                        @if($generatedAntrian->kode_poli)<div class="flex justify-between"><span class="text-[#878a99]">Poli</span><span class="font-semibold">{{ $generatedAntrian->kode_poli }}</span></div>@endif
                        @if($generatedAntrian->kode_dokter)<div class="flex justify-between"><span class="text-[#878a99]">Dokter</span><span class="font-semibold">{{ $generatedAntrian->kode_dokter }}</span></div>@endif
                        <div class="flex justify-between"><span class="text-[#878a99]">Jenis</span><span class="badge {{ $generatedAntrian->jenis_antrian === 'online' ? 'bg-info-subtle' : 'bg-secondary-subtle' }}">{{ ucfirst($generatedAntrian->jenis_antrian) }}</span></div>
                        <div class="flex justify-between"><span class="text-[#878a99]">Status</span><span class="badge bg-warning-subtle">Menunggu</span></div>
                    </div>
                    <div class="border-t border-dashed border-gray-200 p-4 text-center text-[10px] text-[#878a99]">Simpan tiket ini. Harap menunggu giliran Anda dipanggil.</div>
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
                        @if($generatedAntrian->kode_dokter)<div class="flex justify-between my-1"><span>Dokter:</span><span class="font-bold text-right ml-2">{{ $generatedAntrian->kode_dokter }}</span></div>@endif
                        <div class="flex justify-between my-1"><span>Jenis:</span><span class="font-bold text-right ml-2">{{ ucfirst($generatedAntrian->jenis_antrian) }}</span></div>
                    </div>
                    
                    <div class="text-center text-[10px]">
                        Simpan tiket ini.<br>Harap menunggu giliran Anda.
                    </div>
                </div>

                <div class="flex gap-3 mt-4 print:hidden">
                    <button onclick="window.print()" class="btn bg-[#405189] text-white flex-1 h-10 flex items-center justify-center gap-2 hover:bg-[#364574] transition-all"><i class="ri-printer-line"></i> Cetak Tiket</button>
                    <button wire:click="ambilLagi" class="btn bg-[#0ab39c] text-white flex-1 h-10 flex items-center justify-center gap-2 hover:bg-[#099885] transition-all"><i class="ri-add-line"></i> Ambil Lagi</button>
                </div>
            </div>
            @else
            <!-- Form Ambil Antrian -->
            <div class="max-w-2xl mx-auto">
                <div class="card overflow-hidden border-t-2 border-[#405189]">
                    <div class="px-6 py-4 border-b border-gray-100 bg-[#f3f6f9]/50"><h5 class="text-lg font-bold text-[#495057]"><i class="ri-ticket-line mr-2 text-[#405189]"></i>Form Pengambilan Antrian</h5></div>
                    <div class="px-8 py-6">
                        <form wire:submit.prevent="save">
                            @error('general') <div class="bg-red-500/10 border border-red-500/50 text-red-500 px-4 py-3 rounded-xl mb-4 text-sm font-semibold"><i class="ri-alert-line mr-2"></i>{{ $message }}</div> @enderror
                            <div class="space-y-4">
                                <h6 class="text-xs font-bold text-[#405189] uppercase tracking-widest border-b pb-2">Data Pasien</h6>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">Nama Pasien <span class="text-red-500">*</span></label>
                                    <div class="flex gap-2">
                                        <input type="text" wire:model="nama_pasien" class="flex-1 rounded-lg border-gray-200 text-sm px-4 h-[42px] focus:border-[#405189] transition-all @error('nama_pasien') border-red-400 @enderror" placeholder="Nama lengkap pasien">
                                        <button type="button" @click="showSearchModal = true; $wire.set('searchPasien', ''); $wire.set('pasienResults', [])" class="btn bg-[#299cdb] text-white h-[42px] px-3 flex items-center gap-1 text-[10px] font-bold whitespace-nowrap"><i class="ri-search-2-line"></i> CARI PASIEN</button>
                                    </div>
                                    @error('nama_pasien') <span class="text-[11px] text-red-500 mt-1 italic">{{ $message }}</span> @enderror
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div><label class="block text-xs font-semibold text-gray-500 mb-1">No Telepon</label><input type="text" wire:model="no_telepon" class="w-full rounded-lg border-gray-200 text-sm px-4 h-[42px] focus:border-[#405189] transition-all" placeholder="08xxxxxxxxxx"></div>
                                    <div><label class="block text-xs font-semibold text-gray-500 mb-1">NIK</label><input type="text" wire:model="nik" class="w-full rounded-lg border-gray-200 text-sm px-4 h-[42px] focus:border-[#405189] transition-all" placeholder="Nomor Induk Kependudukan"></div>
                                </div>

                                <h6 class="text-xs font-bold text-[#0ab39c] uppercase tracking-widest border-b pb-2 !mt-6">Informasi Kunjungan</h6>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div><label class="block text-xs font-semibold text-gray-500 mb-1">Tanggal Antrian <span class="text-red-500">*</span></label><input type="date" wire:model="tanggal_antrian" class="w-full rounded-lg border-gray-200 text-sm px-4 h-[42px] focus:border-[#405189] transition-all"></div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">Jenis Antrian</label>
                                        <x-custom-dropdown model="jenis_antrian" :options="[
                                            ['value' => 'offline', 'label' => 'Offline (Datang Langsung)', 'icon' => 'ri-walk-line text-blue-500'],
                                            ['value' => 'online', 'label' => 'Online (Booking)', 'icon' => 'ri-global-line text-green-500']
                                        ]" placeholder="Pilih Jenis" />
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">Poli Tujuan</label>
                                        <x-custom-dropdown model="kode_poli" :options="$poliList" placeholder="Pilih Poli" searchable="true" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">Dokter</label>
                                        <x-custom-dropdown model="kode_dokter" :options="$dokterList" placeholder="Pilih Dokter" searchable="true" />
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">Asuransi</label>
                                        <x-custom-dropdown model="asuransi" :options="$asuransiList" placeholder="Pilih Asuransi (Opsional)" searchable="true" />
                                    </div>
                                    <div><label class="block text-xs font-semibold text-gray-500 mb-1">No Asuransi</label><input type="text" wire:model="no_asuransi" class="w-full rounded-lg border-gray-200 text-sm px-4 h-[42px] focus:border-[#405189] transition-all" placeholder="Nomor kartu asuransi"></div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="px-8 py-5 bg-gray-50/80 flex justify-between gap-3 border-t border-gray-100">
                        <a href="{{ route('antrian.index') }}" wire:navigate class="btn bg-orange-500 text-white px-6 h-10 flex items-center gap-2 transition-all hover:bg-orange-600"><i class="ri-arrow-go-back-line"></i> Kembali</a>
                        <button type="button" wire:click="save" wire:loading.attr="disabled" class="btn bg-[#0d6efd] text-white px-8 h-10 shadow-md flex items-center justify-center gap-2 transition-all hover:bg-[#0b5ed7] hover:translate-y-[-2px] disabled:opacity-70"><svg wire:loading wire:target="save" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><i wire:loading.remove wire:target="save" class="ri-ticket-line"></i><span wire:loading.remove wire:target="save">Ambil Antrian</span><span wire:loading wire:target="save">Memproses...</span></button>
                    </div>
                </div>
            </div>
            @endif

            <!-- Search Pasien Modal -->
            <div x-show="showSearchModal" class="fixed inset-0 z-[1050] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" x-transition.opacity style="display:none;">
                <div x-show="showSearchModal" x-transition.scale.95 class="w-full max-w-lg bg-white rounded-xl shadow-2xl overflow-hidden">
                    <div class="px-6 py-4 flex items-center justify-between border-b border-gray-100 bg-[#f3f6f9]/50"><h5 class="text-lg font-bold text-[#495057]"><i class="ri-search-2-line mr-2 text-[#405189]"></i>Cari Pasien</h5><button @click="showSearchModal=false" class="text-gray-400 hover:text-gray-600"><i class="ri-close-line text-2xl"></i></button></div>
                    <div class="px-6 py-5">
                        <div class="relative mb-4"><input type="text" wire:model.live.debounce.300ms="searchPasien" class="w-full rounded-lg border-gray-200 text-sm pl-10 pr-4 h-[42px] focus:border-[#405189] transition-all" placeholder="Cari berdasarkan Nama, NIK, No HP, atau No RM..."><i class="ri-search-line absolute left-3.5 top-1/2 -translate-y-1/2 text-[#878a99]"></i></div>
                        <div class="max-h-[300px] overflow-y-auto space-y-2">
                            @forelse($pasienResults as $p)
                            <button wire:key="psearch-{{ $p['id'] }}" wire:click="pilihPasien({{ $p['id'] }})" class="w-full text-left p-3 rounded-lg border border-gray-100 hover:border-[#405189] hover:bg-[#405189]/5 transition-all group">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-semibold text-[#495057] text-sm group-hover:text-[#405189]">{{ $p['nama_pasien'] }}</p>
                                        <p class="text-[11px] text-[#878a99]">{{ $p['no_rm'] }} · NIK: {{ $p['nik'] ?? '-' }} · {{ $p['no_telepon'] ?? '-' }}</p>
                                    </div>
                                    <i class="ri-arrow-right-s-line text-gray-300 group-hover:text-[#405189] text-xl"></i>
                                </div>
                            </button>
                            @empty
                            @if(strlen($searchPasien) >= 2)
                            <div class="text-center py-6 text-[#878a99]"><i class="ri-user-search-line text-3xl mb-2 block"></i><p class="text-sm">Tidak ada pasien ditemukan</p></div>
                            @else
                            <div class="text-center py-6 text-[#878a99]"><i class="ri-search-eye-line text-3xl mb-2 block"></i><p class="text-sm">Ketik minimal 2 karakter untuk mencari</p></div>
                            @endif
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
        HTML;
    }
}
