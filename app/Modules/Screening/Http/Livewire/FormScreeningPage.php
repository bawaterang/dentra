<?php

namespace App\Modules\Screening\Http\Livewire;

use Livewire\Component;
use App\Models\TrxPendaftaran;
use App\Models\TrxScreening;
use App\Models\MstSurvei;

use App\Traits\HasAccessControl;

class FormScreeningPage extends Component
{
    use HasAccessControl;

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

        // Security Check: Basic access to screening module
        $this->authorizeAccess('/admisi/screening-pasien');

        // Security Check: Poli Isolation (Skip for Admin)
        $user = auth()->user();
        $userRoleNames = $user->roles->pluck('nama_role')->toArray();
        $isAdmin = in_array('Administrator', $userRoleNames) || in_array('Admin', $userRoleNames);

        if (!$isAdmin) {
            $userPoliIds = $user->polis->pluck('id')->toArray();
            if (!in_array($this->pendaftaran->poli_id, $userPoliIds)) {
                abort(403, 'Anda tidak memiliki akses ke data poli ini.');
            }
        }

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
        return view('livewire.modules.screening.form-screening-page');
    }
}
