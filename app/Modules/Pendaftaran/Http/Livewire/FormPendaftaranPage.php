<?php

namespace App\Modules\Pendaftaran\Http\Livewire;

use Livewire\Component;
use App\Models\TrxPendaftaran;
use App\Models\TrxAntrian;
use App\Models\MstPasien;
use App\Models\MstPoli;
use App\Models\MstDokter;
use App\Models\MstAsuransi;

class FormPendaftaranPage extends Component
{
    // Mode
    public $modePasien = 'lama'; // lama or baru

    // From antrian
    public $antrian_id, $pasien_id;

    // Pasien lama search
    public $searchPasien = '';
    public $pasienResults = [];
    public $selectedPasien = null;

    // Pasien baru fields
    public $nama_pasien, $jenis_kelamin, $tempat_lahir, $tanggal_lahir, $alamat;
    public $no_telepon, $agama, $pekerjaan, $nik, $golongan_darah;

    // Pendaftaran fields
    public $poli_id, $dokter_id, $asuransi_id, $no_kartu_asuransi;

    // Data medis awal
    public $kesadaran = 'Compos Mentis', $tekanan_darah, $nadi, $suhu, $berat_badan, $tinggi_badan;
    public $riwayat_penyakit, $alergi, $keterangan_lain;

    // Dropdown data
    public $poliList = [];
    public $dokterList = [];
    public $asuransiList = [];
    public $kesadaranList = [];
    public $jkList = [];
    public $agamaList = [];
    public $golDarahList = [];

    public function mount()
    {
        $this->antrian_id = request()->query('antrian_id');
        $this->pasien_id = request()->query('pasien_id');

        if ($this->pasien_id) {
            $pasien = MstPasien::find($this->pasien_id);
            if ($pasien) {
                $this->selectedPasien = $pasien->toArray();
                $this->alergi = $pasien->alergi;
            }
        }

        if ($this->antrian_id) {
            $antrian = TrxAntrian::find($this->antrian_id);
            if ($antrian) {
                // Pre-fill from antrian data
                if ($antrian->kode_poli) {
                    $poli = MstPoli::where('kode_poli', $antrian->kode_poli)->first();
                    if ($poli) $this->poli_id = $poli->id;
                }
                if ($antrian->kode_dokter) {
                    $dokter = MstDokter::where('kode_dokter', $antrian->kode_dokter)->first();
                    if ($dokter) $this->dokter_id = $dokter->id;
                }
                if ($antrian->asuransi) {
                    $asuransi = MstAsuransi::where('nama_asuransi', $antrian->asuransi)->first();
                    if ($asuransi) $this->asuransi_id = $asuransi->id;
                }
                $this->no_kartu_asuransi = $antrian->no_asuransi;
            }
        }
    }

    public function updatedSearchPasien()
    {
        if (strlen($this->searchPasien) >= 2) {
            $this->pasienResults = MstPasien::where('status', 'Aktif')
                ->where(function ($q) {
                    $q->where('nama_pasien', 'like', '%' . $this->searchPasien . '%')
                      ->orWhere('nik', 'like', '%' . $this->searchPasien . '%')
                      ->orWhere('no_rm', 'like', '%' . $this->searchPasien . '%')
                      ->orWhere('no_telepon', 'like', '%' . $this->searchPasien . '%');
                })
                ->limit(10)
                ->get()
                ->toArray();
        } else {
            $this->pasienResults = [];
        }
    }

    public function pilihPasienLama($id)
    {
        $pasien = MstPasien::findOrFail($id);
        $this->selectedPasien = $pasien->toArray();
        $this->pasien_id = $pasien->id;
        $this->alergi = $pasien->alergi;
        $this->searchPasien = '';
        $this->pasienResults = [];
    }

    public function resetPasien()
    {
        $this->selectedPasien = null;
        $this->pasien_id = null;
        $this->searchPasien = '';
    }

    public function save()
    {
        try {
            // Validasi
            $rules = [
                'poli_id' => 'required|exists:mst_poli,id',
                'dokter_id' => 'required|exists:mst_dokter,id',
            ];

            if ($this->modePasien === 'baru') {
                $rules['nama_pasien'] = 'required|string|max:100';
                $rules['jenis_kelamin'] = 'required|in:Laki-laki,Perempuan';
            } else {
                $rules['pasien_id'] = 'required|exists:mst_pasien,id';
            }

            $this->validate($rules);

            // Duplicate Validation
            $duplicateCheck = \App\Models\TrxPendaftaran::whereDate('created_at', now()->format('Y-m-d'))
                ->where('asuransi_id', $this->asuransi_id)
                ->where(function($q) {
                    if ($this->modePasien === 'lama' && $this->pasien_id) {
                        $q->where('pasien_id', $this->pasien_id);
                    } else {
                        $q->whereHas('pasien', function($sq) {
                            if ($this->nik) {
                                $sq->where('nik', $this->nik);
                            } else {
                                $sq->where('nama_pasien', $this->nama_pasien);
                            }
                        });
                    }
                })
                ->exists();

            if ($duplicateCheck) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'general' => 'Pasien ini sudah terdaftar dengan asuransi yang sama pada hari ini.'
                ]);
            }

            // Simpan pasien baru jika perlu
            if ($this->modePasien === 'baru') {
                $lastRm = MstPasien::withTrashed()->orderBy('id', 'desc')->first();
                $nextRm = 1;
                if ($lastRm && $lastRm->no_rm) {
                    $num = (int) substr($lastRm->no_rm, 2);
                    $nextRm = $num + 1;
                }
                $noRm = 'RM' . str_pad($nextRm, 6, '0', STR_PAD_LEFT);

                $newPasien = MstPasien::create([
                    'no_rm' => $noRm,
                    'nama_pasien' => $this->nama_pasien,
                    'jenis_kelamin' => $this->jenis_kelamin,
                    'tempat_lahir' => $this->tempat_lahir,
                    'tanggal_lahir' => $this->tanggal_lahir,
                    'alamat' => $this->alamat,
                    'no_telepon' => $this->no_telepon,
                    'agama' => $this->agama,
                    'pekerjaan' => $this->pekerjaan,
                    'nik' => $this->nik,
                    'golongan_darah' => $this->golongan_darah,
                    'alergi' => $this->alergi,
                    'status' => 'Aktif',
                ]);
                $this->pasien_id = $newPasien->id;
            }

            // Generate nomor kunjungan
            $nomorKunjungan = TrxPendaftaran::generateNomorKunjungan();

            $pendaftaran = TrxPendaftaran::create([
                'nomor_kunjungan' => $nomorKunjungan,
                'antrian_id' => $this->antrian_id,
                'pasien_id' => $this->pasien_id,
                'poli_id' => $this->poli_id,
                'dokter_id' => $this->dokter_id,
                'asuransi_id' => $this->asuransi_id,
                'no_kartu_asuransi' => $this->no_kartu_asuransi,
                'kesadaran' => $this->kesadaran,
                'tekanan_darah' => $this->tekanan_darah,
                'nadi' => $this->nadi,
                'suhu' => $this->suhu,
                'berat_badan' => $this->berat_badan,
                'tinggi_badan' => $this->tinggi_badan,
                'riwayat_penyakit' => $this->riwayat_penyakit,
                'alergi' => $this->alergi,
                'keterangan_lain' => $this->keterangan_lain,
                'status' => 'terdaftar',
            ]);

            // Update alergi ke master pasien
            if ($this->alergi && $this->pasien_id) {
                MstPasien::where('id', $this->pasien_id)->update(['alergi' => $this->alergi]);
            }

            // Update status antrian
            if ($this->antrian_id) {
                TrxAntrian::where('id', $this->antrian_id)->update(['status' => 'selesai']);
            }

            $this->dispatch('alert', ['type' => 'success', 'message' => 'Pendaftaran berhasil! No Kunjungan: ' . $nomorKunjungan]);
            return redirect()->route('pendaftaran.index');

        } catch (\Illuminate\Validation\ValidationException $e) { throw $e;
        } catch (\Exception $e) { $this->dispatch('alert', ['type' => 'error', 'message' => 'Gagal: ' . $e->getMessage()]); }
    }

    public function cekBpjs()
    {
        // Placeholder for BPJS API integration
        $this->dispatch('alert', ['type' => 'info', 'message' => 'Fitur integrasi API BPJS akan tersedia pada tahap berikutnya.']);
    }

    public function render()
    {
        $this->poliList = MstPoli::where('status', 'Aktif')->get()->map(fn($p) => ['value' => $p->id, 'label' => $p->nama_poli, 'icon' => 'ri-hospital-line text-blue-500'])->toArray();
        $this->dokterList = MstDokter::where('status', 'Aktif')->get()->map(fn($d) => ['value' => $d->id, 'label' => $d->nama_dokter, 'icon' => 'ri-user-star-line text-purple-500'])->toArray();
        $this->asuransiList = MstAsuransi::where('status', 'Aktif')->get()->map(fn($a) => ['value' => $a->id, 'label' => $a->nama_asuransi, 'icon' => 'ri-shield-check-line text-green-500'])->toArray();
        $this->kesadaranList = [
            ['value' => 'Compos Mentis', 'label' => 'Compos Mentis', 'icon' => 'ri-checkbox-circle-line text-green-500'],
            ['value' => 'Somnolence', 'label' => 'Somnolence', 'icon' => 'ri-eye-close-line text-yellow-500'],
            ['value' => 'Sopor', 'label' => 'Sopor', 'icon' => 'ri-eye-close-line text-orange-500'],
            ['value' => 'Coma', 'label' => 'Coma', 'icon' => 'ri-close-circle-line text-red-500'],
        ];
        $this->jkList = [
            ['value' => 'Laki-laki', 'label' => 'Laki-laki', 'icon' => 'ri-men-line text-blue-500'],
            ['value' => 'Perempuan', 'label' => 'Perempuan', 'icon' => 'ri-women-line text-pink-500'],
        ];
        $this->agamaList = [
            ['value' => 'Islam', 'label' => 'Islam', 'icon' => 'ri-star-line text-green-600'],
            ['value' => 'Kristen', 'label' => 'Kristen', 'icon' => 'ri-star-line text-blue-600'],
            ['value' => 'Katolik', 'label' => 'Katolik', 'icon' => 'ri-star-line text-indigo-600'],
            ['value' => 'Hindu', 'label' => 'Hindu', 'icon' => 'ri-star-line text-orange-600'],
            ['value' => 'Buddha', 'label' => 'Buddha', 'icon' => 'ri-star-line text-yellow-600'],
            ['value' => 'Konghucu', 'label' => 'Konghucu', 'icon' => 'ri-star-line text-red-600'],
        ];
        $this->golDarahList = [
            ['value' => 'A', 'label' => 'A', 'icon' => 'ri-drop-line text-red-500'],
            ['value' => 'B', 'label' => 'B', 'icon' => 'ri-drop-line text-blue-500'],
            ['value' => 'AB', 'label' => 'AB', 'icon' => 'ri-drop-line text-purple-500'],
            ['value' => 'O', 'label' => 'O', 'icon' => 'ri-drop-line text-green-500'],
        ];

        return <<<'HTML'
        <div>
            <div class="page-header"><div class="page-header-title"><div class="page-header-icon"><i class="ri-file-add-line"></i></div><h1>Form Pendaftaran</h1></div><div class="page-header-breadcrumb"><a href="/dashboard" wire:navigate><i class="ri-home-line"></i></a><span class="sep">/</span><a href="{{ route('pendaftaran.index') }}" wire:navigate>Pendaftaran</a><span class="sep">/</span><span>Buat Baru</span></div></div>

            <div class="max-w-4xl mx-auto">
                <form wire:submit.prevent="save">
                @error('general') <div class="bg-red-500/10 border border-red-500/50 text-red-500 px-4 py-3 rounded-xl mb-4 text-sm font-semibold"><i class="ri-alert-line mr-2"></i>{{ $message }}</div> @enderror
                <!-- Mode Toggle -->
                <div class="card overflow-hidden border-t-2 border-[#405189] mb-6">
                    <div class="p-5">
                        <div class="flex items-center gap-3 mb-4">
                            <button type="button" wire:click="$set('modePasien','lama')" class="flex-1 p-4 rounded-xl border-2 transition-all {{ $modePasien === 'lama' ? 'border-[#405189] bg-[#405189]/5' : 'border-gray-200 hover:border-gray-300' }}">
                                <div class="flex items-center gap-3"><div class="h-10 w-10 rounded-lg flex items-center justify-center {{ $modePasien === 'lama' ? 'bg-[#405189] text-white' : 'bg-gray-100 text-gray-400' }}"><i class="ri-user-search-line text-lg"></i></div><div class="text-left"><p class="font-bold text-sm {{ $modePasien === 'lama' ? 'text-[#405189]' : 'text-gray-500' }}">Pasien Lama</p><p class="text-[11px] text-[#878a99]">Cari dari data master pasien</p></div></div>
                            </button>
                            <button type="button" wire:click="$set('modePasien','baru')" class="flex-1 p-4 rounded-xl border-2 transition-all {{ $modePasien === 'baru' ? 'border-[#0ab39c] bg-[#0ab39c]/5' : 'border-gray-200 hover:border-gray-300' }}">
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
                        <div class="relative"><input type="text" wire:model.live.debounce.300ms="searchPasien" class="w-full rounded-lg border-gray-200 text-sm pl-10 pr-4 h-[42px] focus:border-[#405189] transition-all" placeholder="Cari pasien berdasarkan Nama, NIK, No RM, atau No HP..."><i class="ri-search-line absolute left-3.5 top-1/2 -translate-y-1/2 text-[#878a99]"></i></div>
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
                                <div><label class="block text-xs font-semibold text-gray-500 mb-1">Nama Pasien <span class="text-red-500">*</span></label><input type="text" wire:model="nama_pasien" class="w-full rounded-lg border-gray-200 text-sm px-4 h-[42px] focus:border-[#405189] transition-all" placeholder="Nama lengkap">@error('nama_pasien')<span class="text-[11px] text-red-500 italic">{{ $message }}</span>@enderror</div>
                                <div><label class="block text-xs font-semibold text-gray-500 mb-1">NIK</label><input type="text" wire:model="nik" class="w-full rounded-lg border-gray-200 text-sm px-4 h-[42px] focus:border-[#405189] transition-all" placeholder="Nomor Induk Kependudukan"></div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div><label class="block text-xs font-semibold text-gray-500 mb-1">Jenis Kelamin <span class="text-red-500">*</span></label><x-custom-dropdown model="jenis_kelamin" :options="$jkList" placeholder="Pilih JK" /></div>
                                <div><label class="block text-xs font-semibold text-gray-500 mb-1">Tempat Lahir</label><input type="text" wire:model="tempat_lahir" class="w-full rounded-lg border-gray-200 text-sm px-4 h-[42px] focus:border-[#405189] transition-all"></div>
                                <div><label class="block text-xs font-semibold text-gray-500 mb-1">Tanggal Lahir</label><input type="date" wire:model="tanggal_lahir" class="w-full rounded-lg border-gray-200 text-sm px-4 h-[42px] focus:border-[#405189] transition-all"></div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div><label class="block text-xs font-semibold text-gray-500 mb-1">Agama</label><x-custom-dropdown model="agama" :options="$agamaList" placeholder="Pilih Agama" /></div>
                                <div><label class="block text-xs font-semibold text-gray-500 mb-1">Gol. Darah</label><x-custom-dropdown model="golongan_darah" :options="$golDarahList" placeholder="Pilih" /></div>
                                <div><label class="block text-xs font-semibold text-gray-500 mb-1">No Telepon</label><input type="text" wire:model="no_telepon" class="w-full rounded-lg border-gray-200 text-sm px-4 h-[42px] focus:border-[#405189] transition-all"></div>
                            </div>
                            <div><label class="block text-xs font-semibold text-gray-500 mb-1">Alamat</label><textarea wire:model="alamat" rows="2" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#405189] transition-all"></textarea></div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Informasi Kunjungan -->
                <div class="card overflow-hidden border-t-2 border-[#0ab39c] mb-6">
                    <div class="px-6 py-4 border-b border-gray-100 bg-[#f3f6f9]/50"><h6 class="text-sm font-bold text-[#0ab39c]"><i class="ri-hospital-line mr-2"></i>Informasi Kunjungan</h6></div>
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div><label class="block text-xs font-semibold text-gray-500 mb-1">Poli <span class="text-red-500">*</span></label><x-custom-dropdown model="poli_id" :options="$poliList" placeholder="Pilih Poli" searchable="true" />@error('poli_id')<span class="text-[11px] text-red-500 italic">{{ $message }}</span>@enderror</div>
                            <div><label class="block text-xs font-semibold text-gray-500 mb-1">Dokter <span class="text-red-500">*</span></label><x-custom-dropdown model="dokter_id" :options="$dokterList" placeholder="Pilih Dokter" searchable="true" />@error('dokter_id')<span class="text-[11px] text-red-500 italic">{{ $message }}</span>@enderror</div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div><label class="block text-xs font-semibold text-gray-500 mb-1">Asuransi</label><x-custom-dropdown model="asuransi_id" :options="$asuransiList" placeholder="Umum (tanpa asuransi)" searchable="true" /></div>
                            <div class="flex gap-2 items-end">
                                <div class="flex-1"><label class="block text-xs font-semibold text-gray-500 mb-1">No Kartu Asuransi</label><input type="text" wire:model="no_kartu_asuransi" class="w-full rounded-lg border-gray-200 text-sm px-4 h-[42px] focus:border-[#405189] transition-all" placeholder="Nomor kartu"></div>
                                <button type="button" wire:click="cekBpjs" class="h-[42px] px-4 rounded-lg bg-[#0ab39c] text-white text-xs font-bold hover:bg-[#099885] transition-all whitespace-nowrap flex items-center gap-1"><i class="ri-search-eye-line"></i> Cek BPJS</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Data Medis Awal -->
                <div class="card overflow-hidden border-t-2 border-[#f7b84b] mb-6">
                    <div class="px-6 py-4 border-b border-gray-100 bg-[#f3f6f9]/50"><h6 class="text-sm font-bold text-[#f7b84b]"><i class="ri-heart-pulse-line mr-2"></i>Data Medis Awal</h6></div>
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            <div><label class="block text-xs font-semibold text-gray-500 mb-1">Kesadaran</label><x-custom-dropdown model="kesadaran" :options="$kesadaranList" placeholder="Pilih" /></div>
                            <div><label class="block text-xs font-semibold text-gray-500 mb-1">Tekanan Darah</label><input type="text" wire:model="tekanan_darah" class="w-full rounded-lg border-gray-200 text-sm px-4 h-[42px] focus:border-[#405189] transition-all" placeholder="120/80 mmHg"></div>
                            <div><label class="block text-xs font-semibold text-gray-500 mb-1">Nadi</label><input type="number" wire:model="nadi" class="w-full rounded-lg border-gray-200 text-sm px-4 h-[42px] focus:border-[#405189] transition-all" placeholder="x/menit"></div>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            <div><label class="block text-xs font-semibold text-gray-500 mb-1">Suhu (°C)</label><input type="number" step="0.1" wire:model="suhu" class="w-full rounded-lg border-gray-200 text-sm px-4 h-[42px] focus:border-[#405189] transition-all" placeholder="36.5"></div>
                            <div><label class="block text-xs font-semibold text-gray-500 mb-1">Berat Badan (kg)</label><input type="number" step="0.1" wire:model="berat_badan" class="w-full rounded-lg border-gray-200 text-sm px-4 h-[42px] focus:border-[#405189] transition-all" placeholder="60"></div>
                            <div><label class="block text-xs font-semibold text-gray-500 mb-1">Tinggi Badan (cm)</label><input type="number" step="0.1" wire:model="tinggi_badan" class="w-full rounded-lg border-gray-200 text-sm px-4 h-[42px] focus:border-[#405189] transition-all" placeholder="170"></div>
                        </div>
                        <div><label class="block text-xs font-semibold text-gray-500 mb-1">Riwayat Penyakit</label><textarea wire:model="riwayat_penyakit" rows="2" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#405189] transition-all" placeholder="Riwayat penyakit sebelumnya..."></textarea></div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div><label class="block text-xs font-semibold text-gray-500 mb-1">Alergi</label><textarea wire:model="alergi" rows="2" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#405189] transition-all" placeholder="Alergi obat/bahan..."></textarea></div>
                            <div><label class="block text-xs font-semibold text-gray-500 mb-1">Keterangan Lain</label><textarea wire:model="keterangan_lain" rows="2" class="w-full rounded-lg border-gray-200 text-sm px-4 py-2.5 focus:border-[#405189] transition-all" placeholder="Catatan tambahan..."></textarea></div>
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
        </div>
        HTML;
    }
}
