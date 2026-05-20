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

        return view('livewire.modules.pendaftaran.form-pendaftaran-page');
    }
}
