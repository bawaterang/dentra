<?php

namespace App\Modules\Bridging\Http\Livewire;

use Livewire\Component;
use App\Models\MstSettingBpjs;
use App\Modules\Bridging\Services\BpjsPcareService;
use App\Models\MstSettingSatusehat;

use App\Traits\HasAccessControl;

class SettingApiPage extends Component
{
    use HasAccessControl;

    // BPJS
    public $bpjs_consid;
    public $bpjs_secret_key;
    public $bpjs_username;
    public $bpjs_password;
    public $bpjs_kd_aplikasi;
    public $bpjs_user_key;
    public $bpjs_base_url_pcare;
    public $bpjs_base_url_antrian;
    public $bpjs_bridging = false;

    // SATUSEHAT
    public $ss_client_id;
    public $ss_client_secret;
    public $ss_url;
    public $ss_token_url;
    public $ss_mode_bridging = 'klinik';
    public $doctorCredentials = [];
    public $dokterList = [];

    public function mount()
    {
        $this->authorizeAccess('/bridging/setting-api');

        $bpjs = MstSettingBpjs::first();
        if ($bpjs) {
            $this->bpjs_consid = $bpjs->consid;
            $this->bpjs_secret_key = $bpjs->secret_key;
            $this->bpjs_username = $bpjs->username;
            $this->bpjs_password = $bpjs->password;
            $this->bpjs_kd_aplikasi = $bpjs->kd_aplikasi;
            $this->bpjs_user_key = $bpjs->user_key;
            $this->bpjs_base_url_pcare = $bpjs->base_url_pcare;
            $this->bpjs_base_url_antrian = $bpjs->base_url_antrian;
            $this->bpjs_bridging = ($bpjs->bridging === 'ON');
        }

        $ss = MstSettingSatusehat::first();
        if ($ss) {
            $this->ss_client_id = $ss->client_id;
            $this->ss_client_secret = $ss->client_secret;
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
            'bpjs_base_url_pcare' => 'required',
            'bpjs_base_url_antrian' => 'nullable',
        ], $this->message_rules, [
            'bpjs_consid' => 'ConsID',
            'bpjs_secret_key' => 'Secret Key',
            'bpjs_username' => 'Username',
            'bpjs_password' => 'Password',
            'bpjs_kd_aplikasi' => 'Kode Aplikasi',
            'bpjs_user_key' => 'User Key',
            'bpjs_base_url_pcare' => 'Base URL PCARE',
            'bpjs_base_url_antrian' => 'Base URL Antrian',
        ]);

        $bpjs = MstSettingBpjs::first() ?: new MstSettingBpjs();
        $bpjs->fill([
            'consid' => $this->bpjs_consid,
            'secret_key' => $this->bpjs_secret_key,
            'username' => $this->bpjs_username,
            'password' => $this->bpjs_password,
            'kd_aplikasi' => $this->bpjs_kd_aplikasi,
            'user_key' => $this->bpjs_user_key,
            'base_url_pcare' => $this->bpjs_base_url_pcare,
            'base_url_antrian' => $this->bpjs_base_url_antrian,
            'bridging' => $this->bpjs_bridging ? 'ON' : 'OFF',
        ]);
        $bpjs->save();

        $this->dispatch('alert', ['type' => 'success', 'message' => 'Pengaturan BPJS berhasil disimpan!']);
    }

    public function saveSatuSehat()
    {
        $this->validate([
            'ss_client_id' => 'required',
            'ss_client_secret' => 'required',
            'ss_url' => 'required',
            'ss_token_url' => 'required',
            'ss_mode_bridging' => 'required',
        ], $this->message_rules, [
            'ss_client_id' => 'Client ID',
            'ss_client_secret' => 'Client Secret',
            'ss_url' => 'FHIR URL',
            'ss_token_url' => 'Token URL',
            'ss_mode_bridging' => 'Mode Bridging',
        ]);

        $ss = MstSettingSatusehat::first() ?: new MstSettingSatusehat();
        $ss->fill([
            'client_id' => $this->ss_client_id,
            'client_secret' => $this->ss_client_secret,
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

    public function testBpjsConnection()
    {
        try {
            // Simpan dulu sebelum test
            $this->saveBpjs();

            $service = new BpjsPcareService();

            if (!$service->isConfigured()) {
                $this->dispatch('alert', [
                    'type' => 'error',
                    'message' => 'Konfigurasi BPJS belum lengkap. Pastikan ConsID, Secret Key, dan Base URL PCare sudah diisi.',
                ]);
                return;
            }

            // Test dengan endpoint dokter (paling sederhana)
            $result = $service->getDokter(0, 1);

            if ($result['success']) {
                $this->dispatch('alert', [
                    'type' => 'success',
                    'message' => 'Koneksi PCare BPJS Berhasil! Response: ' . ($result['metadata']['message'] ?? 'OK'),
                ]);
            } else {
                $this->dispatch('alert', [
                    'type' => 'error',
                    'message' => 'Gagal koneksi PCare: [' . ($result['metadata']['code'] ?? '?') . '] ' . ($result['metadata']['message'] ?? 'Unknown'),
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    public function syncKesadaran()
    {
        try {
            $service = new BpjsPcareService();
            $result = $service->getKesadaran();

            if ($result['success'] && isset($result['data']['list'])) {
                foreach ($result['data']['list'] as $item) {
                    \App\Models\MstKesadaran::updateOrCreate(
                        ['kdSadar' => $item['kdSadar']],
                        ['nmSadar' => $item['nmSadar']]
                    );
                }
                $this->dispatch('alert', ['type' => 'success', 'message' => 'Sinkronisasi Kesadaran Berhasil!']);
            } else {
                $this->dispatch('alert', ['type' => 'error', 'message' => 'Gagal sinkronisasi Kesadaran: ' . ($result['metadata']['message'] ?? 'Unknown error')]);
            }
        } catch (\Exception $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function syncAlergi()
    {
        try {
            $service = new BpjsPcareService();
            $jenisAlergi = ['01' => 'Makanan', '02' => 'Udara', '03' => 'Obat'];
            $successCount = 0;

            foreach ($jenisAlergi as $kdJenis => $nmJenis) {
                $result = $service->getAlergi($kdJenis);
                if ($result['success'] && isset($result['data']['list'])) {
                    foreach ($result['data']['list'] as $item) {
                        \App\Models\MstAlergi::updateOrCreate(
                            ['kdAlergi' => $item['kdAlergi']],
                            [
                                'nmAlergi' => $item['nmAlergi'],
                                'kdJenis' => $kdJenis
                            ]
                        );
                    }
                    $successCount++;
                }
            }

            if ($successCount > 0) {
                $this->dispatch('alert', ['type' => 'success', 'message' => 'Sinkronisasi Alergi Berhasil!']);
            } else {
                $this->dispatch('alert', ['type' => 'error', 'message' => 'Gagal sinkronisasi Alergi']);
            }
        } catch (\Exception $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('bridging::livewire.setting-api-page');
    }
}
