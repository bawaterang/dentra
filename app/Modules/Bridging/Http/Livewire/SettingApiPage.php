<?php

namespace App\Modules\Bridging\Http\Livewire;

use Livewire\Component;
use App\Models\MstSettingBpjs;
use App\Models\MstSettingSatusehat;

class SettingApiPage extends Component
{
    // BPJS
    public $bpjs_consid;
    public $bpjs_secret_key;
    public $bpjs_username;
    public $bpjs_password;
    public $bpjs_kd_aplikasi;
    public $bpjs_user_key;
    public $bpjs_base_url;

    // SATUSEHAT
    public $ss_client_id;
    public $ss_client_secret;
    public $ss_organization_id;
    public $ss_practitioner_id;
    public $ss_location_id;
    public $ss_organization_name;
    public $ss_url;
    public $ss_token_url;
    public $ss_mode_bridging = 'klinik';
    public $doctorCredentials = [];
    public $dokterList = [];

    public function mount()
    {
        $bpjs = MstSettingBpjs::first();
        if ($bpjs) {
            $this->bpjs_consid = $bpjs->consid;
            $this->bpjs_secret_key = $bpjs->secret_key;
            $this->bpjs_username = $bpjs->username;
            $this->bpjs_password = $bpjs->password;
            $this->bpjs_kd_aplikasi = $bpjs->kd_aplikasi;
            $this->bpjs_user_key = $bpjs->user_key;
            $this->bpjs_base_url = $bpjs->base_url;
        }

        $ss = MstSettingSatusehat::first();
        if ($ss) {
            $this->ss_client_id = $ss->client_id;
            $this->ss_client_secret = $ss->client_secret;
            $this->ss_organization_id = $ss->organization_id;
            $this->ss_practitioner_id = $ss->practitioner_id;
            $this->ss_location_id = $ss->location_id;
            $this->ss_organization_name = $ss->organization_name;
            $this->ss_url = $ss->url;
            $this->ss_token_url = $ss->token_url;
            $this->ss_mode_bridging = $ss->mode_bridging ?? 'klinik';
            $this->doctorCredentials = $ss->doctor_credentials ?? [];
        }

        $this->dokterList = \App\Models\MstDokter::where('status', 'Aktif')->get();
        
        // Inisialisasi kredensial kosong untuk dokter baru yang belum ada di DB
        foreach ($this->dokterList as $dokter) {
            if (!isset($this->doctorCredentials[$dokter->id])) {
                $this->doctorCredentials[$dokter->id] = [
                    'client_id' => '',
                    'client_secret' => ''
                ];
            }
        }
    }

    protected $message_rules = [
        'required' => ':attribute tidak boleh kosong.',
    ];

    public function saveBpjs()
    {
        $this->validate([
            'bpjs_consid' => 'required',
            'bpjs_secret_key' => 'required',
            'bpjs_username' => 'required',
            'bpjs_password' => 'required',
            'bpjs_kd_aplikasi' => 'required',
            'bpjs_user_key' => 'required',
            'bpjs_base_url' => 'required',
        ], $this->message_rules, [
            'bpjs_consid' => 'ConsID',
            'bpjs_secret_key' => 'Secret Key',
            'bpjs_username' => 'Username',
            'bpjs_password' => 'Password',
            'bpjs_kd_aplikasi' => 'Kode Aplikasi',
            'bpjs_user_key' => 'User Key',
            'bpjs_base_url' => 'Base URL',
        ]);

        $bpjs = MstSettingBpjs::first() ?: new MstSettingBpjs();
        $bpjs->fill([
            'consid' => $this->bpjs_consid,
            'secret_key' => $this->bpjs_secret_key,
            'username' => $this->bpjs_username,
            'password' => $this->bpjs_password,
            'kd_aplikasi' => $this->bpjs_kd_aplikasi,
            'user_key' => $this->bpjs_user_key,
            'base_url' => $this->bpjs_base_url,
        ]);
        $bpjs->save();

        $this->dispatch('alert', ['type' => 'success', 'message' => 'Pengaturan BPJS berhasil disimpan!']);
    }

    public function saveSatuSehat()
    {
        $this->validate([
            'ss_client_id' => 'required',
            'ss_client_secret' => 'required',
            'ss_organization_id' => 'required',
            'ss_practitioner_id' => 'required',
            'ss_location_id' => 'required',
            'ss_organization_name' => 'required',
            'ss_url' => 'required',
            'ss_token_url' => 'required',
            'ss_mode_bridging' => 'required',
        ], $this->message_rules, [
            'ss_client_id' => 'Client ID',
            'ss_client_secret' => 'Client Secret',
            'ss_organization_id' => 'Organization ID',
            'ss_practitioner_id' => 'Practitioner ID',
            'ss_location_id' => 'Location ID',
            'ss_organization_name' => 'Organization Name',
            'ss_url' => 'FHIR URL',
            'ss_token_url' => 'Token URL',
            'ss_mode_bridging' => 'Mode Bridging',
        ]);

        $ss = MstSettingSatusehat::first() ?: new MstSettingSatusehat();
        $ss->fill([
            'client_id' => $this->ss_client_id,
            'client_secret' => $this->ss_client_secret,
            'organization_id' => $this->ss_organization_id,
            'practitioner_id' => $this->ss_practitioner_id,
            'location_id' => $this->ss_location_id,
            'organization_name' => $this->ss_organization_name,
            'url' => $this->ss_url,
            'token_url' => $this->ss_token_url,
            'mode_bridging' => $this->ss_mode_bridging,
            'doctor_credentials' => $this->doctorCredentials,
        ]);
        $ss->save();

        $this->dispatch('alert', ['type' => 'success', 'message' => 'Pengaturan SATUSEHAT berhasil disimpan!']);
    }

    public function testConnection()
    {
        try {
            // Kita simpan dulu sebelum test pencabutan token
            $this->saveSatuSehat();
            
            $service = new \App\Modules\Bridging\Services\SatuSehatService();
            $service->clearToken(); // Pastikan kita request baru
            $token = $service->getToken();

            if ($token) {
                $this->dispatch('alert', [
                    'type' => 'success', 
                    'message' => 'Koneksi SATUSEHAT Berhasil! Token aktif didapatkan.'
                ]);
            } else {
                $this->dispatch('alert', [
                    'type' => 'error', 
                    'message' => 'Gagal mendapatkan token. Periksa Client ID & Secret.'
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatch('alert', [
                'type' => 'error', 
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }




    public function render()
    {
        return view('bridging::livewire.setting-api-page');
    }
}
