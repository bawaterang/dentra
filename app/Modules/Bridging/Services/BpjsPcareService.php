<?php

namespace App\Modules\Bridging\Services;

use App\Models\MstSettingBpjs;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BpjsPcareService
{
    protected $settings;
    protected $consid;
    protected $secretKey;
    protected $userKey;
    protected $username;
    protected $password;
    protected $kdAplikasi;
    protected $baseUrl;
    protected $baseUrlAntrean;
    protected $timestamp;

    public function __construct()
    {
        $this->settings = MstSettingBpjs::first();
        $this->loadSettings();
    }

    /**
     * Load settings dari database mst_setting_bpjs
     */
    protected function loadSettings(): void
    {
        if (!$this->settings) {
            return;
        }

        $this->consid = trim($this->settings->consid ?? '');
        $this->secretKey = trim($this->settings->secret_key ?? '');
        $this->userKey = trim($this->settings->user_key ?? '');
        $this->username = trim($this->settings->username ?? '');
        $this->password = trim($this->settings->password ?? '');
        $this->kdAplikasi = trim($this->settings->kd_aplikasi ?? '');
        $this->baseUrl = rtrim($this->settings->base_url_pcare ?? '', '/');
        $this->baseUrlAntrean = rtrim($this->settings->base_url_antrian ?? '', '/');
    }

    /**
     * Validasi apakah konfigurasi BPJS sudah lengkap
     */
    public function isConfigured(): bool
    {
        return $this->settings
            && !empty($this->consid)
            && !empty($this->secretKey)
            && !empty($this->baseUrl);
    }

    // ==========================================
    // SIGNATURE GENERATION
    // ==========================================

    /**
     * Generate timestamp UTC unix-based-time
     * Format: (local time in UTC timezone in seconds) - (1970-01-01 in seconds)
     */
    protected function generateTimestamp(): string
    {
        date_default_timezone_set('UTC');
        return strval(time() - strtotime('1970-01-01 00:00:00'));
    }

    /**
     * Generate X-signature header menggunakan HMAC-SHA256
     *
     * Pola:
     * - variabel1 = consumerID & timestamp
     * - Signature = HMAC-SHA256(variabel1, consumerSecretKey)
     * - Hasil di-encode dengan base64
     *
     * @return array [signature, timestamp]
     */
    protected function generateSignature(): array
    {
        $timestamp = $this->generateTimestamp();
        $this->timestamp = $timestamp;

        // variabel1 = consumerID & timestamp
        $data = $this->consid . '&' . $timestamp;

        // HMAC-SHA256 signature (raw binary output)
        $signature = hash_hmac('sha256', $data, $this->secretKey, true);

        // Base64 encode
        $encodedSignature = base64_encode($signature);

        return [$encodedSignature, $timestamp];
    }

    /**
     * Build complete request headers untuk BPJS PCare API
     */
    protected function buildHeaders(): array
    {
        [$signature, $timestamp] = $this->generateSignature();

        // Authorization: Basic base64(username:password:kdAplikasi)
        $authString = base64_encode($this->username . ':' . $this->password . ':' . $this->kdAplikasi);

        return [
            'X-cons-id'    => $this->consid,
            'X-timestamp'  => $timestamp,
            'X-signature'  => $signature,
            'X-authorization' => 'Basic ' . $authString,
            'user_key'     => $this->userKey,
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ];
    }

    /**
     * Build headers untuk BPJS Antrean API (tanpa X-authorization)
     */
    protected function buildAntreanHeaders(): array
    {
        [$signature, $timestamp] = $this->generateSignature();

        return [
            'X-cons-id'    => $this->consid,
            'X-timestamp'  => $timestamp,
            'X-signature'  => $signature,
            'user_key'     => $this->userKey,
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ];
    }

    // ==========================================
    // RESPONSE DECRYPTION
    // ==========================================

    /**
     * Decrypt response dari web service BPJS
     *
     * Langkah:
     * 1. Dekripsi: AES-256-CBC dengan key = SHA256(consid + secretKey + timestamp)
     * 2. Dekompresi: LZ-string decompressFromEncodedURIComponent
     *
     * @param string $encryptedResponse Response terenkripsi dari BPJS
     * @return mixed Hasil dekripsi dan dekompresi
     */
    public function decryptResponse(string $encryptedResponse): mixed
    {
        // Key = consid + secretKey + timestamp (concatenate string)
        $key = $this->consid . $this->secretKey . $this->timestamp;

        // Step 1: Decrypt AES-256-CBC
        $decrypted = $this->stringDecrypt($key, $encryptedResponse);

        if ($decrypted === false || $decrypted === null) {
            Log::warning('BPJS PCare: Gagal dekripsi response AES-256-CBC');
            return null;
        }

        // Step 2: Decompress LZ-string
        $decompressed = LzString::decompressFromEncodedURIComponent($decrypted);

        if ($decompressed === null || $decompressed === '' || $decompressed === false) {
            // Mungkin response tidak dikompres, coba return langsung
            Log::info('BPJS PCare: LZ-string dekompresi kosong, menggunakan hasil dekripsi langsung');
            return json_decode($decrypted, true) ?: $decrypted;
        }

        return json_decode($decompressed, true) ?: $decompressed;
    }

    /**
     * AES-256-CBC Decrypt
     *
     * @param string $key Key untuk dekripsi (consid + secretKey + timestamp)
     * @param string $string String terenkripsi (base64)
     * @return string|false Hasil dekripsi
     */
    protected function stringDecrypt(string $key, string $string): string|false
    {
        $encryptMethod = 'AES-256-CBC';

        // Hash key dengan SHA-256
        $keyHash = hex2bin(hash('sha256', $key));

        // IV - AES-256-CBC expects 16 bytes
        $iv = substr(hex2bin(hash('sha256', $key)), 0, 16);

        // Decrypt
        $output = openssl_decrypt(
            base64_decode($string),
            $encryptMethod,
            $keyHash,
            OPENSSL_RAW_DATA,
            $iv
        );

        return $output;
    }

    // ==========================================
    // HTTP CLIENT
    // ==========================================

    /**
     * Send GET request ke BPJS PCare API
     *
     * @param string $endpoint Endpoint path (tanpa base URL)
     * @return array Response array dengan keys: success, data, metadata, raw
     */
    public function get(string $endpoint): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'data' => null,
                'metadata' => [
                    'code' => 500,
                    'message' => 'Konfigurasi BPJS belum lengkap. Silakan isi ConsID, Secret Key, dan Base URL di Setting API.',
                ],
                'raw' => null,
            ];
        }

        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        $headers = $this->buildHeaders();

        Log::info('BPJS PCare Request', [
            'url' => $url,
            'consid' => substr($this->consid, 0, 4) . '****',
            'timestamp' => $this->timestamp,
        ]);

        try {
            $response = Http::withHeaders($headers)
                ->withoutVerifying()
                ->timeout(30)
                ->get($url);

            $body = $response->json();
            $httpStatus = $response->status();

            Log::info('BPJS PCare Response', [
                'http_status' => $httpStatus,
                'meta_code' => $body['metaData']['code'] ?? 'unknown',
                'meta_message' => $body['metaData']['message'] ?? 'unknown',
            ]);

            // Cek metaData dari response
            $metaCode = $body['metaData']['code'] ?? null;
            $metaMessage = $body['metaData']['message'] ?? 'Unknown';

            if ($metaCode == 200 || $metaCode == '200') {
                // Response berhasil - perlu decrypt data
                $responseData = $body['response'] ?? null;

                if ($responseData && is_string($responseData)) {
                    // Response terenkripsi, perlu decrypt
                    $decryptedData = $this->decryptResponse($responseData);
                    return [
                        'success' => true,
                        'data' => $decryptedData,
                        'metadata' => [
                            'code' => (int) $metaCode,
                            'message' => $metaMessage,
                        ],
                        'raw' => $responseData,
                    ];
                }

                // Response tidak terenkripsi (sudah JSON)
                return [
                    'success' => true,
                    'data' => $responseData,
                    'metadata' => [
                        'code' => (int) $metaCode,
                        'message' => $metaMessage,
                    ],
                    'raw' => null,
                ];
            }

            // Error dari BPJS
            return [
                'success' => false,
                'data' => null,
                'metadata' => [
                    'code' => (int) ($metaCode ?? $httpStatus),
                    'message' => $metaMessage,
                ],
                'raw' => $response->body(),
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('BPJS PCare Connection Error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'data' => null,
                'metadata' => [
                    'code' => 0,
                    'message' => 'Connection Error: ' . $e->getMessage(),
                ],
                'raw' => null,
            ];
        } catch (\Exception $e) {
            Log::error('BPJS PCare Error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'data' => null,
                'metadata' => [
                    'code' => 500,
                    'message' => 'Error: ' . $e->getMessage(),
                ],
                'raw' => null,
            ];
        }
    }

    /**
     * Send POST request ke BPJS PCare API
     */
    public function post(string $endpoint, array $data): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'data' => null,
                'metadata' => [
                    'code' => 500,
                    'message' => 'Konfigurasi BPJS belum lengkap.',
                ],
                'raw' => null,
            ];
        }

        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        $headers = $this->buildHeaders();

        try {
            $response = Http::withHeaders($headers)
                ->withoutVerifying()
                ->timeout(30)
                ->post($url, $data);

            $body = $response->json();
            $metaCode = $body['metaData']['code'] ?? null;
            $metaMessage = $body['metaData']['message'] ?? 'Unknown';

            if ($metaCode == 200 || $metaCode == '200' || $metaCode == 201 || $metaCode == '201') {
                $responseData = $body['response'] ?? null;

                if ($responseData && is_string($responseData)) {
                    $decryptedData = $this->decryptResponse($responseData);
                    return [
                        'success' => true,
                        'data' => $decryptedData,
                        'metadata' => ['code' => (int) $metaCode, 'message' => $metaMessage],
                        'raw' => $responseData,
                    ];
                }

                return [
                    'success' => true,
                    'data' => $responseData,
                    'metadata' => ['code' => (int) $metaCode, 'message' => $metaMessage],
                    'raw' => null,
                ];
            }

            return [
                'success' => false,
                'data' => null,
                'metadata' => ['code' => (int) ($metaCode ?? $response->status()), 'message' => $metaMessage],
                'raw' => $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error('BPJS PCare POST Error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'data' => null,
                'metadata' => ['code' => 500, 'message' => 'Error: ' . $e->getMessage()],
                'raw' => null,
            ];
        }
    }

    /**
     * Send PUT request ke BPJS PCare API
     */
    public function put(string $endpoint, array $data): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'data' => null,
                'metadata' => ['code' => 500, 'message' => 'Konfigurasi BPJS belum lengkap.'],
                'raw' => null,
            ];
        }

        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        $headers = $this->buildHeaders();

        try {
            $response = Http::withHeaders($headers)
                ->withoutVerifying()
                ->timeout(30)
                ->put($url, $data);

            $body = $response->json();
            $metaCode = $body['metaData']['code'] ?? null;
            $metaMessage = $body['metaData']['message'] ?? 'Unknown';

            if ($metaCode == 200 || $metaCode == '200') {
                $responseData = $body['response'] ?? null;

                if ($responseData && is_string($responseData)) {
                    $decryptedData = $this->decryptResponse($responseData);
                    return [
                        'success' => true,
                        'data' => $decryptedData,
                        'metadata' => ['code' => (int) $metaCode, 'message' => $metaMessage],
                        'raw' => $responseData,
                    ];
                }

                return [
                    'success' => true,
                    'data' => $responseData,
                    'metadata' => ['code' => (int) $metaCode, 'message' => $metaMessage],
                    'raw' => null,
                ];
            }

            return [
                'success' => false,
                'data' => null,
                'metadata' => ['code' => (int) ($metaCode ?? $response->status()), 'message' => $metaMessage],
                'raw' => $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error('BPJS PCare PUT Error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'data' => null,
                'metadata' => ['code' => 500, 'message' => 'Error: ' . $e->getMessage()],
                'raw' => null,
            ];
        }
    }

    /**
     * Send DELETE request ke BPJS PCare API
     */
    public function delete(string $endpoint): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'data' => null,
                'metadata' => ['code' => 500, 'message' => 'Konfigurasi BPJS belum lengkap.'],
                'raw' => null,
            ];
        }

        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        $headers = $this->buildHeaders();

        try {
            $response = Http::withHeaders($headers)
                ->withoutVerifying()
                ->timeout(30)
                ->delete($url);

            $body = $response->json();
            $metaCode = $body['metaData']['code'] ?? null;
            $metaMessage = $body['metaData']['message'] ?? 'Unknown';

            if ($metaCode == 200 || $metaCode == '200') {
                return [
                    'success' => true,
                    'data' => $body['response'] ?? null,
                    'metadata' => ['code' => (int) $metaCode, 'message' => $metaMessage],
                    'raw' => null,
                ];
            }

            return [
                'success' => false,
                'data' => null,
                'metadata' => ['code' => (int) ($metaCode ?? $response->status()), 'message' => $metaMessage],
                'raw' => $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error('BPJS PCare DELETE Error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'data' => null,
                'metadata' => ['code' => 500, 'message' => 'Error: ' . $e->getMessage()],
                'raw' => null,
            ];
        }
    }

    // ==========================================
    // PCARE SPECIFIC ENDPOINTS
    // ==========================================

    /**
     * Get data dokter dari PCare
     *
     * Endpoint: {Base URL}/dokter/{start}/{limit}
     *
     * @param int $start Row data awal yang akan ditampilkan
     * @param int $limit Limit jumlah data yang akan ditampilkan
     * @return array
     */
    public function getDokter(int $start = 0, int $limit = 10): array
    {
        return $this->get("dokter/{$start}/{$limit}");
    }

    /**
     * Get data poli dari PCare
     *
     * @param int $start Row data awal
     * @param int $limit Limit jumlah data
     * @return array
     */
    public function getPoli(int $start = 0, int $limit = 10): array
    {
        return $this->get("poli/fktp/{$start}/{$limit}");
    }

    /**
     * Get data peserta berdasarkan nomor kartu BPJS
     *
     * @param string $noKartu Nomor Kartu BPJS
     * @return array
     */
    public function getPesertaByNoKartu(string $noKartu): array
    {
        return $this->get("peserta/{$noKartu}");
    }

    /**
     * Get data peserta berdasarkan NIK
     *
     * @param string $nik NIK Peserta
     * @return array
     */
    public function getPesertaByNik(string $nik): array
    {
        return $this->get("peserta/nik/{$nik}");
    }

    /**
     * Get data diagnosa / ICD-10
     *
     * @param string $keyword Keyword pencarian diagnosa
     * @param int $start Row data awal yang akan ditampilkan
     * @param int $limit Limit jumlah data yang akan ditampilkan
     * @return array
     */
    public function getDiagnosa(string $keyword, int $start = 0, int $limit = 10): array
    {
        return $this->get("diagnosa/{$keyword}/{$start}/{$limit}");
    }

    /**
     * Get data kesadaran
     *
     * @return array
     */
    public function getKesadaran(): array
    {
        return $this->get("kesadaran");
    }

    /**
     * Get data spesialis
     *
     * @return array
     */
    public function getSpesialis(): array
    {
        return $this->get("spesialis");
    }

    /**
     * Get data sub spesialis
     *
     * @param string $kdSpesialis Kode Spesialis
     * @return array
     */
    public function getSubSpesialis(string $kdSpesialis): array
    {
        return $this->get("spesialis/{$kdSpesialis}/subspesialis");
    }

    /**
     * Get data sarana (spesialis)
     *
     * @return array
     */
    public function getSarana(): array
    {
        return $this->get("spesialis/sarana");
    }

    /**
     * Get data khusus (spesialis)
     *
     * @return array
     */
    public function getKhusus(): array
    {
        return $this->get("spesialis/khusus");
    }

    /**
     * Get faskes rujukan sub spesialis
     *
     * @param string $kdSubSpesialis Kode Sub Spesialis
     * @param string $kdSarana Kode Sarana
     * @param string $tglEstRujuk Tanggal Estimasi Rujukan (dd-MM-yyyy)
     * @return array
     */
    public function getFaskesRujukanSubSpesialis(string $kdSubSpesialis, string $kdSarana, string $tglEstRujuk): array
    {
        return $this->get("spesialis/rujuk/subspesialis/{$kdSubSpesialis}/sarana/{$kdSarana}/tglEstRujuk/{$tglEstRujuk}");
    }

    /**
     * Get data provider (faskes rujukan)
     *
     * @param int $start Row data awal
     * @param int $limit Limit jumlah data
     * @return array
     */
    public function getProvider(int $start = 0, int $limit = 10): array
    {
        return $this->get("provider/{$start}/{$limit}");
    }

    /**
     * Get data obat DPHO
     *
     * @param string $keyword Keyword pencarian obat
     * @param int $start Row data awal yang akan ditampilkan
     * @param int $limit Limit jumlah data yang akan ditampilkan
     * @return array
     */
    public function getObat(string $keyword, int $start = 0, int $limit = 10): array
    {
        return $this->get("obat/dpho/{$keyword}/{$start}/{$limit}");
    }

    /**
     * Get data tindakan/prosedur
     *
     * @param string $kdTkp Kode TKP (10: RJTP, 20: RITP, 50: Promotif)
     * @param int $start Row data awal yang akan ditampilkan
     * @param int $limit Limit jumlah data yang akan ditampilkan
     * @return array
     */
    public function getTindakan(string $kdTkp, int $start = 0, int $limit = 10): array
    {
        return $this->get("tindakan/kdTkp/{$kdTkp}/{$start}/{$limit}");
    }

    /**
     * Get data tindakan by kunjungan
     *
     * @param string $noKunjungan Nomor Kunjungan
     * @return array
     */
    public function getTindakanByKunjungan(string $noKunjungan): array
    {
        return $this->get("tindakan/kunjungan/{$noKunjungan}");
    }

    /**
     * Add Tindakan
     *
     * @param array $data Body tindakan
     * @return array
     */
    public function addTindakan(array $data): array
    {
        return $this->post('tindakan', $data);
    }

    /**
     * Edit Tindakan
     *
     * @param array $data Body tindakan (harus menyertakan kdTindakanSK)
     * @return array
     */
    public function editTindakan(array $data): array
    {
        return $this->put('tindakan', $data);
    }

    /**
     * Delete Tindakan
     *
     * @param string $kdTindakanSK Kode Tindakan SK
     * @param string $noKunjungan Nomor Kunjungan
     * @return array
     */
    public function deleteTindakan(string $kdTindakanSK, string $noKunjungan): array
    {
        return $this->delete("tindakan/{$kdTindakanSK}/kunjungan/{$noKunjungan}");
    }

    /**
     * Get data status pulang
     *
     * @param bool $isRawatInap Jika rawat inap diisi true, sebaliknya false
     * @return array
     */
    public function getStatusPulang(bool $isRawatInap = false): array
    {
        $boolStr = $isRawatInap ? 'true' : 'false';
        return $this->get("statuspulang/rawatInap/{$boolStr}");
    }

    /**
     * Get data alergi
     *
     * @param string $kdJenis 01:Makanan, 02:Udara, 03:Obat
     * @return array
     */
    public function getAlergi(string $kdJenis): array
    {
        return $this->get("alergi/jenis/{$kdJenis}");
    }

    /**
     * Get data prognosa
     *
     * @return array
     */
    public function getPrognosa(): array
    {
        return $this->get("prognosa");
    }

    /**
     * Get rekapitulasi skrinning riwayat kesehatan per peserta
     *
     * @param string $noKartuOrNama Nomor atau Nama Peserta
     * @param int $start Row data awal
     * @param int $limit Limit data
     * @return array
     */
    public function getSkrinningPeserta(string $noKartuOrNama, int $start = 0, int $limit = 10): array
    {
        return $this->get("skrinning/peserta/{$noKartuOrNama}/{$start}/{$limit}");
    }

    /**
     * Get data peserta prolanis DM
     *
     * @param string $noKartuOrNama Nomor atau Nama Peserta
     * @param int $start Row data awal
     * @param int $limit Limit data
     * @return array
     */
    public function getProlanisDM(string $noKartuOrNama, int $start = 0, int $limit = 10): array
    {
        return $this->get("skrinning/prolanis/dm/{$noKartuOrNama}/{$start}/{$limit}");
    }

    /**
     * Get data peserta prolanis HT
     *
     * @param string $noKartuOrNama Nomor atau Nama Peserta
     * @param int $start Row data awal
     * @param int $limit Limit data
     * @return array
     */
    public function getProlanisHT(string $noKartuOrNama, int $start = 0, int $limit = 10): array
    {
        return $this->get("skrinning/prolanis/ht/{$noKartuOrNama}/{$start}/{$limit}");
    }

    /**
     * Get data MCU (Medical Check Up)
     *
     * @param string $noKunjungan Nomor Kunjungan
     * @return array
     */
    public function getMcu(string $noKunjungan): array
    {
        return $this->get("MCU/kunjungan/{$noKunjungan}");
    }

    /**
     * Get data riwayat kunjungan peserta berdasarkan nomor kartu
     *
     * @param string $noKartu Nomor kartu BPJS
     * @return array
     */
    public function getRiwayatKunjungan(string $noKartu): array
    {
        return $this->get("kunjungan/peserta/{$noKartu}");
    }

    /**
     * Get data rujukan berdasarkan nomor kunjungan
     *
     * @param string $noKunjungan Nomor kunjungan
     * @return array
     */
    public function getRujukan(string $noKunjungan): array
    {
        return $this->get("kunjungan/rujukan/{$noKunjungan}");
    }

    /**
     * Get data rujukan berdasarkan nomor kartu peserta
     *
     * @param string $noKartu Nomor kartu BPJS
     * @return array
     */
    public function getRujukanByNoKartu(string $noKartu): array
    {
        return $this->get("kunjungan/peserta/{$noKartu}");
    }

    // ==========================================
    // KUNJUNGAN CRUD
    // ==========================================

    /**
     * Add kunjungan baru ke BPJS PCare
     *
     * Endpoint: POST {Base URL}/kunjungan
     * Content-Type: text/plain
     *
     * @param array $data Data kunjungan sesuai format BPJS PCare
     * @return array
     */
    public function addKunjungan(array $data): array
    {
        return $this->post('kunjungan', $data);
    }

    /**
     * Edit kunjungan di BPJS PCare
     *
     * Endpoint: PUT {Base URL}/kunjungan
     * Content-Type: text/plain
     *
     * @param array $data Data kunjungan yang akan diupdate (harus menyertakan noKunjungan)
     * @return array
     */
    public function editKunjungan(array $data): array
    {
        return $this->put('kunjungan', $data);
    }

    /**
     * Delete kunjungan di BPJS PCare
     *
     * Endpoint: DELETE {Base URL}/kunjungan/{noKunjungan}
     * Content-Type: application/json; charset=utf-8
     *
     * @param string $noKunjungan Nomor Kunjungan BPJS yang akan dihapus
     * @return array
     */
    public function deleteKunjungan(string $noKunjungan): array
    {
        return $this->delete("kunjungan/{$noKunjungan}");
    }

    // ==========================================
    // OBAT KUNJUNGAN
    // ==========================================

    /**
     * Get obat by kunjungan
     *
     * @param string $noKunjungan Nomor Kunjungan
     * @return array
     */
    public function getObatByKunjungan(string $noKunjungan): array
    {
        return $this->get("obat/kunjungan/{$noKunjungan}");
    }

    /**
     * Add obat ke kunjungan
     *
     * @param array $data Body request obat
     * @return array
     */
    public function addObat(array $data): array
    {
        // Content-Type di API BPJS dimintakan text/plain, namun format datanya JSON.
        // Helper post kita menggunakan application/json yang umumnya tetap diterima oleh BPJS PCare.
        return $this->post('obat/kunjungan', $data);
    }

    /**
     * Delete obat kunjungan
     *
     * @param string $kdObatSK Kode Obat SK
     * @param string $noKunjungan Nomor Kunjungan
     * @return array
     */
    public function deleteObatKunjungan(string $kdObatSK, string $noKunjungan): array
    {
        return $this->delete("obat/{$kdObatSK}/kunjungan/{$noKunjungan}");
    }

    // ==========================================
    // PENDAFTARAN
    // ==========================================

    /**
     * Get Data Pendaftaran by Nomor Urut
     *
     * @param string $noUrut Nomor Urut Pendaftaran
     * @param string $tglDaftar Tanggal Pendaftaran (dd-MM-yyyy)
     * @return array
     */
    public function getPendaftaranByNoUrut(string $noUrut, string $tglDaftar): array
    {
        return $this->get("pendaftaran/noUrut/{$noUrut}/tglDaftar/{$tglDaftar}");
    }

    /**
     * Get Data Pendaftaran Provider
     *
     * @param string $tglDaftar Tanggal Pendaftaran (dd-MM-yyyy)
     * @param int $start Row data awal
     * @param int $limit Limit data
     * @return array
     */
    public function getPendaftaranProvider(string $tglDaftar, int $start, int $limit): array
    {
        return $this->get("pendaftaran/tglDaftar/{$tglDaftar}/{$start}/{$limit}");
    }

    /**
     * Add Pendaftaran
     *
     * @param array $data Body pendaftaran
     * @return array
     */
    public function addPendaftaran(array $data): array
    {
        return $this->post('pendaftaran', $data);
    }

    /**
     * Delete Pendaftaran
     *
     * @param string $noKartu Nomor Kartu Peserta
     * @param string $tglDaftar Tanggal Pendaftaran (dd-MM-yyyy)
     * @param string $noUrut Nomor Urut Pendaftaran
     * @param string $kdPoli Kode Poli
     * @return array
     */
    public function deletePendaftaran(string $noKartu, string $tglDaftar, string $noUrut, string $kdPoli): array
    {
        return $this->delete("pendaftaran/peserta/{$noKartu}/tglDaftar/{$tglDaftar}/noUrut/{$noUrut}/kdPoli/{$kdPoli}");
    }

    /**
     * Build body request kunjungan dari data transaksi internal aplikasi
     *
     * @param \App\Models\TrxPendaftaran $pendaftaran Data pendaftaran dari aplikasi
     * @param string $jenisRujukan 'hemodialisa' atau 'spesialis'
     * @param array $rujukanData Data rujukan tambahan
     * @param string|null $noKunjunganBpjs Nomor kunjungan BPJS (untuk edit, null untuk create)
     * @return array Body request format BPJS PCare
     */
    public function buildKunjunganBody(
        \App\Models\TrxPendaftaran $pendaftaran,
        string $jenisRujukan = 'spesialis',
        array $rujukanData = [],
        ?string $noKunjunganBpjs = null
    ): array {
        $pasien = $pendaftaran->pasien;
        $dokter = $pendaftaran->dokter;
        $poli = $pendaftaran->poli;

        // Parse tekanan darah (format: "120/80")
        $tekananDarah = explode('/', $pendaftaran->tekanan_darah ?? '0/0');
        $sistole = (int) ($tekananDarah[0] ?? 0);
        $diastole = (int) ($tekananDarah[1] ?? 0);

        // Parse alergi
        $alergiParts = explode(',', $pendaftaran->alergi ?? '');
        $alergiMakan = trim($alergiParts[0] ?? '') ?: '00';
        $alergiUdara = trim($alergiParts[1] ?? '') ?: '00';
        $alergiObat = trim($alergiParts[2] ?? '') ?: '00';

        // Get diagnosa utama dan sekunder
        $diagnoses = $pendaftaran->diagnoses()->orderBy('id')->get();
        $kdDiag1 = $diagnoses->get(0)->kode_diagnosa ?? null;
        $kdDiag2 = $diagnoses->get(1)->kode_diagnosa ?? null;
        $kdDiag3 = $diagnoses->get(2)->kode_diagnosa ?? null;

        // Build rujukan lanjut berdasarkan jenis
        $rujukLanjut = null;
        if (!empty($rujukanData)) {
            if ($jenisRujukan === 'hemodialisa') {
                $rujukLanjut = [
                    'tglEstRujuk' => $rujukanData['tglEstRujuk'] ?? now()->format('d-m-Y'),
                    'kdppk' => $rujukanData['kdppk'] ?? null,
                    'subSpesialis' => null,
                    'khusus' => [
                        'kdKhusus' => $rujukanData['kdKhusus'] ?? 'HDL',
                        'kdSubSpesialis' => null,
                        'catatan' => $rujukanData['catatan'] ?? '',
                    ],
                ];
            } else {
                $rujukLanjut = [
                    'tglEstRujuk' => $rujukanData['tglEstRujuk'] ?? now()->format('d-m-Y'),
                    'kdppk' => $rujukanData['kdppk'] ?? null,
                    'subSpesialis' => [
                        'kdSubSpesialis1' => $rujukanData['kdSubSpesialis1'] ?? null,
                        'kdSarana' => $rujukanData['kdSarana'] ?? null,
                    ],
                    'khusus' => null,
                ];
            }
        }

        $body = [
            'noKunjungan' => $noKunjunganBpjs,
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
            'kdStatusPulang' => $rujukanData['kdStatusPulang'] ?? '3',
            'tglPulang' => $pendaftaran->created_at ? $pendaftaran->created_at->format('d-m-Y') : now()->format('d-m-Y'),
            'kdDokter' => $dokter->dokter_bpjs_id ?? '',
            'kdDiag1' => $kdDiag1,
            'kdDiag2' => $kdDiag2,
            'kdDiag3' => $kdDiag3,
            'kdPoliRujukInternal' => null,
            'rujukLanjut' => $rujukLanjut,
            'kdTacc' => (int) ($rujukanData['kdTacc'] ?? 0),
            'alasanTacc' => $rujukanData['alasanTacc'] ?? null,
            'anamnesa' => $pendaftaran->riwayat_penyakit ?? 'anamnesa',
            'alergiMakan' => $alergiMakan,
            'alergiUdara' => $alergiUdara,
            'alergiObat' => $alergiObat,
            'kdPrognosa' => $rujukanData['kdPrognosa'] ?? '01',
            'terapiObat' => $rujukanData['terapiObat'] ?? '',
            'terapiNonObat' => $rujukanData['terapiNonObat'] ?? '',
            'bmhp' => $rujukanData['bmhp'] ?? '',
            'suhu' => str_replace('.', ',', (string) ($pendaftaran->suhu ?? '36,4')),
        ];

        return $body;
    }

    /**
     * Build body request pendaftaran dari data pendaftaran riil
     *
     * @param \App\Models\TrxPendaftaran $pendaftaran
     * @return array
     */
    public function buildPendaftaranBody(\App\Models\TrxPendaftaran $pendaftaran): array
    {
        $pasien = $pendaftaran->pasien;
        $dokter = $pendaftaran->dokter;
        $poli = $pendaftaran->poli;

        // Parse tekanan darah (format: "120/80")
        $tekananDarah = explode('/', $pendaftaran->tekanan_darah ?? '0/0');
        $sistole = (int) ($tekananDarah[0] ?? 0);
        $diastole = (int) ($tekananDarah[1] ?? 0);

        // Get diagnosa utama
        $kdDiag1 = $pendaftaran->diagnoses()->orderBy('id')->first()->kode_diagnosa ?? 'A00';

        return [
            'noKartu' => $pasien->no_penjamin ?? '',
            'tglDaftar' => $pendaftaran->created_at ? $pendaftaran->created_at->format('d-m-Y') : now()->format('d-m-Y'),
            'kdPoli' => $poli->poli_bpjs_id ?? '',
            'kdTkp' => '10', // Default RJTP
            'kdSadar' => $pendaftaran->kesadaran ?? '01',
            'sistole' => $sistole,
            'diastole' => $diastole,
            'beratBadan' => (int) ($pendaftaran->berat_badan ?? 0),
            'tinggi_badan' => (int) ($pendaftaran->tinggi_badan ?? 0),
            'respRate' => 0,
            'heartRate' => (int) ($pendaftaran->nadi ?? 0),
            'lingkarPerut' => 0,
            'kdProviderRayon' => $this->settings->kd_ppk ?? '',
            'kdStatusPulang' => '3', // Default Berobat Jalan
            'tglPulang' => $pendaftaran->created_at ? $pendaftaran->created_at->format('d-m-Y') : now()->format('d-m-Y'),
            'kdDokter' => $dokter->dokter_bpjs_id ?? '',
            'kdDiag1' => $kdDiag1,
            'kdDiag2' => null,
            'kdDiag3' => null,
            'kdPoliRujukInternal' => null,
            'rujukLanjut' => null,
            'kdTacc' => 0,
            'alasanTacc' => null,
        ];
    }

    /**
     * Build body request antrean dari data antrian riil
     *
     * @param \App\Models\TrxAntrian $antrian
     * @return array
     */
    public function buildAntreanBody(\App\Models\TrxAntrian $antrian): array
    {
        $pasien = $antrian->pasien;
        $pendaftaran = $antrian->pendaftaran;
        $poli = $antrian->poli;
        $dokter = $antrian->dokter;

        return [
            'nomorkartu' => $antrian->no_asuransi ?? ($pasien->no_penjamin ?? ''),
            'nik' => $antrian->nik_manual ?? ($pasien->nik ?? ''),
            'nohp' => $antrian->no_telepon_manual ?? ($pasien->no_hp ?? ''),
            'kodepoli' => $poli ? ($poli->poli_bpjs_id ?? $poli->kode_poli) : $antrian->kode_poli,
            'namapoli' => $poli->nama_poli ?? 'Poli',
            'norm' => $pasien->no_rm ?? '-',
            'tanggalperiksa' => $antrian->tanggal_antrian ? $antrian->tanggal_antrian->format('Y-m-d') : now()->format('Y-m-d'),
            'kodedokter' => (int) ($dokter->dokter_bpjs_id ?? ($antrian->kode_dokter ?? 0)),
            'namadokter' => $dokter->nama_dokter ?? 'Dokter',
            'jampraktek' => $antrian->time_slot ?? '08:00-12:00',
            'nomorantrean' => $antrian->nomor_antrian ?? '-',
            'angkaantrean' => (int) filter_var($antrian->nomor_antrian, FILTER_SANITIZE_NUMBER_INT) ?: 1,
            'keterangan' => 'Harap datang tepat waktu'
        ];
    }

    /**
     * Build body update status antrean dari data riil
     *
     * @param \App\Models\TrxAntrian $antrian
     * @return array
     */
    public function buildUpdateStatusAntreanBody(\App\Models\TrxAntrian $antrian): array
    {
        $pasien = $antrian->pasien;
        $poli = $antrian->poli;

        return [
            'tanggalperiksa' => $antrian->tanggal_antrian ? $antrian->tanggal_antrian->format('Y-m-d') : now()->format('Y-m-d'),
            'kodepoli' => $poli ? ($poli->poli_bpjs_id ?? $poli->kode_poli) : $antrian->kode_poli,
            'nomorkartu' => $antrian->no_asuransi ?? ($pasien->no_penjamin ?? ''),
            'status' => $antrian->status == 'hadir' || $antrian->status == 'selesai' ? 1 : 2, // 1 Hadir, 2 Tidak Hadir
            'waktu' => (int)(microtime(true) * 1000)
        ];
    }

    /**
     * Build body batal antrean dari data riil
     *
     * @param \App\Models\TrxAntrian $antrian
     * @return array
     */
    public function buildBatalAntreanBody(\App\Models\TrxAntrian $antrian): array
    {
        $pasien = $antrian->pasien;
        $poli = $antrian->poli;

        return [
            'tanggalperiksa' => $antrian->tanggal_antrian ? $antrian->tanggal_antrian->format('Y-m-d') : now()->format('Y-m-d'),
            'kodepoli' => $poli ? ($poli->poli_bpjs_id ?? $poli->kode_poli) : $antrian->kode_poli,
            'nomorkartu' => $antrian->no_asuransi ?? ($pasien->no_penjamin ?? ''),
            'alasan' => 'Dibatalkan oleh sistem aplikasi'
        ];
    }

    /**
     * Build body request tindakan
     *
     * @param \App\Models\TrxTindakan $tindakan
     * @param string $noKunjunganBpjs
     * @param int $kdTindakanSK
     * @return array
     */
    public function buildTindakanBody(\App\Models\TrxTindakan $tindakan, string $noKunjunganBpjs, int $kdTindakanSK = 0): array
    {
        $mstTindakan = $tindakan->tindakan;

        return [
            'kdTindakanSK' => $kdTindakanSK,
            'noKunjungan' => $noKunjunganBpjs,
            'kdTindakan' => $mstTindakan->kode_tindakan ?? '',
            'biaya' => (int) $tindakan->biaya,
            'keterangan' => null,
            'hasil' => 0,
        ];
    }

    // ==========================================
    // HTTP CLIENT ANTREAN ONLINE
    // ==========================================

    /**
     * Send GET request ke BPJS Antrean API
     *
     * @param string $endpoint Endpoint path (tanpa base URL)
     * @return array
     */
    public function getAntrean(string $endpoint): array
    {
        if (!$this->settings || empty($this->consid) || empty($this->secretKey) || empty($this->baseUrlAntrean)) {
            return [
                'success' => false,
                'data' => null,
                'metadata' => [
                    'code' => 500,
                    'message' => 'Konfigurasi Antrean BPJS belum lengkap. Silakan isi URL Antrean di Setting API.',
                ],
                'raw' => null,
            ];
        }

        $url = $this->baseUrlAntrean . '/' . ltrim($endpoint, '/');
        $headers = $this->buildAntreanHeaders();

        Log::info('BPJS Antrean Request', [
            'url' => $url,
            'consid' => substr($this->consid, 0, 4) . '****',
            'timestamp' => $this->timestamp,
        ]);

        try {
            $response = Http::withHeaders($headers)
                ->withoutVerifying()
                ->timeout(30)
                ->get($url);

            $body = $response->json();
            $httpStatus = $response->status();

            // Meta data in Antrean is sometimes lowercase 'metadata' or 'metaData' depending on API
            $metaDataObj = $body['metadata'] ?? $body['metaData'] ?? [];
            $metaCode = $metaDataObj['code'] ?? $httpStatus;
            $metaMessage = $metaDataObj['message'] ?? 'Unknown';

            Log::info('BPJS Antrean Response', [
                'http_status' => $httpStatus,
                'meta_code' => $metaCode,
                'meta_message' => $metaMessage,
            ]);

            // Antrean often uses code '1' or 1 for success instead of 200, occasionally 200
            if ($metaCode == 200 || $metaCode == '200' || $metaCode == 1 || $metaCode == '1') {
                $responseData = $body['response'] ?? null;

                if ($responseData && is_string($responseData)) {
                    $decryptedData = $this->decryptResponse($responseData);
                    return [
                        'success' => true,
                        'data' => $decryptedData,
                        'metadata' => [
                            'code' => (int) ($metaCode == 1 || $metaCode == '1' ? 200 : $metaCode),
                            'message' => $metaMessage,
                        ],
                        'raw' => $body,
                    ];
                }

                // If response is not encrypted or array directly
                return [
                    'success' => true,
                    'data' => is_array($responseData) ? $responseData : [],
                    'metadata' => [
                        'code' => (int) ($metaCode == 1 || $metaCode == '1' ? 200 : $metaCode),
                        'message' => $metaMessage,
                    ],
                    'raw' => $body,
                ];
            } else {
                return [
                    'success' => false,
                    'data' => null,
                    'metadata' => [
                        'code' => (int) $metaCode,
                        'message' => $metaMessage,
                    ],
                    'raw' => $body,
                ];
            }
        } catch (\Exception $e) {
            Log::error('BPJS Antrean Error: ' . $e->getMessage());
            return [
                'success' => false,
                'data' => null,
                'metadata' => [
                    'code' => 500,
                    'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage(),
                ],
                'raw' => null,
            ];
        }
    }

    /**
     * Send POST request ke BPJS Antrean API
     *
     * @param string $endpoint Endpoint path (tanpa base URL)
     * @param array $data Body request
     * @return array
     */
    public function postAntrean(string $endpoint, array $data): array
    {
        if (!$this->settings || empty($this->consid) || empty($this->secretKey) || empty($this->baseUrlAntrean)) {
            return [
                'success' => false,
                'data' => null,
                'metadata' => [
                    'code' => 500,
                    'message' => 'Konfigurasi Antrean BPJS belum lengkap. Silakan isi URL Antrean di Setting API.',
                ],
                'raw' => null,
            ];
        }

        $url = $this->baseUrlAntrean . '/' . ltrim($endpoint, '/');
        $headers = $this->buildAntreanHeaders();

        Log::info('BPJS Antrean POST Request', [
            'url' => $url,
            'data' => $data,
        ]);

        try {
            $response = Http::withHeaders($headers)
                ->withoutVerifying()
                ->timeout(30)
                ->post($url, $data);

            $body = $response->json();
            $httpStatus = $response->status();

            $metaDataObj = $body['metadata'] ?? $body['metaData'] ?? [];
            $metaCode = $metaDataObj['code'] ?? $httpStatus;
            $metaMessage = $metaDataObj['message'] ?? 'Unknown';

            if ($metaCode == 200 || $metaCode == '200' || $metaCode == 1 || $metaCode == '1') {
                return [
                    'success' => true,
                    'data' => $body['response'] ?? null,
                    'metadata' => [
                        'code' => (int) ($metaCode == 1 || $metaCode == '1' ? 200 : $metaCode),
                        'message' => $metaMessage,
                    ],
                    'raw' => $body,
                ];
            } else {
                return [
                    'success' => false,
                    'data' => null,
                    'metadata' => [
                        'code' => (int) $metaCode,
                        'message' => $metaMessage,
                    ],
                    'raw' => $body,
                ];
            }
        } catch (\Exception $e) {
            Log::error('BPJS Antrean POST Error: ' . $e->getMessage());
            return [
                'success' => false,
                'data' => null,
                'metadata' => [
                    'code' => 500,
                    'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage(),
                ],
                'raw' => null,
            ];
        }
    }

    /**
     * Get referensi poli antrean
     *
     * @param string $tanggal Format YYYY-MM-DD
     */
    public function getRefPoliAntrean(string $tanggal): array
    {
        return $this->getAntrean("ref/poli/tanggal/{$tanggal}");
    }

    /**
     * Get referensi dokter antrean
     *
     * @param string $kodepoli Kode poli
     * @param string $tanggal Format YYYY-MM-DD
     */
    public function getRefDokterAntrean(string $kodepoli, string $tanggal): array
    {
        return $this->getAntrean("ref/dokter/kodepoli/{$kodepoli}/tanggal/{$tanggal}");
    }

    /**
     * Add Antrean
     *
     * @param array $data Body antrean
     * @return array
     */
    public function addAntrean(array $data): array
    {
        return $this->postAntrean('antrean/add', $data);
    }

    /**
     * Update status antrean hadir/tidak hadir
     *
     * @param array $data Body status
     * @return array
     */
    public function updateStatusAntrean(array $data): array
    {
        // Endpoint sama dengan add antrean menurut dokumentasi user
        return $this->postAntrean('antrean/add', $data);
    }

    /**
     * Batal Antrean
     *
     * @param array $data Body batal
     * @return array
     */
    public function batalAntrean(array $data): array
    {
        return $this->postAntrean('antrean/batal', $data);
    }
}

