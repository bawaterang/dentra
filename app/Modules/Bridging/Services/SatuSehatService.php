<?php

namespace App\Modules\Bridging\Services;

use App\Models\MstSettingSatusehat;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SatuSehatService
{
    protected $settings;
    protected $dokterId;
    protected $clientId;
    protected $clientSecret;

    /**
     * @param int|null $dokterId ID dokter untuk pencarian kredensial spesifik (jika mode_bridging = dokter)
     */
    public function __construct(?int $dokterId = null)
    {
        $this->settings = MstSettingSatusehat::first();
        $this->dokterId = $dokterId;
        $this->resolveCredentials();
    }

    /**
     * Tentukan kredensial yang akan digunakan (Klinik vs Dokter)
     */
    protected function resolveCredentials()
    {
        if (!$this->settings) return;

        $this->clientId = $this->settings->client_id;
        $this->clientSecret = $this->settings->client_secret;

        // Jika mode bridging adalah 'dokter' dan ada dokterId yang dikirimkan
        if (($this->settings->mode_bridging ?? 'klinik') === 'dokter' && $this->dokterId) {
            $doctorCredentials = $this->settings->doctor_credentials ?? [];
            
            if (isset($doctorCredentials[$this->dokterId])) {
                $creds = $doctorCredentials[$this->dokterId];
                if (!empty($creds['client_id']) && !empty($creds['client_secret'])) {
                    $this->clientId = $creds['client_id'];
                    $this->clientSecret = $creds['client_secret'];
                    Log::info("SatuSehat: Menggunakan kredensial spesifik untuk Dokter ID: {$this->dokterId}");
                }
            }
        }
    }

    /**
     * Get validated token from cache or request fresh one.
     */
    public function getToken()
    {
        if (!$this->settings || !$this->clientId || !$this->clientSecret) {
            throw new \Exception('Konfigurasi Browser SatuSehat belum lengkap (Client ID/Secret kosong).');
        }

        $cacheKey = 'satusehat_access_token_' . md5($this->clientId);

        return Cache::remember($cacheKey, 3500, function () {
            return $this->requestNewToken();
        });
    }

    /**
     * Search for a patient in SatuSehat by NIK (and optionally Name/BirthDate)
     */
    public function searchPatient(string $nik, ?string $name = null, ?string $birthDate = null)
    {
        $url = $this->getBaseUrl() . '/Patient';
        
        // Option 1: Name + BirthDate + NIK
        if ($name && $birthDate) {
            $params = [
                'name' => $name,
                'birthdate' => $birthDate,
                'identifier' => 'https://fhir.kemkes.go.id/id/nik|' . $nik
            ];
            $response = Http::withHeaders($this->getHeaders())->get($url, $params);
            if ($response->successful() && !empty($response->json()['entry'])) {
                return $response->json()['entry'][0]['resource'];
            }
        }


        // Option 2: Name + NIK
        if ($name) {
            $params = [
                'name' => $name,
                'identifier' => 'https://fhir.kemkes.go.id/id/nik|' . $nik
            ];
            $response = Http::withHeaders($this->getHeaders())->get($url, $params);
            if ($response->successful() && !empty($response->json()['entry'])) {
                return $response->json()['entry'][0]['resource'];
            }
        }


        // Option 3: NIK only
        $params = [
            'identifier' => 'https://fhir.kemkes.go.id/id/nik|' . $nik
        ];
        $response = Http::withHeaders($this->getHeaders())->get($url, $params);
        if ($response->successful() && !empty($response->json()['entry'])) {
            return $response->json()['entry'][0]['resource'];
        }

        return null;
    }


    /**
     * Create a new Patient in SatuSehat.
     */
    public function createPatient(\App\Models\MstPasien $pasien)
    {
        $url = $this->getBaseUrl() . '/Patient';

        $body = [
            "resourceType" => "Patient",
            "meta" => [
                "profile" => ["https://fhir.kemkes.go.id/r4/StructureDefinition/Patient"]
            ],
            "identifier" => [
                [
                    "use" => "official",
                    "system" => "https://fhir.kemkes.go.id/id/nik",
                    "value" => $pasien->nik
                ]
            ],
            "active" => true,
            "name" => [
                [
                    "use" => "official",
                    "text" => strtoupper($pasien->nama_pasien)
                ]
            ],
            "gender" => $pasien->jenis_kelamin === 'Laki-laki' ? 'male' : 'female',
            "birthDate" => $pasien->tanggal_lahir ? $pasien->tanggal_lahir->format('Y-m-d') : null,
            "deceasedBoolean" => false,
            "address" => [
                [
                    "use" => "home",
                    "line" => [$pasien->alamat ?: "-"],
                    "city" => $pasien->kabupaten_id ? \App\Models\MstWilayahKabupaten::find($pasien->kabupaten_id)?->nama : "-",
                    "postalCode" => $pasien->kode_pos ?: "-",
                    "country" => "ID",
                    "extension" => [
                        [
                            "url" => "https://fhir.kemkes.go.id/r4/StructureDefinition/administrativeCode",
                            "extension" => [
                                ["url" => "province", "valueCode" => $pasien->provinsi_id ?: ""],
                                ["url" => "city", "valueCode" => $pasien->kabupaten_id ?: ""],
                                ["url" => "district", "valueCode" => $pasien->kecamatan_id ?: ""],
                                ["url" => "village", "valueCode" => $pasien->kelurahan_id ?: ""],
                                ["url" => "rw", "valueCode" => "0"], // Bawaan default if not available
                                ["url" => "rt", "valueCode" => "0"]
                            ]
                        ]
                    ]
                ]
            ],
            "maritalStatus" => [
                "coding" => [
                    [
                        "system" => "http://terminology.hl7.org/CodeSystem/v3-MaritalStatus",
                        "code" => $this->mapMaritalStatus($pasien->marital_status),
                        "display" => $pasien->marital_status ?: "Unmarried"
                    ]
                ],
                "text" => $pasien->marital_status ?: "Unmarried"
            ],
            "multipleBirthInteger" => 0,
            "contact" => [
                [
                    "relationship" => [
                        [
                            "coding" => [
                                ["system" => "http://terminology.hl7.org/CodeSystem/v2-0131", "code" => "C"]
                            ]
                        ]
                    ],
                    "name" => ["use" => "official", "text" => strtoupper($pasien->nama_pasien)], // Default to patient's own info if empty
                    "telecom" => [
                        [
                            "system" => "phone",
                            "value" => $pasien->no_telepon ?: "-",
                            "use" => "mobile"
                        ]
                    ]
                ]
            ],
            "communication" => [
                [
                    "language" => [
                        "coding" => [
                            ["system" => "urn:ietf:bcp:47", "code" => "id-ID", "display" => "Indonesian"]
                        ],
                        "text" => "Indonesian"
                    ],
                    "preferred" => true
                ]
            ]
        ];

        $response = Http::withHeaders($this->getHeaders())->post($url, $body);

        if ($response->successful()) {
            $data = $response->json();
            $uuid = $data['id'];
            $pasien->update(['satusehat_uuid' => $uuid]);
            return $data;
        }

        throw new \Exception('Gagal create Patient di SatuSehat: ' . $response->body());
    }

    protected function mapMaritalStatus($status)
    {
        $map = [
            'Married' => 'M',
            'Single' => 'U',
            'Divorced' => 'D',
            'Widowed' => 'W',
            'Never Married' => 'S'
        ];
        return $map[$status] ?? 'U';
    }

    /**
     * Search for an Organization by Name
     */
    public function searchOrganization(string $name)
    {
        $url = $this->getBaseUrl() . '/Organization';
        
        $params = ['name' => $name];
        
        $response = Http::withHeaders($this->getHeaders())->get($url, $params);
        if ($response->successful() && !empty($response->json()['entry'])) {
            return $response->json()['entry']; // Returning the array of matching organizations
        }

        return null;
    }

    /**
     * Get a specific Organization by its SatuSehat UUID
     */
    public function getOrganization(string $id)
    {
        $url = $this->getBaseUrl() . '/Organization/' . $id;
        
        $response = Http::withHeaders($this->getHeaders())->get($url);
        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    /**
     * Formats the Organization JSON payload
     */
    protected function formatOrganizationPayload(\App\Models\MstInstansi $instansi, string $id = null)
    {
        $payload = [
            "resourceType" => "Organization",
            "active" => true,
            "identifier" => [
                [
                    "use" => "official",
                    "system" => "http://sys-ids.kemkes.go.id/organization/" . $instansi->organization_id,
                    "value" => $instansi->organization_id
                ]
            ],
            "type" => [
                [
                    "coding" => [
                        [
                            "system" => "http://terminology.hl7.org/CodeSystem/organization-type",
                            "code" => "dept",
                            "display" => "Hospital Department"
                        ]
                    ]
                ]
            ],
            "name" => $instansi->nama_instansi,
            "telecom" => [
                [
                    "system" => "phone",
                    "value" => $instansi->telepon ?: "-",
                    "use" => "work"
                ],
                [
                    "system" => "email",
                    "value" => $instansi->email ?: "-",
                    "use" => "work"
                ],
                [
                    "system" => "url",
                    "value" => $instansi->website ?: "-",
                    "use" => "work"
                ]
            ],
            "address" => [
                [
                    "use" => "work",
                    "type" => "both",
                    "line" => [
                        $instansi->alamat ?: "-"
                    ],
                    "city" => $instansi->kabupaten_id ? \App\Models\MstWilayahKabupaten::find($instansi->kabupaten_id)?->nama : "-",
                    "postalCode" => $instansi->kode_pos ?: "-",
                    "country" => "ID",
                    "extension" => [
                        [
                            "url" => "https://fhir.kemkes.go.id/r4/StructureDefinition/administrativeCode",
                            "extension" => [
                                [
                                    "url" => "province",
                                    "valueCode" => $instansi->provinsi_id ?: ""
                                ],
                                [
                                    "url" => "city",
                                    "valueCode" => $instansi->kabupaten_id ?: ""
                                ],
                                [
                                    "url" => "district",
                                    "valueCode" => $instansi->kecamatan_id ?: ""
                                ],
                                [
                                    "url" => "village",
                                    "valueCode" => $instansi->kelurahan_id ?: ""
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            "partOf" => [
                "reference" => "Organization/" . $instansi->organization_id
            ]
        ];

        if ($id) {
            $payload['id'] = $id;
        }

        return $payload;
    }

    /**
     * Create a new Organization in SatuSehat.
     */
    public function createOrganization(\App\Models\MstInstansi $instansi)
    {
        $url = $this->getBaseUrl() . '/Organization';
        $body = $this->formatOrganizationPayload($instansi);

        $response = Http::withHeaders($this->getHeaders())->post($url, $body);

        if ($response->successful()) {
            $data = $response->json();
            $uuid = $data['id'];
            $instansi->update(['organization_id' => $uuid]);
            return $data;
        }

        throw new \Exception('Gagal create Organization di SatuSehat: ' . $response->body());
    }

    /**
     * Update an existing Organization in SatuSehat.
     */
    public function updateOrganization(\App\Models\MstInstansi $instansi)
    {
        if (!$instansi->organization_id) {
            throw new \Exception('Organization belum disinkronisasi ke SatuSehat (ID kosong).');
        }

        $url = $this->getBaseUrl() . '/Organization/' . $instansi->organization_id;
        $body = $this->formatOrganizationPayload($instansi, $instansi->organization_id);

        $response = Http::withHeaders($this->getHeaders())->put($url, $body);

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('Gagal update Organization di SatuSehat: ' . $response->body());
    }

    /**
     * Search for a Practitioner by NIK
     */
    public function searchPractitionerByNik(string $nik)
    {
        $url = $this->getBaseUrl() . '/Practitioner';
        $params = ['identifier' => 'https://fhir.kemkes.go.id/id/nik|' . $nik];
        
        $response = Http::withHeaders($this->getHeaders())->get($url, $params);
        if ($response->successful() && !empty($response->json()['entry'])) {
            return $response->json()['entry'];
        }

        return null;
    }

    /**
     * Search for a Practitioner by Name, Gender, and BirthDate
     */
    public function searchPractitionerByDetail(string $name, string $gender, string $birthDate)
    {
        $url = $this->getBaseUrl() . '/Practitioner';
        $params = [
            'name' => $name,
            'gender' => $gender,
            'birthdate' => $birthDate
        ];
        
        $response = Http::withHeaders($this->getHeaders())->get($url, $params);
        if ($response->successful() && !empty($response->json()['entry'])) {
            return $response->json()['entry'];
        }

        return null;
    }

    /**
     * Get a specific Practitioner by its SatuSehat UUID
     */
    public function getPractitioner(string $id)
    {
        $url = $this->getBaseUrl() . '/Practitioner/' . $id;
        
        $response = Http::withHeaders($this->getHeaders())->get($url);
        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    /**
     * Request a fresh token from SatuSehat OAuth2 endpoint.


     */
    public function requestNewToken()
    {
        $baseUrl = $this->settings->token_url ?: 'https://api-satusehat.kemkes.go.id/oauth2/v1';
        $url = rtrim($baseUrl, '/') . '/accesstoken';

        try {
            $response = Http::asForm()
                ->withoutVerifying()
                ->withQueryParameters([
                    'grant_type' => 'client_credentials'
                ])
                ->post($url, [
                    'client_id' => trim($this->clientId),
                    'client_secret' => trim($this->clientSecret),
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['access_token'] ?? null;
            }

            Log::error('SatuSehat Auth Error: ' . $response->body());
            throw new \Exception('Gagal mendapatkan token SatuSehat: ' . ($response->json()['message'] ?? 'Unknown Error'));

        } catch (\Exception $e) {
            Log::error('SatuSehat Connection Exception: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Clear cached token (use this if getting 401 Unauthorized from clinical APIs).
     */
    public function clearToken()
    {
        if ($this->clientId) {
            $cacheKey = 'satusehat_access_token_' . md5($this->clientId);
            Cache::forget($cacheKey);
        }
    }

    /**
     * Get standard FHIR headers for subsequent requests.
     */
    public function getHeaders()
    {
        return [
            'Authorization' => 'Bearer ' . $this->getToken(),
            'Content-Type' => 'application/json',
        ];
    }
    
    /**
     * Utility to get Full Base URL for FHIR Requests.
     */
    public function getBaseUrl()
    {
        return $this->settings->url ?: 'https://api-satusehat.kemkes.go.id/fhir-r4/v1';
    }
}
