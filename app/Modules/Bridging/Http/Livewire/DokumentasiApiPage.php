<?php

namespace App\Modules\Bridging\Http\Livewire;

use Livewire\Component;
use App\Modules\Bridging\Services\BpjsPcareService;

class DokumentasiApiPage extends Component
{
    // Selected endpoint
    public $selectedEndpoint = 'dokter';

    // Parameters
    public $paramStart = 0;
    public $paramLimit = 20;
    public $paramKeyword = '';
    public $paramNoKartu = '';
    public $paramNik = '';
    public $paramNoKunjungan = '';
    public $paramIsRawatInap = false;
    public $paramKdTkp = '10';
    public $paramTanggal = '';
    public $paramKodePoli = '';

    // Kunjungan body (single JSON textarea)
    public $kunjunganBodyJson = '';
    public $paramNoKunjunganBpjs = '';

    // Patient search for auto-fill
    public $searchPasienQuery = '';
    public $foundPasiens = [];

    // Response state
    public $responseData = [];
    public $responseColumns = [];
    public $totalData = 0;
    public $isLoading = false;
    public $errorMessage = '';
    public $successMessage = '';
    public $rawResponse = '';
    public $lastFetched = '';
    public $responseTime = 0;
    public $responseMetaCode;
    public $responseMetaMessage;

    /**
     * Definisi semua endpoint PCare BPJS yang tersedia
     */
    public function getEndpointDefinitionsProperty(): array
    {
        return [
            // === REFERENSI ===
            'dokter' => [
                'label' => 'Data Dokter',
                'category' => 'Referensi',
                'icon' => 'ri-stethoscope-line',
                'color' => '#0d6efd',
                'method' => 'GET',
                'endpoint' => '{Base URL}/dokter/{start}/{limit}',
                'description' => 'Mengambil data dokter yang terdaftar di faskes.',
                'params' => ['start', 'limit'],
                'response_fields' => ['kdDokter' => 'Kode Dokter', 'nmDokter' => 'Nama Dokter'],
            ],
            'poli' => [
                'label' => 'Data Poli',
                'category' => 'Referensi',
                'icon' => 'ri-hospital-line',
                'color' => '#0ab39c',
                'method' => 'GET',
                'endpoint' => '{Base URL}/poli/fktp/{start}/{limit}',
                'description' => 'Mengambil data poli yang tersedia di FKTP.',
                'params' => ['start', 'limit'],
                'response_fields' => ['kdPoli' => 'Kode Poli', 'nmPoli' => 'Nama Poli'],
            ],
            'diagnosa' => [
                'label' => 'Data Diagnosa (ICD-10)',
                'category' => 'Referensi',
                'icon' => 'ri-heart-pulse-line',
                'color' => '#f06548',
                'method' => 'GET',
                'endpoint' => '{Base URL}/diagnosa/{keyword}/{start}/{limit}',
                'description' => 'Mencari data diagnosa berdasarkan kode ICD-10 atau nama penyakit dengan paginasi.',
                'params' => ['keyword', 'start', 'limit'],
                'response_fields' => ['kdDiag' => 'Kode Diagnosa', 'nmDiag' => 'Nama Diagnosa'],
            ],
            'kesadaran' => [
                'label' => 'Data Kesadaran',
                'category' => 'Referensi',
                'icon' => 'ri-psychotherapy-line',
                'color' => '#405189',
                'method' => 'GET',
                'endpoint' => '{Base URL}/kesadaran',
                'description' => 'Mengambil data referensi tingkat kesadaran pasien.',
                'params' => [],
                'response_fields' => ['kdSadar' => 'Kode Kesadaran', 'nmSadar' => 'Nama Kesadaran'],
            ],
            'spesialis' => [
                'label' => 'Data Spesialis',
                'category' => 'Referensi',
                'icon' => 'ri-medal-line',
                'color' => '#ebab0c',
                'method' => 'GET',
                'endpoint' => '{Base URL}/spesialis',
                'description' => 'Mengambil data referensi spesialis/sub-spesialis.',
                'params' => [],
                'response_fields' => ['kdSpesialis' => 'Kode Spesialis', 'nmSpesialis' => 'Nama Spesialis'],
            ],
            'statuspulang' => [
                'label' => 'Status Pulang',
                'category' => 'Referensi',
                'icon' => 'ri-logout-box-r-line',
                'color' => '#299cdb',
                'method' => 'GET',
                'endpoint' => '{Base URL}/statuspulang/rawatInap/{isRawatInap}',
                'description' => 'Mengambil data referensi status pulang pasien (Rawat Inap true/false).',
                'params' => ['isRawatInap'],
                'response_fields' => ['kdStatusPulang' => 'Kode Status', 'nmStatusPulang' => 'Nama Status'],
            ],
            'tindakan' => [
                'label' => 'Data Tindakan',
                'category' => 'Referensi',
                'icon' => 'ri-surgical-mask-line',
                'color' => '#f672a7',
                'method' => 'GET',
                'endpoint' => '{Base URL}/tindakan/kdTkp/{kdTkp}/{start}/{limit}',
                'description' => 'Mencari data tindakan/prosedur berdasarkan kode TKP dan paginasi.',
                'params' => ['kdTkp', 'start', 'limit'],
                'response_fields' => ['kdTindakan' => 'Kode Tindakan', 'nmTindakan' => 'Nama Tindakan'],
            ],
            'obat' => [
                'label' => 'Data Obat (DPHO)',
                'category' => 'Referensi',
                'icon' => 'ri-capsule-line',
                'color' => '#0d6efd',
                'method' => 'GET',
                'endpoint' => '{Base URL}/obat/dpho/{keyword}/{start}/{limit}',
                'description' => 'Mencari data obat dari Daftar dan Plafon Harga Obat (DPHO) dengan paginasi.',
                'params' => ['keyword', 'start', 'limit'],
                'response_fields' => ['kdObat' => 'Kode Obat', 'nmObat' => 'Nama Obat'],
            ],
            'provider' => [
                'label' => 'Data Provider (Faskes Rujukan)',
                'category' => 'Referensi',
                'icon' => 'ri-building-2-line',
                'color' => '#0ab39c',
                'method' => 'GET',
                'endpoint' => '{Base URL}/provider/{start}/{limit}',
                'description' => 'Mengambil data faskes provider rujukan dengan paginasi.',
                'params' => ['start', 'limit'],
                'response_fields' => ['kdProvider' => 'Kode Provider', 'nmProvider' => 'Nama Provider'],
            ],

            // === PESERTA ===
            'peserta_no_kartu' => [
                'label' => 'Peserta (No. Kartu)',
                'category' => 'Peserta',
                'icon' => 'ri-user-heart-line',
                'color' => '#405189',
                'method' => 'GET',
                'endpoint' => '{Base URL}/peserta/{noKartu}',
                'description' => 'Mengambil data peserta BPJS berdasarkan nomor kartu.',
                'params' => ['noKartu'],
                'response_fields' => ['noKartu' => 'No. Kartu', 'nama' => 'Nama', 'nik' => 'NIK', 'sex' => 'L/P', 'tglLahir' => 'Tgl Lahir', 'kdProviderPst' => 'Kode Provider', 'nmProviderPst' => 'Provider', 'jnsPeserta' => 'Jenis Peserta', 'aktif' => 'Status'],
            ],
            'peserta_nik' => [
                'label' => 'Peserta (NIK)',
                'category' => 'Peserta',
                'icon' => 'ri-user-search-line',
                'color' => '#f06548',
                'method' => 'GET',
                'endpoint' => '{Base URL}/peserta/nik/{nik}',
                'description' => 'Mengambil data peserta BPJS berdasarkan NIK.',
                'params' => ['nik'],
                'response_fields' => ['noKartu' => 'No. Kartu', 'nama' => 'Nama', 'nik' => 'NIK', 'sex' => 'L/P', 'tglLahir' => 'Tgl Lahir', 'kdProviderPst' => 'Kode Provider', 'nmProviderPst' => 'Provider', 'jnsPeserta' => 'Jenis Peserta', 'aktif' => 'Status'],
            ],

            // === KUNJUNGAN ===
            'riwayat_kunjungan' => [
                'label' => 'Riwayat Kunjungan',
                'category' => 'Kunjungan',
                'icon' => 'ri-history-line',
                'color' => '#299cdb',
                'method' => 'GET',
                'endpoint' => '{Base URL}/kunjungan/peserta/{noKartu}',
                'description' => 'Mengambil riwayat kunjungan peserta berdasarkan nomor kartu.',
                'params' => ['noKartu'],
                'response_fields' => ['noKunjungan' => 'No. Kunjungan', 'tglKunjungan' => 'Tgl Kunjungan', 'kdPoli' => 'Kode Poli', 'nmPoli' => 'Poli'],
            ],

            // === RUJUKAN ===
            'rujukan' => [
                'label' => 'Rujukan (No. Kunjungan)',
                'category' => 'Rujukan',
                'icon' => 'ri-share-forward-line',
                'color' => '#f672a7',
                'method' => 'GET',
                'endpoint' => '{Base URL}/kunjungan/rujukan/{noKunjungan}',
                'description' => 'Mengambil data rujukan berdasarkan nomor kunjungan.',
                'params' => ['noKunjungan'],
                'response_fields' => ['noRujukan' => 'No. Rujukan', 'kdProviderTujuan' => 'Kd Provider Tujuan', 'nmProviderTujuan' => 'Provider Tujuan'],
            ],
            'rujukan_peserta' => [
                'label' => 'Rujukan (No. Kartu)',
                'category' => 'Rujukan',
                'icon' => 'ri-user-following-line',
                'color' => '#299cdb',
                'method' => 'GET',
                'endpoint' => '{Base URL}/kunjungan/peserta/{noKartu}',
                'description' => 'Mengambil data rujukan/kunjungan berdasarkan nomor kartu peserta.',
                'params' => ['noKartu'],
                'response_fields' => ['noKunjungan' => 'No. Kunjungan', 'tglKunjungan' => 'Tgl Kunjungan', 'noRujukan' => 'No. Rujukan', 'kdProviderTujuan' => 'Kd Tujuan'],
            ],

            // === KUNJUNGAN CRUD ===
            'add_kunjungan' => [
                'label' => 'Tambah Kunjungan',
                'category' => 'Kunjungan',
                'icon' => 'ri-add-circle-line',
                'color' => '#0ab39c',
                'method' => 'POST',
                'endpoint' => '{Base URL}/kunjungan',
                'description' => 'Menambahkan data kunjungan baru ke PCare BPJS. Mendukung rujukan Hemodialisa dan Spesialis.',
                'params' => ['kunjungan_body'],
                'response_fields' => ['field' => 'Field', 'message' => 'No. Kunjungan BPJS'],
            ],
            'edit_kunjungan' => [
                'label' => 'Edit Kunjungan',
                'category' => 'Kunjungan',
                'icon' => 'ri-edit-2-line',
                'color' => '#f7b84b',
                'method' => 'PUT',
                'endpoint' => '{Base URL}/kunjungan',
                'description' => 'Mengubah data kunjungan yang sudah terdaftar di PCare BPJS.',
                'params' => ['kunjungan_body'],
                'response_fields' => ['message' => 'Message'],
            ],
            'delete_kunjungan' => [
                'label' => 'Hapus Kunjungan',
                'category' => 'Kunjungan',
                'icon' => 'ri-delete-bin-line',
                'color' => '#f06548',
                'method' => 'DELETE',
                'endpoint' => '{Base URL}/kunjungan/{noKunjungan}',
                'description' => 'Menghapus data kunjungan dari PCare BPJS berdasarkan nomor kunjungan.',
                'params' => ['noKunjunganBpjs'],
                'response_fields' => ['message' => 'Message'],
            ],


            // === LAINNYA ===
            'mcu' => [
                'label' => 'Data MCU',
                'category' => 'Lainnya',
                'icon' => 'ri-file-list-3-line',
                'color' => '#405189',
                'method' => 'GET',
                'endpoint' => '{Base URL}/MCU/kunjungan/{noKunjungan}',
                'description' => 'Mengambil data Medical Check Up (MCU) peserta berdasarkan nomor kunjungan.',
                'params' => ['noKunjungan'],
                'response_fields' => ['noKartu' => 'No. Kartu', 'nama' => 'Nama'],
            ],

            // === ANTREAN ONLINE ===
            'antrean_poli' => [
                'label' => 'Referensi Poli (Antrol)',
                'category' => 'Antrean',
                'icon' => 'ri-hospital-line',
                'color' => '#878a99',
                'method' => 'GET',
                'endpoint' => '{Base URL Antrean}/ref/poli/tanggal/{tanggal}',
                'description' => 'Mengambil referensi poli antrean online berdasarkan tanggal.',
                'params' => ['tanggal'],
                'response_fields' => ['nmpoli' => 'Nama Poli', 'nmsubspesialis' => 'Nama Sub Spesialis', 'kdpoli' => 'Kode Poli', 'kdsubspesialis' => 'Kode Sub Spesialis'],
            ],
            'antrean_dokter' => [
                'label' => 'Referensi Dokter (Antrol)',
                'category' => 'Antrean',
                'icon' => 'ri-stethoscope-line',
                'color' => '#878a99',
                'method' => 'GET',
                'endpoint' => '{Base URL Antrean}/ref/dokter/kodepoli/{kodepoli}/tanggal/{tanggal}',
                'description' => 'Mengambil referensi jadwal dokter antrean online.',
                'params' => ['kodepoli', 'tanggal'],
                'response_fields' => ['namadokter' => 'Nama Dokter', 'kodedokter' => 'Kode Dokter', 'jadwal' => 'Jadwal', 'kapasitaspasien' => 'Kapasitas'],
            ],
        ];
    }

    /**
     * Computed: Grouped endpoints by category
     */
    public function getGroupedEndpointsProperty(): array
    {
        $grouped = [];
        foreach ($this->endpointDefinitions as $key => $ep) {
            $grouped[$ep['category']][$key] = $ep;
        }
        return $grouped;
    }

    /**
     * Computed: Current endpoint definition
     */
    public function getCurrentEndpointProperty(): ?array
    {
        return $this->endpointDefinitions[$this->selectedEndpoint] ?? null;
    }

    /**
     * Reset state when switching endpoint
     */
    public function updatedSelectedEndpoint()
    {
        $this->responseData = [];
        $this->responseColumns = [];
        $this->totalData = 0;
        $this->errorMessage = '';
        $this->successMessage = '';
        $this->rawResponse = '';
        $this->responseMetaCode = null;
        $this->responseMetaMessage = null;
        $this->lastFetched = '';
        $this->responseTime = 0;
    }

    /**
     * Execute the selected endpoint
     */
    public function executeEndpoint()
    {
        $this->isLoading = true;
        $this->errorMessage = '';
        $this->successMessage = '';
        $this->rawResponse = '';
        $this->responseData = [];

        try {
            $service = new BpjsPcareService();

            if (!$service->isConfigured()) {
                $this->errorMessage = 'Konfigurasi BPJS belum lengkap. Silakan isi ConsID, Secret Key, dan Base URL PCare di halaman Setting API.';
                $this->dispatch('alert', ['type' => 'error', 'message' => $this->errorMessage]);
                $this->isLoading = false;
                return;
            }

            $startTime = microtime(true);

            $result = match($this->selectedEndpoint) {
                'dokter' => $service->getDokter((int) $this->paramStart, (int) $this->paramLimit),
                'poli' => $service->getPoli((int) $this->paramStart, (int) $this->paramLimit),
                'diagnosa' => $service->getDiagnosa($this->paramKeyword ?: 'A00', (int) $this->paramStart, (int) $this->paramLimit),
                'kesadaran' => $service->getKesadaran(),
                'spesialis' => $service->getSpesialis(),
                'statuspulang' => $service->getStatusPulang((bool) $this->paramIsRawatInap),
                'tindakan' => $service->getTindakan($this->paramKdTkp, (int) $this->paramStart, (int) $this->paramLimit),
                'obat' => $service->getObat($this->paramKeyword ?: 'PARAM', (int) $this->paramStart, (int) $this->paramLimit),
                'provider' => $service->getProvider((int) $this->paramStart, (int) $this->paramLimit),
                'peserta_no_kartu' => $service->getPesertaByNoKartu($this->paramNoKartu),
                'peserta_nik' => $service->getPesertaByNik($this->paramNik),
                'riwayat_kunjungan' => $service->getRiwayatKunjungan($this->paramNoKartu),
                'rujukan' => $service->getRujukan($this->paramNoKunjungan),
                'rujukan_peserta' => $service->getRujukanByNoKartu($this->paramNoKartu),
                'mcu' => $service->getMcu($this->paramNoKunjungan),
                'add_kunjungan' => $service->addKunjungan($this->parseKunjunganBody()),
                'edit_kunjungan' => $service->editKunjungan($this->parseKunjunganBody()),
                'delete_kunjungan' => $service->deleteKunjungan($this->paramNoKunjunganBpjs),
                'antrean_poli' => $service->getRefPoliAntrean($this->paramTanggal ?: now()->format('Y-m-d')),
                'antrean_dokter' => $service->getRefDokterAntrean($this->paramKodePoli, $this->paramTanggal ?: now()->format('Y-m-d')),
                default => ['success' => false, 'data' => null, 'metadata' => ['code' => 400, 'message' => 'Endpoint tidak dikenali.'], 'raw' => null],
            };

            $this->responseTime = round((microtime(true) - $startTime) * 1000);
            $this->responseMetaCode = $result['metadata']['code'] ?? null;
            $this->responseMetaMessage = $result['metadata']['message'] ?? null;

            if ($result['success']) {
                $data = $result['data'];

                if (is_array($data)) {
                    // Handle nested structure: { count: N, list: [...] }
                    if (isset($data['list'])) {
                        $this->responseData = $data['list'];
                        $this->totalData = $data['count'] ?? count($data['list']);
                    } elseif (isset($data[0]) && is_array($data[0])) {
                        // Direct array of items
                        $this->responseData = $data;
                        $this->totalData = count($data);
                    } else {
                        // Single object - wrap in array
                        $this->responseData = [$data];
                        $this->totalData = 1;
                    }

                    // Auto detect columns
                    if (!empty($this->responseData)) {
                        $first = $this->responseData[0];
                        if (is_array($first)) {
                            $this->responseColumns = array_keys($first);
                        }
                    }
                } else {
                    $this->responseData = [];
                    $this->totalData = 0;
                }

                $this->lastFetched = now()->format('d M Y H:i:s');
                $this->successMessage = "Berhasil! Ditemukan {$this->totalData} data. ({$this->responseTime}ms)";
                $this->dispatch('alert', ['type' => 'success', 'message' => $this->successMessage]);
            } else {
                $this->errorMessage = 'Gagal: [' . ($result['metadata']['code'] ?? '?') . '] ' . ($result['metadata']['message'] ?? 'Unknown error');
                $this->dispatch('alert', ['type' => 'error', 'message' => $this->errorMessage]);

                if ($result['raw']) {
                    $this->rawResponse = is_string($result['raw']) ? $result['raw'] : json_encode($result['raw'], JSON_PRETTY_PRINT);
                }
            }
        } catch (\Exception $e) {
            $this->errorMessage = 'Exception: ' . $e->getMessage();
            $this->dispatch('alert', ['type' => 'error', 'message' => $this->errorMessage]);
        }

        $this->isLoading = false;
    }

    /**
     * Navigate pagination (for paginated endpoints)
     */
    public function nextPage()
    {
        $this->paramStart += $this->paramLimit;
        $this->executeEndpoint();
    }

    public function prevPage()
    {
        $this->paramStart = max(0, $this->paramStart - $this->paramLimit);
        $this->executeEndpoint();
    }

    /**
     * Parse body request kunjungan dari JSON textarea
     */
    protected function parseKunjunganBody(): array
    {
        if (empty($this->kunjunganBodyJson)) {
            return [];
        }

        $parsed = json_decode($this->kunjunganBodyJson, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->errorMessage = 'Format JSON body tidak valid: ' . json_last_error_msg();
            $this->dispatch('alert', ['type' => 'error', 'message' => $this->errorMessage]);
            return [];
        }

        return $parsed;
    }

    /**
     * Search pasien dari aplikasi
     */
    public function searchPasien()
    {
        if (strlen($this->searchPasienQuery) < 2) {
            $this->foundPasiens = [];
            return;
        }

        $this->foundPasiens = \App\Models\TrxPendaftaran::with(['pasien', 'dokter', 'poli', 'diagnoses'])
            ->whereHas('pasien', function ($q) {
                $q->where('nama_pasien', 'like', '%' . $this->searchPasienQuery . '%')
                  ->orWhere('no_rm', 'like', '%' . $this->searchPasienQuery . '%')
                  ->orWhere('no_penjamin', 'like', '%' . $this->searchPasienQuery . '%');
            })
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'nomor_kunjungan' => $p->nomor_kunjungan,
                    'nama_pasien' => $p->pasien->nama_pasien ?? '-',
                    'no_rm' => $p->pasien->no_rm ?? '-',
                    'no_kartu' => $p->pasien->no_penjamin ?? '-',
                    'poli' => $p->poli->nama_poli ?? '-',
                    'dokter' => $p->dokter->nama_dokter ?? '-',
                    'tanggal' => $p->created_at ? $p->created_at->format('d-m-Y') : '-',
                ];
            })
            ->toArray();
    }

    /**
     * Select pasien & auto-fill body JSON dari data kunjungan internal
     */
    public function selectPasien(int $pendaftaranId)
    {
        $pendaftaran = \App\Models\TrxPendaftaran::with(['pasien', 'dokter', 'poli', 'diagnoses'])->find($pendaftaranId);

        if (!$pendaftaran) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Data pendaftaran tidak ditemukan.']);
            return;
        }

        $pasien = $pendaftaran->pasien;
        $dokter = $pendaftaran->dokter;
        $poli = $pendaftaran->poli;

        // Parse tekanan darah (format: "120/80")
        $tekananDarah = explode('/', $pendaftaran->tekanan_darah ?? '0/0');
        $sistole = (int) ($tekananDarah[0] ?? 0);
        $diastole = (int) ($tekananDarah[1] ?? 0);

        // Get diagnosa
        $diagnoses = $pendaftaran->diagnoses()->orderBy('id')->get();

        $body = [
            'noKunjungan' => null,
            'noKartu' => $pasien->no_penjamin ?? '',
            'tglDaftar' => $pendaftaran->created_at ? $pendaftaran->created_at->format('d-m-Y') : now()->format('d-m-Y'),
            'kdPoli' => $poli->poli_bpjs_id ?? null,
            'keluhan' => $pendaftaran->riwayat_penyakit ?? 'keluhan',
            'kdSadar' => $pendaftaran->kesadaran ?? '01',
            'sistole' => $sistole,
            'diastole' => $diastole,
            'beratBadan' => (int) ($pendaftaran->berat_badan ?? 0),
            'tinggiBadan' => (int) ($pendaftaran->tinggi_badan ?? 0),
            'respRate' => 0,
            'heartRate' => (int) ($pendaftaran->nadi ?? 0),
            'lingkarPerut' => 0,
            'kdStatusPulang' => '3',
            'tglPulang' => $pendaftaran->created_at ? $pendaftaran->created_at->format('d-m-Y') : now()->format('d-m-Y'),
            'kdDokter' => $dokter->dokter_bpjs_id ?? '',
            'kdDiag1' => $diagnoses->get(0)->kode_diagnosa ?? null,
            'kdDiag2' => $diagnoses->get(1)->kode_diagnosa ?? null,
            'kdDiag3' => $diagnoses->get(2)->kode_diagnosa ?? null,
            'kdPoliRujukInternal' => null,
            'rujukLanjut' => null,
            'kdTacc' => 0,
            'alasanTacc' => null,
            'anamnesa' => $pendaftaran->riwayat_penyakit ?? 'anamnesa',
            'alergiMakan' => '00',
            'alergiUdara' => '00',
            'alergiObat' => '00',
            'kdPrognosa' => '01',
            'terapiObat' => '',
            'terapiNonObat' => '',
            'bmhp' => '',
            'suhu' => str_replace('.', ',', (string) ($pendaftaran->suhu ?? '36,4')),
        ];

        $this->kunjunganBodyJson = json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $this->foundPasiens = [];
        $this->searchPasienQuery = $pasien->nama_pasien . ' - ' . $pendaftaran->nomor_kunjungan;

        $this->dispatch('alert', ['type' => 'success', 'message' => 'Data pasien ' . $pasien->nama_pasien . ' berhasil di-insert ke body request.']);
    }

    /**
     * Generate default JSON template for kunjungan body
     */
    public function generateDefaultBody()
    {
        $body = [
            'noKunjungan' => null,
            'noKartu' => '0000043678034',
            'tglDaftar' => now()->format('d-m-Y'),
            'kdPoli' => null,
            'keluhan' => 'keluhan',
            'kdSadar' => '01',
            'sistole' => 0,
            'diastole' => 0,
            'beratBadan' => 0,
            'tinggiBadan' => 0,
            'respRate' => 0,
            'heartRate' => 0,
            'lingkarPerut' => 0,
            'kdStatusPulang' => '3',
            'tglPulang' => now()->format('d-m-Y'),
            'kdDokter' => '',
            'kdDiag1' => 'A01.0',
            'kdDiag2' => null,
            'kdDiag3' => null,
            'kdPoliRujukInternal' => null,
            'rujukLanjut' => null,
            'kdTacc' => 0,
            'alasanTacc' => null,
            'anamnesa' => 'anamnesa',
            'alergiMakan' => '00',
            'alergiUdara' => '00',
            'alergiObat' => '00',
            'kdPrognosa' => '01',
            'terapiObat' => '',
            'terapiNonObat' => '',
            'bmhp' => '',
            'suhu' => '36,4',
        ];

        $this->kunjunganBodyJson = json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function render()
    {
        return view('bridging::livewire.dokumentasi-api-page');
    }
}
