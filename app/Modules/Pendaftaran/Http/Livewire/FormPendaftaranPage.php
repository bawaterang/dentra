<?php

namespace App\Modules\Pendaftaran\Http\Livewire;

use Livewire\Component;
use App\Models\TrxPendaftaran;
use App\Models\TrxAntrian;
use App\Models\MstPasien;
use App\Models\MstPoli;
use App\Models\MstDokter;
use App\Models\MstAsuransi;
use App\Models\MstSettingAntrianHari;
use App\Models\MstSettingAntrianLibur;
use App\Models\MstSettingAntrian;
use App\Models\MstSettingAntrianDetail;
use App\Models\MstKesadaran;
use App\Models\MstAlergi;
use App\Traits\DynamicKodeGenerator;

class FormPendaftaranPage extends Component
{
    use DynamicKodeGenerator;
    // Mode
    public $modePasien = 'lama'; // lama or baru

    // From antrian
    public $antrian_id, $pasien_id;

    // Antrian specific fields
    public $tanggal_antrian, $jenis_antrian = 'offline';
    public $time_slot;
    public $mode_antrian = 'Nomor Urut';
    public $availableTimeSlots = [];

    // Pasien lama search
    public $searchPasien = '';
    public $pasienResults = [];
    public $selectedPasien = null;

    // Pasien baru fields (also used for editing lama)
    public $nama_pasien, $jenis_kelamin, $tempat_lahir, $tanggal_lahir, $alamat;
    public $no_telepon, $agama, $pekerjaan, $nik, $golongan_darah;
    public $showEditPasienModal = false;

    // Pendaftaran fields
    public $poli_id, $dokter_id, $asuransi_id, $no_kartu_asuransi;

    // Data medis awal
    public $kesadaran = '01', $tekanan_darah, $nadi, $suhu, $berat_badan, $tinggi_badan, $lingkar_perut;
    public $riwayat_penyakit, $kode_alergi, $alergi, $keterangan_lain;

    // Dropdown data
    public $poliList = [];
    public $dokterList = [];
    public $asuransiList = [];
    public $kesadaranList = [];
    public $alergiList = [];
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
                $this->kode_alergi = $pasien->kode_alergi;
                $this->alergi = $pasien->alergi;
            }
        } elseif ($this->antrian_id) {
            $this->modePasien = 'baru';
        }

        $setting = MstSettingAntrian::first();
        if ($setting) {
            $this->mode_antrian = $setting->mode_antrian;
        }

        if ($this->antrian_id) {
            $antrian = TrxAntrian::find($this->antrian_id);
            if ($antrian) {
                // Pre-fill from antrian data
                if ($antrian->kode_poli) {
                    $poli = MstPoli::where('kode_poli', $antrian->kode_poli)->first();
                    if ($poli)
                        $this->poli_id = $poli->id;
                }
                if ($antrian->kode_dokter) {
                    $dokter = MstDokter::where('kode_dokter', $antrian->kode_dokter)->first();
                    if ($dokter)
                        $this->dokter_id = $dokter->id;
                }
                if ($antrian->asuransi) {
                    $asuransi = MstAsuransi::where('nama_asuransi', $antrian->asuransi)->first();
                    if ($asuransi)
                        $this->asuransi_id = $asuransi->id;
                }
                $this->no_kartu_asuransi = $antrian->no_asuransi;
                $this->tanggal_antrian = $antrian->tanggal_antrian;
                $this->jenis_antrian = $antrian->jenis_antrian ?? 'offline';
                $this->time_slot = $antrian->time_slot ? substr($antrian->time_slot, 0, 5) . ':00' : null;

                if (!$this->pasien_id) {
                    $this->nama_pasien = $antrian->nama_pasien_input_manual;
                    $this->nik = $antrian->nik_manual;
                    $this->no_telepon = $antrian->no_telepon_manual;
                }
            }
        } else {
            $this->tanggal_antrian = now()->format('Y-m-d');
        }
        $this->loadAvailableSlots();
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
        $this->kode_alergi = $pasien->kode_alergi;
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

    public function updatedPoliId()
    {
        $this->dokter_id = null;
        $this->time_slot = null;
        $this->loadAvailableSlots();
    }

    public function updatedDokterId()
    {
        $this->time_slot = null;
        $this->loadAvailableSlots();
    }

    public function updatedTanggalAntrian()
    {
        $this->loadAvailableSlots();
    }

    public function loadAvailableSlots()
    {
        if ($this->mode_antrian === 'Nomor Urut' || empty($this->tanggal_antrian)) {
            $this->availableTimeSlots = [];
            return;
        }

        $hariMap = ['Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu'];
        $hariNama = $hariMap[\Carbon\Carbon::parse($this->tanggal_antrian)->format('l')];

        $query = TrxAntrian::whereDate('tanggal_antrian', $this->tanggal_antrian)
            ->where('status', '!=', 'batal')
            ->whereNotNull('time_slot');

        if ($this->antrian_id) {
            $query->where('id', '!=', $this->antrian_id);
        }

        if ($this->poli_id) {
            $poli = MstPoli::find($this->poli_id);
            if ($poli)
                $query->where('kode_poli', $poli->kode_poli);
        }
        if ($this->dokter_id) {
            $dokter = MstDokter::find($this->dokter_id);
            if ($dokter)
                $query->where('kode_dokter', $dokter->kode_dokter);
        }

        $bookedSlotsShort = $query->pluck('time_slot')
            ->map(function ($t) {
                return substr($t, 0, 5); })
            ->toArray();

        $this->availableTimeSlots = MstSettingAntrianDetail::where('hari', $hariNama)
            ->orderBy('waktu')
            ->get()
            ->filter(function ($slot) use ($bookedSlotsShort) {
                return !in_array(substr($slot->waktu, 0, 5), $bookedSlotsShort);
            })
            ->map(function ($slot) {
                return [
                    'value' => substr($slot->waktu, 0, 5) . ':00',
                    'label' => substr($slot->waktu, 0, 5) . ' (' . $slot->nomor_urut . ')',
                    'icon' => 'ri-time-line text-green-500'
                ];
            })->values()->toArray();

        if ($this->time_slot && !in_array(substr($this->time_slot, 0, 5) . ':00', array_column($this->availableTimeSlots, 'value'))) {
            $this->time_slot = null;
        }
    }

    public function editPasien()
    {
        if ($this->selectedPasien) {
            $this->nama_pasien = $this->selectedPasien['nama_pasien'] ?? '';
            $this->jenis_kelamin = $this->selectedPasien['jenis_kelamin'] ?? '';
            $this->tempat_lahir = $this->selectedPasien['tempat_lahir'] ?? '';
            $this->tanggal_lahir = $this->selectedPasien['tanggal_lahir'] ?? '';
            $this->alamat = $this->selectedPasien['alamat'] ?? '';
            $this->no_telepon = $this->selectedPasien['no_telepon'] ?? '';
            $this->agama = $this->selectedPasien['agama'] ?? '';
            $this->pekerjaan = $this->selectedPasien['pekerjaan'] ?? '';
            $this->nik = $this->selectedPasien['nik'] ?? '';
            $this->golongan_darah = $this->selectedPasien['golongan_darah'] ?? '';
            $this->showEditPasienModal = true;
        }
    }

    public function updatePasienLama()
    {
        $this->validate([
            'nama_pasien' => 'required|string|max:100',
            'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
        ]);

        $pasien = MstPasien::findOrFail($this->pasien_id);
        $pasien->update([
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
        ]);

        $this->selectedPasien = $pasien->fresh()->toArray();
        $this->showEditPasienModal = false;
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Data pasien berhasil diperbarui.']);
    }

    public function save()
    {
        try {
            $this->validateHoliday();
            $this->validateRegistration();
            $this->handlePasienData();
            $this->syncAntrianData();

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
                'lingkar_perut' => $this->lingkar_perut,
                'riwayat_penyakit' => $this->riwayat_penyakit,
                'kode_alergi' => $this->kode_alergi,
                'alergi' => $this->alergi,
                'keterangan_lain' => $this->keterangan_lain,
                'status' => 'terdaftar',
            ]);

            if ($this->pasien_id) {
                $updateData = [];
                if ($this->kode_alergi !== null) $updateData['kode_alergi'] = $this->kode_alergi;
                if ($this->alergi !== null) $updateData['alergi'] = $this->alergi;
                if (!empty($updateData)) MstPasien::where('id', $this->pasien_id)->update($updateData);
            }

            $this->dispatch('alert', [
                'type' => 'success',
                'message' => 'Pendaftaran berhasil! No Kunjungan: ' . $nomorKunjungan,
                'redirect' => route('pendaftaran.index')
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Gagal: ' . $e->getMessage()]);
        }
    }

    protected function validateHoliday()
    {
        $now = now();
        $hariMap = ['Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu'];
        $hariIni = $hariMap[$now->format('l')];

        $liburKhusus = MstSettingAntrianLibur::where('tanggal_mulai', '<=', $now->format('Y-m-d'))
            ->where('tanggal_selesai', '>=', $now->format('Y-m-d'))
            ->first();
        
        if ($liburKhusus) {
            throw \Illuminate\Validation\ValidationException::withMessages(['general' => 'Hari ini adalah Hari Libur (' . ($liburKhusus->keterangan ?? 'Nasional') . ').']);
        }

        $settingHari = MstSettingAntrianHari::where('hari', $hariIni)->first();
        if ($settingHari && $settingHari->is_holiday) {
            throw \Illuminate\Validation\ValidationException::withMessages(['general' => "Hari $hariIni klinik tidak beroperasi (Libur Mingguan)."]);
        }
    }

    protected function validateRegistration()
    {
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

        $duplicateCheck = TrxPendaftaran::whereDate('created_at', now()->format('Y-m-d'))
            ->where('asuransi_id', $this->asuransi_id)
            ->where('status', '!=', 'batal')
            ->where(function($q) {
                if ($this->modePasien === 'lama' && $this->pasien_id) {
                    $q->where('pasien_id', $this->pasien_id);
                } else {
                    $q->whereHas('pasien', function($sq) {
                        if ($this->nik) $sq->where('nik', $this->nik);
                        else $sq->where('nama_pasien', $this->nama_pasien);
                    });
                }
            })->exists();

        if ($duplicateCheck) {
            throw \Illuminate\Validation\ValidationException::withMessages(['general' => 'Pasien ini sudah terdaftar dengan asuransi yang sama pada hari ini.']);
        }
    }

    protected function handlePasienData()
    {
        if ($this->modePasien === 'baru') {
            $noRm = $this->generateDynamicKode('mst_pasien', 'no_rm') ?: 'P00001';
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
                'kode_alergi' => $this->kode_alergi,
                'alergi' => $this->alergi,
                'status' => 'Aktif',
            ]);
            $this->pasien_id = $newPasien->id;
        }
    }

    protected function syncAntrianData()
    {
        if (!$this->antrian_id) {
            $poli = MstPoli::find($this->poli_id);
            $dokter = MstDokter::find($this->dokter_id);
            $asuransiModel = MstAsuransi::find($this->asuransi_id);
            
            $hariMap = ['Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu'];
            $hariNama = $hariMap[\Carbon\Carbon::parse($this->tanggal_antrian)->format('l')];

            if ($this->mode_antrian !== 'Nomor Urut') {
                if (!$this->time_slot) throw \Illuminate\Validation\ValidationException::withMessages(['time_slot' => 'Silakan pilih Slot Waktu.']);
                $slotDetail = MstSettingAntrianDetail::where('hari', $hariNama)->where('waktu', 'like', substr($this->time_slot, 0, 5).'%')->first();
                $nomorAntrian = $slotDetail ? $slotDetail->nomor_urut : '001';

                // Apply poli prefix to slot number
                $poliPrefix = $poli?->prefix_antrian;
                if ($poliPrefix) {
                    $nomorAntrian = preg_replace('/^[a-zA-Z]+/', $poliPrefix, $nomorAntrian);
                    if (!preg_match('/^[a-zA-Z]/', $nomorAntrian)) {
                        $nomorAntrian = $poliPrefix . '-' . $nomorAntrian;
                    }
                }
            } else {
                $setting = MstSettingAntrian::first();
                $format = $setting ? ($setting->format_nomor_antrian ?? '[nomor]') : '[nomor]';
                $countToday = TrxAntrian::whereDate('tanggal_antrian', $this->tanggal_antrian)
                    ->where('kode_poli', $poli?->kode_poli)->where('kode_dokter', $dokter?->kode_dokter)->count();
                
                $prefix = ''; $len = 3; $base = 0;
                if (preg_match('/(.*?)([0-9]+)$/', $format, $matches)) {
                    $prefix = $matches[1]; $len = strlen($matches[2]); $base = intval($matches[2]);
                }

                // Prefix from Poli Master Data
                $poliPrefix = $poli?->prefix_antrian;
                if ($poliPrefix) {
                    $dynamicPrefix = $poliPrefix . '-';
                } else {
                    $dynamicPrefix = $prefix;
                }

                $nomorAntrian = $dynamicPrefix . str_pad($base + $countToday + 1, $len, '0', STR_PAD_LEFT);
            }

            $antrian = TrxAntrian::create([
                'nomor_antrian' => $nomorAntrian,
                'tanggal_antrian' => $this->tanggal_antrian,
                'jenis_antrian' => $this->jenis_antrian,
                'pasien_id' => $this->pasien_id,
                'nama_pasien_input_manual' => $this->modePasien === 'baru' ? $this->nama_pasien : null,
                'no_telepon_manual' => $this->modePasien === 'baru' ? $this->no_telepon : null,
                'nik_manual' => $this->modePasien === 'baru' ? $this->nik : null,
                'kode_dokter' => $dokter?->kode_dokter,
                'kode_poli' => $poli?->kode_poli,
                'asuransi' => $asuransiModel?->nama_asuransi,
                'no_asuransi' => $this->no_kartu_asuransi,
                'time_slot' => $this->time_slot,
                'status' => 'selesai',
            ]);
            $this->antrian_id = $antrian->id;
        } else {
            TrxAntrian::where('id', $this->antrian_id)->update([
                'tanggal_antrian' => $this->tanggal_antrian,
                'jenis_antrian' => $this->jenis_antrian,
                'time_slot' => $this->time_slot,
                'status' => 'selesai'
            ]);
        }
    }


    public function cekBpjs()
    {
        // Placeholder for BPJS API integration
        $this->dispatch('alert', ['type' => 'info', 'message' => 'Fitur integrasi API BPJS akan tersedia pada tahap berikutnya.']);
    }

    public function render()
    {
        $this->poliList = MstPoli::where('status', 'Aktif')->get()->map(fn($p) => ['value' => $p->id, 'label' => $p->nama_poli, 'icon' => 'ri-hospital-line text-blue-500'])->toArray();

        $docQuery = MstDokter::where('status', 'Aktif');
        if ($this->poli_id) {
            $poli = MstPoli::with('dokters')->find($this->poli_id);
            if ($poli) {
                $mappedIds = $poli->dokters->pluck('id')->toArray();
                $docQuery->whereIn('id', $mappedIds);
            } else {
                $docQuery->whereRaw('1=0');
            }
        }
        $this->dokterList = $docQuery->get()->map(fn($d) => ['value' => $d->id, 'label' => $d->nama_dokter, 'icon' => 'ri-user-star-line text-purple-500'])->toArray();
        $this->asuransiList = MstAsuransi::where('status', 'Aktif')->get()->map(fn($a) => ['value' => $a->id, 'label' => $a->nama_asuransi, 'icon' => 'ri-shield-check-line text-green-500'])->toArray();
        $this->kesadaranList = MstKesadaran::all()->map(fn($k) => ['value' => $k->kdSadar, 'label' => $k->nmSadar, 'icon' => 'ri-checkbox-circle-line text-green-500'])->toArray();
        $this->alergiList = MstAlergi::all()->map(fn($a) => ['value' => $a->kdAlergi, 'label' => $a->nmAlergi, 'icon' => 'ri-bug-line text-red-500'])->toArray();
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
        HTML;
    }
}
