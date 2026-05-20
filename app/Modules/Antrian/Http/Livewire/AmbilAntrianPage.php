<?php

namespace App\Modules\Antrian\Http\Livewire;
 
use Livewire\Component;
use Carbon\Carbon;
use App\Models\TrxAntrian;
use App\Models\MstPasien;
use App\Models\MstPoli;
use App\Models\MstDokter;
use App\Models\MstAsuransi;
use App\Models\MstSettingAntrianHari;
use App\Models\MstSettingAntrianLibur;
use App\Models\MstSettingAntrian;
use App\Models\MstSettingAntrianDetail;

class AmbilAntrianPage extends Component
{
    public $nama_pasien, $no_telepon, $nik;
    public $kode_poli, $kode_dokter, $asuransi, $no_asuransi;
    public $tanggal_antrian, $jenis_antrian = 'offline';
    public $time_slot;
    public $reservasi_id;
    public $mode_antrian = 'Nomor Urut';
    public $format_nomor_antrian = '[nomor]';
    public $availableTimeSlots = [];

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
        $setting = MstSettingAntrian::first();
        if ($setting) {
            $this->mode_antrian = $setting->mode_antrian;
            $this->format_nomor_antrian = $setting->format_nomor_antrian ?? '[nomor]';
        }

        if (request()->has('reservasi_id')) {
            $this->reservasi_id = request()->reservasi_id;
            $reservasi = \App\Models\TrxReservasi::with(['pasien', 'poli', 'dokter'])->find($this->reservasi_id);
            if ($reservasi) {
                if ($reservasi->pasien) {
                    $this->nama_pasien = $reservasi->pasien->nama_pasien;
                    $this->no_telepon = $reservasi->pasien->no_telepon;
                    $this->nik = $reservasi->pasien->nik;
                    $this->no_asuransi = $reservasi->pasien->no_penjamin;
                } else {
                    $this->nama_pasien = $reservasi->nama_pasien_manual;
                    $this->no_telepon = $reservasi->no_telepon_manual;
                    $this->nik = $reservasi->nik_manual;
                }
                
                $this->kode_poli = $reservasi->poli?->kode_poli;
                
                // We need to fetch dokters based on poli directly if needed, or rely on livewire rendering.
                // The view relies on kode_poli to fetch dokters. So setting kode_dokter will work if options match.
                $this->kode_dokter = $reservasi->dokter?->kode_dokter;
                
                $this->tanggal_antrian = $reservasi->tanggal_reservasi;
                $this->jenis_antrian = 'online'; // reservation means online/booking
                
                // time_slot is stored in TrxReservasi
                if ($reservasi->time_slot) {
                    $this->time_slot = substr($reservasi->time_slot, 0, 5) . ':00';
                }
            }
        }

        $this->loadAvailableSlots();
    }

    public function updatedTanggalAntrian()
    {
        $this->loadAvailableSlots();
    }

    public function updatedKodePoli()
    {
        $this->kode_dokter = null;
        $this->time_slot = null;
        $this->loadAvailableSlots();
    }

    public function updatedKodeDokter()
    {
        $this->time_slot = null;
        $this->loadAvailableSlots();
    }

    public function loadAvailableSlots()
    {
        if ($this->mode_antrian === 'Nomor Urut' || empty($this->tanggal_antrian)) {
            $this->availableTimeSlots = [];
            return;
        }

        $hariMap = ['Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu'];
        $hariNama = $hariMap[Carbon::parse($this->tanggal_antrian)->format('l')];
        
        // Filter booked slots by Poli and Dokter on the specific date
        $query = TrxAntrian::whereDate('tanggal_antrian', $this->tanggal_antrian)
            ->where('status', '!=', 'batal')
            ->whereNotNull('time_slot');

        if ($this->kode_poli) {
            $query->where('kode_poli', $this->kode_poli);
        }
        if ($this->kode_dokter) {
            $query->where('kode_dokter', $this->kode_dokter);
        }

        $bookedSlotsShort = $query->pluck('time_slot')
            ->map(function($t) { return substr($t, 0, 5); })
            ->toArray();

        $this->availableTimeSlots = MstSettingAntrianDetail::where('hari', $hariNama)
            ->orderBy('waktu')
            ->get()
            ->filter(function($slot) use ($bookedSlotsShort) {
                return !in_array(substr($slot->waktu, 0, 5), $bookedSlotsShort);
            })
            ->map(function($slot) {
                return [
                    'value' => substr($slot->waktu, 0, 5) . ':00', // standardize back to sql TIME length
                    'label' => substr($slot->waktu, 0, 5) . ' (' . $slot->nomor_urut . ')',
                    'icon' => 'ri-time-line text-green-500'
                ];
            })->values()->toArray();
            
        // Reset time_slot if the previous selection is newly booked or invalid
        if ($this->time_slot && !in_array(substr($this->time_slot, 0, 5).':00', array_column($this->availableTimeSlots, 'value'))) {
            $this->time_slot = null;
        }
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

            // Holiday Validation
            $checkDate = Carbon::parse($this->tanggal_antrian);
            $hariMap = ['Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu'];
            $hariNama = $hariMap[$checkDate->format('l')];

            // Check Specific Date Range
            $liburKhusus = MstSettingAntrianLibur::where('tanggal_mulai', '<=', $checkDate->format('Y-m-d'))
                ->where('tanggal_selesai', '>=', $checkDate->format('Y-m-d'))
                ->first();
            
            if ($liburKhusus) {
                $this->dispatch('alert', [
                    'type' => 'warning',
                    'message' => 'Maaf, klinik sedang libur pada tanggal tersebut. Keterangan: ' . ($liburKhusus->keterangan ?? 'Libur Nasional')
                ]);
                return;
            }

            // Check Weekly Holiday
            $settingHari = MstSettingAntrianHari::where('hari', $hariNama)->first();
            if ($settingHari && $settingHari->is_holiday) {
                $this->dispatch('alert', [
                    'type' => 'warning',
                    'message' => "Maaf, klinik tidak beroperasi (Libur) pada setiap hari $hariNama."
                ]);
                return;
            }

            // Auto-sinkronisasi pasien
            $pasienId = null;
            if ($this->nik) {
                $pasien = MstPasien::where(fn($q) => $q->where('nik', $this->nik))->first();
                if ($pasien) { $pasienId = $pasien->id; }
            }
            if (!$pasienId && $this->nama_pasien) {
                $pasien = MstPasien::where(fn($q) => $q->where('nama_pasien', $this->nama_pasien))
                    ->when($this->no_telepon, fn($q) => $q->where(fn($q2) => $q2->where('no_telepon', $this->no_telepon)))
                    ->first();
                if ($pasien) { $pasienId = $pasien->id; }
            }

            // Duplicate Validation
            $duplicateCheck = TrxAntrian::query()
                ->where(fn($q) => $q->where('tanggal_antrian', $this->tanggal_antrian))
                ->where(fn($q) => $q->where('asuransi', $this->asuransi))
                ->where(fn($q) => $q->where('status', '!=', 'batal'));
                
            if ($pasienId) {
                $duplicateCheck->where(fn($q) => $q->where('pasien_id', $pasienId));
            } else {
                $duplicateCheck->where(fn($q) => $q->where('nama_pasien_input_manual', $this->nama_pasien));
            }

            if ($duplicateCheck->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'general' => 'Pasien ini sudah memiliki antrian dengan asuransi yang sama pada tanggal tersebut.'
                ]);
            }

            // Generate nomor antrian
            if ($this->mode_antrian !== 'Nomor Urut') {
                if (!$this->time_slot) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'time_slot' => 'Silakan pilih Slot Waktu.'
                    ]);
                }
                
                $isBooked = TrxAntrian::whereDate('tanggal_antrian', $this->tanggal_antrian)
                    ->where('time_slot', 'like', substr($this->time_slot, 0, 5) . '%')
                    ->where('kode_poli', $this->kode_poli)
                    ->where('kode_dokter', $this->kode_dokter)
                    ->where('status', '!=', 'batal')
                    ->exists();
                    
                if ($isBooked) {
                    $this->loadAvailableSlots();
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'time_slot' => 'Slot waktu ini baru saja diambil. Silakan pilih slot lain.'
                    ]);
                }

                $slotDetail = MstSettingAntrianDetail::where('hari', $hariNama)->where('waktu', 'like', substr($this->time_slot, 0, 5).'%')->first();
                if (!$slotDetail) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'time_slot' => 'Slot waktu tidak valid.'
                    ]);
                }
                
                $nomorAntrian = $slotDetail->nomor_urut;

                // Apply poli prefix to slot number
                $poliRecord = MstPoli::where('kode_poli', $this->kode_poli)->first();
                $poliPrefix = $poliRecord?->prefix_antrian;
                if ($poliPrefix) {
                    // Replace existing letter prefix (e.g. "A-001" → "B-001")
                    $nomorAntrian = preg_replace('/^[a-zA-Z]+/', $poliPrefix, $nomorAntrian);
                    // If no letter prefix existed, prepend it (e.g. "001" → "B-001")
                    if (!preg_match('/^[a-zA-Z]/', $nomorAntrian)) {
                        $nomorAntrian = $poliPrefix . '-' . $nomorAntrian;
                    }
                }
            } else {
                $countToday = TrxAntrian::whereDate('tanggal_antrian', $this->tanggal_antrian)
                    ->where('kode_poli', $this->kode_poli)
                    ->where('kode_dokter', $this->kode_dokter)
                    ->count();
                $nextSequence = $countToday + 1;
                
                $base = 0;
                $len = 3;
                $prefix = '';
                if (preg_match('/(.*?)([0-9]+)$/', $this->format_nomor_antrian, $matches)) {
                    $prefix = $matches[1];
                    $suffixTpl = $matches[2];
                    $len = strlen($suffixTpl);
                    $base = intval($suffixTpl);
                }
                
                // Prefix from Poli Master Data
                $poliRecord = MstPoli::where('kode_poli', $this->kode_poli)->first();
                $poliPrefix = $poliRecord?->prefix_antrian;

                if ($poliPrefix) {
                    $dynamicPrefix = $poliPrefix . '-';
                } elseif (preg_match('/[a-zA-Z]/', $prefix)) {
                    $dynamicPrefix = $prefix;
                } else {
                    $dynamicPrefix = $prefix;
                }
                
                $nomorString = str_pad($base + $nextSequence, $len, '0', STR_PAD_LEFT);
                $nomorAntrian = $dynamicPrefix . $nomorString;
            }

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

            if ($this->reservasi_id) {
                $reservasiRecord = \App\Models\TrxReservasi::find($this->reservasi_id);
                if ($reservasiRecord && $reservasiRecord->status === 'aktif') {
                    $reservasiRecord->update(['status' => 'hadir']);
                }
            }

            $this->generatedAntrian = $antrian;
            $this->dispatch('alert', ['type' => 'success', 'message' => 'Antrian berhasil diambil! Nomor: ' . $nomorAntrian]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => 'Ambil Antrian Gagal! Silakan periksa kembali isian form Anda.'
            ]);
            throw $e;
        } catch (\Exception $e) {
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => 'Gagal mengambil antrian: ' . $e->getMessage()
            ]);
        }
    }

    public function ambilLagi()
    {
        $this->resetForm();
        $this->mount();
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
        
        $docQuery = MstDokter::where('status', 'Aktif');
        if ($this->kode_poli) {
            $poli = MstPoli::with('dokters')->where('kode_poli', $this->kode_poli)->first();
            if ($poli) {
                $mappedIds = $poli->dokters->pluck('id')->toArray();
                $docQuery->whereIn('id', $mappedIds);
            } else {
                $docQuery->whereRaw('1=0');
            }
        }
        $this->dokterList = $docQuery->get()->map(fn($d) => ['value' => $d->kode_dokter, 'label' => $d->nama_dokter, 'icon' => 'ri-user-star-line text-purple-500'])->toArray();
        $this->asuransiList = MstAsuransi::where('status', 'Aktif')->get()->map(fn($a) => ['value' => $a->nama_asuransi, 'label' => $a->nama_asuransi, 'icon' => 'ri-shield-check-line text-green-500'])->toArray();

        return view('livewire.modules.antrian.ambil-antrian-page');
    }
}
