<?php

namespace App\Modules\Bridging\Services;

use App\Models\LogSatusehat;
use App\Models\MstInstansi;
use App\Models\MstKfaObat;
use App\Models\MstLocation;
use App\Models\MstPasien;
use App\Models\MstSettingSatusehat;
use App\Models\MstWilayahKabupaten;
use App\Models\TrxPendaftaran;
use App\Models\TrxSatusehatLog;
use App\Models\TrxTindakan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SatuSehatService
{
    protected $settings;

    protected $dokterId;

    protected $clientId;

    protected $clientSecret;

    /**
     * Tanggal minimum yang diperbolehkan oleh SatuSehat.
     * Pengiriman data tidak boleh menggunakan tanggal sebelum 03 Juni 2014.
     */
    const SATUSEHAT_MIN_DATE = '2014-06-03';

    /**
     * @param  int|null  $dokterId  ID dokter untuk pencarian kredensial spesifik (jika mode_bridging = dokter)
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
        if (! $this->settings) {
            return;
        }

        $this->clientId = $this->settings->client_id;
        $this->clientSecret = $this->settings->client_secret;

        // Jika mode bridging adalah 'dokter' dan ada dokterId yang dikirimkan
        if (($this->settings->mode_bridging ?? 'klinik') === 'dokter' && $this->dokterId) {
            $doctorCredentials = $this->settings->doctor_credentials ?? [];

            if (isset($doctorCredentials[$this->dokterId])) {
                $creds = $doctorCredentials[$this->dokterId];
                if (! empty($creds['client_id']) && ! empty($creds['client_secret'])) {
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
        if (! $this->settings || ! $this->clientId || ! $this->clientSecret) {
            throw new \Exception('Konfigurasi Browser SatuSehat belum lengkap (Client ID/Secret kosong).');
        }

        $cacheKey = 'satusehat_access_token_'.md5($this->clientId);

        return Cache::remember($cacheKey, 3500, function () {
            return $this->requestNewToken();
        });
    }

    /**
     * Search for a patient in SatuSehat by NIK (and optionally Name/BirthDate)
     */
    public function searchPatient(string $nik, ?string $name = null, ?string $birthDate = null)
    {
        $url = $this->getBaseUrl().'/Patient';

        // Option 1: Name + BirthDate + NIK
        if ($name && $birthDate) {
            $params = [
                'name' => $name,
                'birthdate' => $birthDate,
                'identifier' => 'https://fhir.kemkes.go.id/id/nik|'.$nik,
            ];
            $response = Http::withHeaders($this->getHeaders())->get($url, $params);
            if ($response->successful() && ! empty($response->json()['entry'])) {
                return $response->json()['entry'][0]['resource'];
            }
        }

        // Option 2: Name + NIK
        if ($name) {
            $params = [
                'name' => $name,
                'identifier' => 'https://fhir.kemkes.go.id/id/nik|'.$nik,
            ];
            $response = Http::withHeaders($this->getHeaders())->get($url, $params);
            if ($response->successful() && ! empty($response->json()['entry'])) {
                return $response->json()['entry'][0]['resource'];
            }
        }

        // Option 3: NIK only
        $params = [
            'identifier' => 'https://fhir.kemkes.go.id/id/nik|'.$nik,
        ];
        $response = Http::withHeaders($this->getHeaders())->get($url, $params);
        if ($response->successful() && ! empty($response->json()['entry'])) {
            return $response->json()['entry'][0]['resource'];
        }

        return null;
    }

    /**
     * Create a new Patient in SatuSehat.
     */
    public function createPatient(MstPasien $pasien)
    {
        $url = $this->getBaseUrl().'/Patient';

        $body = [
            'resourceType' => 'Patient',
            'meta' => [
                'profile' => ['https://fhir.kemkes.go.id/r4/StructureDefinition/Patient'],
            ],
            'identifier' => [
                [
                    'use' => 'official',
                    'system' => 'https://fhir.kemkes.go.id/id/nik',
                    'value' => $pasien->nik,
                ],
            ],
            'active' => true,
            'name' => [
                [
                    'use' => 'official',
                    'text' => strtoupper($pasien->nama_pasien),
                ],
            ],
            'gender' => $pasien->jenis_kelamin === 'Laki-laki' ? 'male' : 'female',
            'birthDate' => $pasien->tanggal_lahir ? $pasien->tanggal_lahir->format('Y-m-d') : null,
            'deceasedBoolean' => false,
            'address' => [
                [
                    'use' => 'home',
                    'line' => [$pasien->alamat ?: '-'],
                    'city' => $pasien->kabupaten_id ? MstWilayahKabupaten::find($pasien->kabupaten_id)?->nama : '-',
                    'postalCode' => $pasien->kode_pos ?: '-',
                    'country' => 'ID',
                    'extension' => [
                        [
                            'url' => 'https://fhir.kemkes.go.id/r4/StructureDefinition/administrativeCode',
                            'extension' => [
                                ['url' => 'province', 'valueCode' => $pasien->provinsi_id ?: ''],
                                ['url' => 'city', 'valueCode' => $pasien->kabupaten_id ?: ''],
                                ['url' => 'district', 'valueCode' => $pasien->kecamatan_id ?: ''],
                                ['url' => 'village', 'valueCode' => $pasien->kelurahan_id ?: ''],
                                ['url' => 'rw', 'valueCode' => '0'], // Bawaan default if not available
                                ['url' => 'rt', 'valueCode' => '0'],
                            ],
                        ],
                    ],
                ],
            ],
            'maritalStatus' => [
                'coding' => [
                    [
                        'system' => 'http://terminology.hl7.org/CodeSystem/v3-MaritalStatus',
                        'code' => $this->mapMaritalStatus($pasien->marital_status),
                        'display' => $pasien->marital_status ?: 'Unmarried',
                    ],
                ],
                'text' => $pasien->marital_status ?: 'Unmarried',
            ],
            'multipleBirthInteger' => 0,
            'contact' => [
                [
                    'relationship' => [
                        [
                            'coding' => [
                                ['system' => 'http://terminology.hl7.org/CodeSystem/v2-0131', 'code' => 'C'],
                            ],
                        ],
                    ],
                    'name' => ['use' => 'official', 'text' => strtoupper($pasien->nama_pasien)], // Default to patient's own info if empty
                    'telecom' => [
                        [
                            'system' => 'phone',
                            'value' => $pasien->no_telepon ?: '-',
                            'use' => 'mobile',
                        ],
                    ],
                ],
            ],
            'communication' => [
                [
                    'language' => [
                        'coding' => [
                            ['system' => 'urn:ietf:bcp:47', 'code' => 'id-ID', 'display' => 'Indonesian'],
                        ],
                        'text' => 'Indonesian',
                    ],
                    'preferred' => true,
                ],
            ],
        ];

        $response = Http::withHeaders($this->getHeaders())->post($url, $body);

        if ($response->successful()) {
            $data = $response->json();
            $uuid = $data['id'];
            $pasien->update(['satusehat_uuid' => $uuid]);

            return $data;
        }

        throw new \Exception('Gagal create Patient di SatuSehat: '.$response->body());
    }

    protected function mapMaritalStatus($status)
    {
        $map = [
            'Married' => 'M',
            'Single' => 'U',
            'Divorced' => 'D',
            'Widowed' => 'W',
            'Never Married' => 'S',
        ];

        return $map[$status] ?? 'U';
    }

    /**
     * Search for an Organization by Name
     */
    public function searchOrganization(string $name)
    {
        $url = $this->getBaseUrl().'/Organization';

        $params = ['name' => $name];

        $response = Http::withHeaders($this->getHeaders())->get($url, $params);
        if ($response->successful() && ! empty($response->json()['entry'])) {
            return $response->json()['entry']; // Returning the array of matching organizations
        }

        return null;
    }

    /**
     * Get a specific Organization by its SatuSehat UUID
     */
    public function getOrganization(string $id)
    {
        $url = $this->getBaseUrl().'/Organization/'.$id;

        $response = Http::withHeaders($this->getHeaders())->get($url);
        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    /**
     * Formats the Organization JSON payload
     */
    protected function formatOrganizationPayload(MstInstansi $instansi, ?string $id = null)
    {
        $payload = [
            'resourceType' => 'Organization',
            'active' => true,
            'identifier' => [
                [
                    'use' => 'official',
                    'system' => 'http://sys-ids.kemkes.go.id/organization/'.$instansi->organization_id,
                    'value' => $instansi->organization_id,
                ],
            ],
            'type' => [
                [
                    'coding' => [
                        [
                            'system' => 'http://terminology.hl7.org/CodeSystem/organization-type',
                            'code' => 'dept',
                            'display' => 'Hospital Department',
                        ],
                    ],
                ],
            ],
            'name' => $instansi->nama_instansi,
            'telecom' => [
                [
                    'system' => 'phone',
                    'value' => $instansi->telepon ?: '-',
                    'use' => 'work',
                ],
                [
                    'system' => 'email',
                    'value' => $instansi->email ?: '-',
                    'use' => 'work',
                ],
                [
                    'system' => 'url',
                    'value' => $instansi->website ?: '-',
                    'use' => 'work',
                ],
            ],
            'address' => [
                [
                    'use' => 'work',
                    'type' => 'both',
                    'line' => [
                        $instansi->alamat ?: '-',
                    ],
                    'city' => $instansi->kabupaten_id ? MstWilayahKabupaten::find($instansi->kabupaten_id)?->nama : '-',
                    'postalCode' => $instansi->kode_pos ?: '-',
                    'country' => 'ID',
                    'extension' => [
                        [
                            'url' => 'https://fhir.kemkes.go.id/r4/StructureDefinition/administrativeCode',
                            'extension' => [
                                [
                                    'url' => 'province',
                                    'valueCode' => $instansi->provinsi_id ?: '',
                                ],
                                [
                                    'url' => 'city',
                                    'valueCode' => $instansi->kabupaten_id ?: '',
                                ],
                                [
                                    'url' => 'district',
                                    'valueCode' => $instansi->kecamatan_id ?: '',
                                ],
                                [
                                    'url' => 'village',
                                    'valueCode' => $instansi->kelurahan_id ?: '',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'partOf' => [
                'reference' => 'Organization/'.$instansi->organization_id,
            ],
        ];

        if ($id) {
            $payload['id'] = $id;
        }

        return $payload;
    }

    /**
     * Create a new Organization in SatuSehat.
     */
    public function createOrganization(MstInstansi $instansi)
    {
        $url = $this->getBaseUrl().'/Organization';
        $body = $this->formatOrganizationPayload($instansi);

        $response = Http::withHeaders($this->getHeaders())->post($url, $body);

        if ($response->successful()) {
            $data = $response->json();
            $uuid = $data['id'];
            $instansi->update(['organization_id' => $uuid]);

            return $data;
        }

        throw new \Exception('Gagal create Organization di SatuSehat: '.$response->body());
    }

    /**
     * Update an existing Organization in SatuSehat.
     */
    public function updateOrganization(MstInstansi $instansi)
    {
        if (! $instansi->organization_id) {
            throw new \Exception('Organization belum disinkronisasi ke SatuSehat (ID kosong).');
        }

        $url = $this->getBaseUrl().'/Organization/'.$instansi->organization_id;
        $body = $this->formatOrganizationPayload($instansi, $instansi->organization_id);

        $response = Http::withHeaders($this->getHeaders())->put($url, $body);

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('Gagal update Organization di SatuSehat: '.$response->body());
    }

    /**
     * Search for a Practitioner by NIK
     */
    public function searchPractitionerByNik(string $nik)
    {
        $url = $this->getBaseUrl().'/Practitioner';
        $params = ['identifier' => 'https://fhir.kemkes.go.id/id/nik|'.$nik];

        $response = Http::withHeaders($this->getHeaders())->get($url, $params);
        if ($response->successful() && ! empty($response->json()['entry'])) {
            return $response->json()['entry'];
        }

        return null;
    }

    /**
     * Search for a Practitioner by Name, Gender, and BirthDate
     */
    public function searchPractitionerByDetail(string $name, string $gender, string $birthDate)
    {
        $url = $this->getBaseUrl().'/Practitioner';
        $params = [
            'name' => $name,
            'gender' => $gender,
            'birthdate' => $birthDate,
        ];

        $response = Http::withHeaders($this->getHeaders())->get($url, $params);
        if ($response->successful() && ! empty($response->json()['entry'])) {
            return $response->json()['entry'];
        }

        return null;
    }

    /**
     * Get a specific Practitioner by its SatuSehat UUID
     */
    public function getPractitioner(string $id)
    {
        $url = $this->getBaseUrl().'/Practitioner/'.$id;

        $response = Http::withHeaders($this->getHeaders())->get($url);
        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    /**
     * Search for a Location
     */
    /**
     * Search for a Location by Name
     */
    public function searchLocationByName(string $name)
    {
        $url = $this->getBaseUrl().'/Location';
        $params = ['name' => $name];

        $response = Http::withHeaders($this->getHeaders())->get($url, $params);
        if ($response->successful() && ! empty($response->json()['entry'])) {
            return $response->json()['entry'];
        }

        return null;
    }

    /**
     * Search for a Location by Organization ID
     */
    public function searchLocationByOrganization(string $organizationId)
    {
        $url = $this->getBaseUrl().'/Location';
        $orgRef = str_contains($organizationId, 'Organization/') ? $organizationId : 'Organization/'.$organizationId;
        $params = ['organization' => $orgRef];

        $response = Http::withHeaders($this->getHeaders())->get($url, $params);
        if ($response->successful() && ! empty($response->json()['entry'])) {
            return $response->json()['entry'];
        }

        return null;
    }

    /**
     * Search for a Location by its specific ID
     */
    public function searchLocationByIDLocation(string $id)
    {
        // Fetch single resource and wrap it in FHIR search format
        $url = $this->getBaseUrl().'/Location/'.$id;
        $response = Http::withHeaders($this->getHeaders())->get($url);

        if ($response->successful()) {
            return [
                ['resource' => $response->json()],
            ];
        }

        return null;
    }

    /**
     * Get a specific Location by ID
     */
    public function getLocation(string $id)
    {
        $url = $this->getBaseUrl().'/Location/'.$id;
        $response = Http::withHeaders($this->getHeaders())->get($url);
        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    /**
     * Create a Location in SatuSehat
     */
    public function createLocation(MstLocation $location)
    {
        $url = $this->getBaseUrl().'/Location';
        $payload = $this->formatLocationPayload($location);

        $response = Http::withHeaders($this->getHeaders())->post($url, $payload);

        if ($response->successful()) {
            $data = $response->json();
            $location->update(['location_id' => $data['id']]);

            return $data;
        }

        throw new \Exception('SatuSehat Create Location Error: '.$response->body());
    }

    /**
     * Update a Location in SatuSehat
     */
    public function updateLocation(MstLocation $location)
    {
        if (! $location->location_id) {
            throw new \Exception('Location ID SatuSehat tidak ditemukan.');
        }

        $url = $this->getBaseUrl().'/Location/'.$location->location_id;
        $payload = $this->formatLocationPayload($location, $location->location_id);

        $response = Http::withHeaders($this->getHeaders())->put($url, $payload);

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('SatuSehat Update Location Error: '.$response->body());
    }

    /**
     * Format Location payload for FHIR R4
     */
    private function formatLocationPayload(MstLocation $location, $id = null)
    {
        $instansi = MstInstansi::first();
        if (! $instansi) {
            throw new \Exception('Data profil klinik (mst_instansi) tidak ditemukan.');
        }

        $payload = [
            'resourceType' => 'Location',
            'identifier' => [
                [
                    'system' => 'http://sys-ids.kemkes.go.id/location/'.$instansi->organization_id,
                    'value' => $instansi->organization_name,
                ],
            ],

            'status' => $location->status ?: 'active',
            'name' => $location->location_name,
            'description' => $location->description,
            'mode' => 'instance',
            'telecom' => [
                [
                    'system' => 'phone',
                    'value' => $instansi->telepon,
                    'use' => 'work',
                ],
                [
                    'system' => 'email',
                    'value' => $instansi->email,
                ],
                [
                    'system' => 'url',
                    'value' => $instansi->website,
                    'use' => 'work',
                ],
            ],
            'address' => [
                'use' => 'work',
                'line' => [$instansi->alamat],
                'city' => $instansi->kabupaten_id ? MstWilayahKabupaten::where('kode', $instansi->kabupaten_id)->value('nama') : '-',
                'postalCode' => $instansi->kode_pos,
                'country' => 'ID',
                'extension' => [
                    [
                        'url' => 'https://fhir.kemkes.go.id/r4/StructureDefinition/administrativeCode',
                        'extension' => [
                            ['url' => 'province', 'valueCode' => $instansi->provinsi_id],
                            ['url' => 'city', 'valueCode' => $instansi->kabupaten_id],
                            ['url' => 'district', 'valueCode' => $instansi->kecamatan_id],
                            ['url' => 'village', 'valueCode' => $instansi->kelurahan_id],
                            ['url' => 'rt', 'valueCode' => '1'],
                            ['url' => 'rw', 'valueCode' => '1'],
                        ],
                    ],
                ],
            ],
            'physicalType' => [
                'coding' => [
                    [
                        'system' => 'http://terminology.hl7.org/CodeSystem/location-physical-type',
                        'code' => 'ro',
                        'display' => 'Room',
                    ],
                ],
            ],
            'managingOrganization' => [
                'reference' => 'Organization/'.$instansi->organization_id,
            ],
        ];

        if ($id) {
            $payload['id'] = $id;
        }

        if ($location->longitude && $location->latitude) {
            $payload['position'] = [
                'longitude' => (float) $location->longitude,
                'latitude' => (float) $location->latitude,
                'altitude' => 0,
            ];
        }

        return $payload;
    }

    // ==========================================
    // ENCOUNTER RESOURCE
    // ==========================================

    /**
     * Search Encounters by Patient Subject UUID (satusehat_uuid from mst_pasien).
     * GET {baseUrl}/Encounter?subject={subjectUuid}
     */
    public function searchEncounterBySubject(string $subjectUuid)
    {
        $url = $this->getBaseUrl().'/Encounter';
        $params = ['subject' => $subjectUuid];

        $response = Http::withHeaders($this->getHeaders())->get($url, $params);

        if ($response->successful() && ! empty($response->json()['entry'])) {
            return $response->json()['entry'];
        }

        return null;
    }

    /**
     * Get a specific Encounter by its UUID (path variable).
     * GET {baseUrl}/Encounter/{id}
     */
    public function getEncounterById(string $encounterUuid)
    {
        $url = $this->getBaseUrl().'/Encounter/'.$encounterUuid;

        $response = Http::withHeaders($this->getHeaders())->get($url);

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    /**
     * Create a new Encounter (status: arrived).
     * POST {baseUrl}/Encounter
     *
     * @param  array  $data  Expecting keys: pasien (MstPasien), dokter (MstDokter), location (MstLocation), nomor_kunjungan, period_start
     */
    public function createEncounter(array $data)
    {
        $instansi = MstInstansi::first();
        if (! $instansi) {
            throw new \Exception('Data profil klinik (mst_instansi) tidak ditemukan.');
        }

        $pasien = $data['pasien'];   // MstPasien model
        $dokter = $data['dokter'];   // MstDokter model
        $location = $data['location']; // MstLocation model
        $nomorKunjungan = $data['nomor_kunjungan'] ?? '';
        $periodStart = $this->formatUtcDateTime($data['period_start'] ?? null);

        $body = [
            'resourceType' => 'Encounter',
            'status' => 'arrived',
            'class' => [
                'system' => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
                'code' => 'AMB',
                'display' => 'ambulatory',
            ],
            'subject' => [
                'reference' => 'Patient/'.$pasien->satusehat_uuid,
                'display' => $pasien->nama_pasien,
            ],
            'participant' => [
                [
                    'type' => [
                        [
                            'coding' => [
                                [
                                    'system' => 'http://terminology.hl7.org/CodeSystem/v3-ParticipationType',
                                    'code' => 'ATND',
                                    'display' => 'attender',
                                ],
                            ],
                        ],
                    ],
                    'individual' => [
                        'reference' => 'Practitioner/'.$dokter->practitioner_id,
                        'display' => $dokter->nama_dokter,
                    ],
                ],
            ],
            'period' => [
                'start' => $periodStart,
            ],
            'location' => [
                [
                    'location' => [
                        'reference' => 'Location/'.$location->location_id,
                        'display' => $location->location_name,
                    ],
                ],
            ],
            'statusHistory' => [
                [
                    'status' => 'arrived',
                    'period' => [
                        'start' => $periodStart,
                    ],
                ],
            ],
            'serviceProvider' => [
                'reference' => 'Organization/'.$instansi->organization_id,
            ],
            'identifier' => [
                [
                    'system' => 'http://sys-ids.kemkes.go.id/encounter/'.$instansi->organization_id,
                    'value' => $nomorKunjungan,
                ],
            ],
        ];

        $url = $this->getBaseUrl().'/Encounter';
        $response = Http::withHeaders($this->getHeaders())->post($url, $body);

        if ($response->successful()) {
            $result = $response->json();
            $this->logSatusehatData(
                $instansi->organization_id,
                $result['id'] ?? null,
                $body,
                'Success',
                $nomorKunjungan,
                'Encounter',
                $pasien->satusehat_uuid,
                $result,
                null,
                $data['created_by'] ?? null
            );

            return $result;
        }

        $this->logSatusehatData(
            $instansi->organization_id,
            null,
            $body,
            'Failed',
            $nomorKunjungan,
            'Encounter',
            $pasien->satusehat_uuid,
            [],
            $response->body(),
            $data['created_by'] ?? null
        );
        throw new \Exception('Gagal create Encounter di SatuSehat: '.$response->body());
    }

    /**
     * Update Encounter to in-progress status.
     * PUT {baseUrl}/Encounter/{id}
     *
     * @param  array  $data  Expecting: pasien, dokter, location, nomor_kunjungan, period_start, period_end, arrived_start, arrived_end, inprogress_start, inprogress_end
     */
    public function updateEncounterInProgress(string $encounterUuid, array $data)
    {
        $instansi = MstInstansi::first();
        if (! $instansi) {
            throw new \Exception('Data profil klinik (mst_instansi) tidak ditemukan.');
        }

        $pasien = $data['pasien'];
        $dokter = $data['dokter'];
        $location = $data['location'];
        $nomorKunjungan = $data['nomor_kunjungan'] ?? '';

        $body = [
            'resourceType' => 'Encounter',
            'id' => $encounterUuid,
            'identifier' => [
                [
                    'system' => 'http://sys-ids.kemkes.go.id/encounter/'.$instansi->organization_id,
                    'value' => $nomorKunjungan,
                ],
            ],
            'status' => 'in-progress',
            'class' => [
                'system' => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
                'code' => 'AMB',
                'display' => 'ambulatory',
            ],
            'subject' => [
                'reference' => 'Patient/'.$pasien->satusehat_uuid,
                'display' => $pasien->nama_pasien,
            ],
            'participant' => [
                [
                    'type' => [
                        [
                            'coding' => [
                                [
                                    'system' => 'http://terminology.hl7.org/CodeSystem/v3-ParticipationType',
                                    'code' => 'ATND',
                                    'display' => 'attender',
                                ],
                            ],
                        ],
                    ],
                    'individual' => [
                        'reference' => 'Practitioner/'.$dokter->practitioner_id,
                        'display' => $dokter->nama_dokter,
                    ],
                ],
            ],
            'period' => [
                'start' => $this->formatUtcDateTime($data['period_start'] ?? null),
                'end' => $this->formatUtcDateTime($data['period_end'] ?? null),
            ],
            'location' => [
                [
                    'location' => [
                        'reference' => 'Location/'.$location->location_id,
                        'display' => $location->location_name,
                    ],
                ],
            ],
            'statusHistory' => [
                [
                    'status' => 'arrived',
                    'period' => [
                        'start' => $this->formatUtcDateTime($data['arrived_start'] ?? $data['period_start'] ?? null),
                        'end' => $this->formatUtcDateTime($data['arrived_end'] ?? $data['period_start'] ?? null),
                    ],
                ],
                [
                    'status' => 'in-progress',
                    'period' => [
                        'start' => $this->formatUtcDateTime($data['inprogress_start'] ?? $data['period_start'] ?? null),
                        'end' => $this->formatUtcDateTime($data['inprogress_end'] ?? $data['period_end'] ?? null),
                    ],
                ],
            ],
            'serviceProvider' => [
                'reference' => 'Organization/'.$instansi->organization_id,
            ],
        ];

        $url = $this->getBaseUrl().'/Encounter/'.$encounterUuid;
        $response = Http::withHeaders($this->getHeaders())->put($url, $body);

        if ($response->successful()) {
            $result = $response->json();
            $this->logSatusehatData(
                $instansi->organization_id,
                $encounterUuid,
                $body,
                'Success',
                $nomorKunjungan,
                'Encounter',
                $pasien->satusehat_uuid,
                $result,
                null,
                $data['created_by'] ?? null
            );

            return $result;
        }

        $this->logSatusehatData(
            $instansi->organization_id,
            $encounterUuid,
            $body,
            'Failed',
            $nomorKunjungan,
            'Encounter',
            $pasien->satusehat_uuid,
            [],
            $response->body(),
            $data['created_by'] ?? null
        );
        throw new \Exception('Gagal update Encounter (in-progress) di SatuSehat: '.$response->body());
    }

    /**
     * Update Encounter with discharge disposition (status tetap in-progress, tambah hospitalization).
     * PUT {baseUrl}/Encounter/{id}
     *
     * @param  array  $data  Expecting: pasien, dokter, location, nomor_kunjungan, period_start, period_end,
     *                       arrived_start, arrived_end, inprogress_start, inprogress_end,
     *                       discharge_code, discharge_display, discharge_text
     */
    public function updateEncounterDischargeDisposition(string $encounterUuid, array $data)
    {
        $instansi = MstInstansi::first();
        if (! $instansi) {
            throw new \Exception('Data profil klinik (mst_instansi) tidak ditemukan.');
        }

        $pasien = $data['pasien'];
        $dokter = $data['dokter'];
        $location = $data['location'];
        $nomorKunjungan = $data['nomor_kunjungan'] ?? '';

        $body = [
            'resourceType' => 'Encounter',
            'id' => $encounterUuid,
            'identifier' => [
                [
                    'system' => 'http://sys-ids.kemkes.go.id/encounter/'.$instansi->organization_id,
                    'value' => $nomorKunjungan,
                ],
            ],
            'status' => 'in-progress',
            'class' => [
                'system' => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
                'code' => 'AMB',
                'display' => 'ambulatory',
            ],
            'subject' => [
                'reference' => 'Patient/'.$pasien->satusehat_uuid,
                'display' => $pasien->nama_pasien,
            ],
            'participant' => [
                [
                    'type' => [
                        [
                            'coding' => [
                                [
                                    'system' => 'http://terminology.hl7.org/CodeSystem/v3-ParticipationType',
                                    'code' => 'ATND',
                                    'display' => 'attender',
                                ],
                            ],
                        ],
                    ],
                    'individual' => [
                        'reference' => 'Practitioner/'.$dokter->practitioner_id,
                        'display' => $dokter->nama_dokter,
                    ],
                ],
            ],
            'period' => [
                'start' => $this->formatUtcDateTime($data['period_start'] ?? null),
                'end' => $this->formatUtcDateTime($data['period_end'] ?? null),
            ],
            'location' => [
                [
                    'location' => [
                        'reference' => 'Location/'.$location->location_id,
                        'display' => $location->location_name,
                    ],
                ],
            ],
            'statusHistory' => [
                [
                    'status' => 'arrived',
                    'period' => [
                        'start' => $this->formatUtcDateTime($data['arrived_start'] ?? $data['period_start'] ?? null),
                        'end' => $this->formatUtcDateTime($data['arrived_end'] ?? $data['period_start'] ?? null),
                    ],
                ],
                [
                    'status' => 'in-progress',
                    'period' => [
                        'start' => $this->formatUtcDateTime($data['inprogress_start'] ?? $data['period_start'] ?? null),
                        'end' => $this->formatUtcDateTime($data['inprogress_end'] ?? $data['period_end'] ?? null),
                    ],
                ],
            ],
            'hospitalization' => [
                'dischargeDisposition' => [
                    'coding' => [
                        [
                            'system' => 'http://terminology.hl7.org/CodeSystem/discharge-disposition',
                            'code' => $data['discharge_code'] ?? 'home',
                            'display' => $data['discharge_display'] ?? 'Home',
                        ],
                    ],
                    'text' => $data['discharge_text'] ?? '',
                ],
            ],
            'serviceProvider' => [
                'reference' => 'Organization/'.$instansi->organization_id,
            ],
        ];

        $url = $this->getBaseUrl().'/Encounter/'.$encounterUuid;
        $response = Http::withHeaders($this->getHeaders())->put($url, $body);

        if ($response->successful()) {
            $result = $response->json();
            $this->logSatusehatData(
                $instansi->organization_id,
                $encounterUuid,
                $body,
                'Success',
                $nomorKunjungan,
                'Encounter',
                $pasien->satusehat_uuid,
                $result,
                null,
                $data['created_by'] ?? null
            );

            return $result;
        }

        $this->logSatusehatData(
            $instansi->organization_id,
            $encounterUuid,
            $body,
            'Failed',
            $nomorKunjungan,
            'Encounter',
            $pasien->satusehat_uuid,
            [],
            $response->body(),
            $data['created_by'] ?? null
        );
        throw new \Exception('Gagal update Encounter (discharge disposition) di SatuSehat: '.$response->body());
    }

    /**
     * Update Encounter to finished status (include diagnosis).
     * PUT {baseUrl}/Encounter/{id}
     *
     * @param  array  $data  Expecting: pasien, dokter, location, nomor_kunjungan, period_start, period_end,
     *                       arrived_start, arrived_end, inprogress_start, inprogress_end, finished_start, finished_end,
     *                       diagnosis (array of [condition_uuid, condition_display, rank])
     */
    public function updateEncounterFinished(string $encounterUuid, array $data)
    {
        $instansi = MstInstansi::first();
        if (! $instansi) {
            throw new \Exception('Data profil klinik (mst_instansi) tidak ditemukan.');
        }

        $pasien = $data['pasien'];
        $dokter = $data['dokter'];
        $location = $data['location'];
        $nomorKunjungan = $data['nomor_kunjungan'] ?? '';

        // Build diagnosis array
        $diagnosisArray = [];
        if (! empty($data['diagnosis'])) {
            foreach ($data['diagnosis'] as $idx => $diag) {
                $diagnosisArray[] = [
                    'condition' => [
                        'reference' => 'Condition/'.($diag['condition_uuid'] ?? ''),
                        'display' => $diag['condition_display'] ?? '',
                    ],
                    'use' => [
                        'coding' => [
                            [
                                'system' => 'http://terminology.hl7.org/CodeSystem/diagnosis-role',
                                'code' => 'DD',
                                'display' => 'Discharge diagnosis',
                            ],
                        ],
                    ],
                    'rank' => $diag['rank'] ?? ($idx + 1),
                ];
            }
        }

        $body = [
            'resourceType' => 'Encounter',
            'id' => $encounterUuid,
            'identifier' => [
                [
                    'system' => 'http://sys-ids.kemkes.go.id/encounter/'.$instansi->organization_id,
                    'value' => $nomorKunjungan,
                ],
            ],
            'status' => 'finished',
            'class' => [
                'system' => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
                'code' => 'AMB',
                'display' => 'ambulatory',
            ],
            'subject' => [
                'reference' => 'Patient/'.$pasien->satusehat_uuid,
                'display' => $pasien->nama_pasien,
            ],
            'participant' => [
                [
                    'type' => [
                        [
                            'coding' => [
                                [
                                    'system' => 'http://terminology.hl7.org/CodeSystem/v3-ParticipationType',
                                    'code' => 'ATND',
                                    'display' => 'attender',
                                ],
                            ],
                        ],
                    ],
                    'individual' => [
                        'reference' => 'Practitioner/'.$dokter->practitioner_id,
                        'display' => $dokter->nama_dokter,
                    ],
                ],
            ],
            'period' => [
                'start' => $this->formatUtcDateTime($data['period_start'] ?? null),
                'end' => $this->formatUtcDateTime($data['period_end'] ?? null),
            ],
            'location' => [
                [
                    'location' => [
                        'reference' => 'Location/'.$location->location_id,
                        'display' => $location->location_name,
                    ],
                ],
            ],
            'statusHistory' => [
                [
                    'status' => 'arrived',
                    'period' => [
                        'start' => $this->formatUtcDateTime($data['arrived_start'] ?? $data['period_start'] ?? null),
                        'end' => $this->formatUtcDateTime($data['arrived_end'] ?? $data['period_start'] ?? null),
                    ],
                ],
                [
                    'status' => 'in-progress',
                    'period' => [
                        'start' => $this->formatUtcDateTime($data['inprogress_start'] ?? $data['period_start'] ?? null),
                        'end' => $this->formatUtcDateTime($data['inprogress_end'] ?? $data['period_end'] ?? null),
                    ],
                ],
                [
                    'status' => 'finished',
                    'period' => [
                        'start' => $this->formatUtcDateTime($data['finished_start'] ?? $data['period_end'] ?? null),
                        'end' => $this->formatUtcDateTime($data['finished_end'] ?? $data['period_end'] ?? null),
                    ],
                ],
            ],
            'serviceProvider' => [
                'reference' => 'Organization/'.$instansi->organization_id,
            ],
        ];

        // Tambahkan diagnosis jika ada
        if (! empty($diagnosisArray)) {
            $body['diagnosis'] = $diagnosisArray;
        }

        $url = $this->getBaseUrl().'/Encounter/'.$encounterUuid;
        $response = Http::withHeaders($this->getHeaders())->put($url, $body);

        if ($response->successful()) {
            $result = $response->json();
            $this->logSatusehatData(
                $instansi->organization_id,
                $encounterUuid,
                $body,
                'Success',
                $nomorKunjungan,
                'Encounter',
                $pasien->satusehat_uuid,
                $result,
                null,
                $data['created_by'] ?? null
            );

            return $result;
        }

        $this->logSatusehatData(
            $instansi->organization_id,
            $encounterUuid,
            $body,
            'Failed',
            $nomorKunjungan,
            'Encounter',
            $pasien->satusehat_uuid,
            [],
            $response->body(),
            $data['created_by'] ?? null
        );
        throw new \Exception('Gagal update Encounter (finished) di SatuSehat: '.$response->body());
    }

    // ==========================================
    // CONDITION RESOURCE
    // ==========================================

    /**
     * Search Conditions by Patient Subject UUID.
     * GET {baseUrl}/Condition?subject={subjectUuid}
     *
     * @param  string  $subjectUuid  SatuSehat UUID pasien (dari mst_pasien.satusehat_uuid)
     */
    public function searchConditionBySubject(string $subjectUuid)
    {
        $url = $this->getBaseUrl().'/Condition';
        $params = ['subject' => $subjectUuid];

        $response = Http::withHeaders($this->getHeaders())->get($url, $params);

        if ($response->successful() && ! empty($response->json()['entry'])) {
            return $response->json()['entry'];
        }

        return null;
    }

    /**
     * Search Conditions by Patient Subject UUID and Encounter UUID.
     * GET {baseUrl}/Condition?subject={subjectUuid}&encounter={encounterUuid}
     *
     * @param  string  $subjectUuid  SatuSehat UUID pasien (dari mst_pasien.satusehat_uuid)
     * @param  string  $encounterUuid  UUID Encounter
     */
    public function searchConditionBySubjectAndEncounter(string $subjectUuid, string $encounterUuid)
    {
        $url = $this->getBaseUrl().'/Condition';
        $params = [
            'subject' => $subjectUuid,
            'encounter' => $encounterUuid,
        ];

        $response = Http::withHeaders($this->getHeaders())->get($url, $params);

        if ($response->successful() && ! empty($response->json()['entry'])) {
            return $response->json()['entry'];
        }

        return null;
    }

    /**
     * Search Conditions by Encounter UUID.
     * GET {baseUrl}/Condition?encounter={encounterUuid}
     *
     * @param  string  $encounterUuid  UUID Encounter
     */
    public function searchConditionByEncounter(string $encounterUuid)
    {
        $url = $this->getBaseUrl().'/Condition';
        $params = ['encounter' => $encounterUuid];

        $response = Http::withHeaders($this->getHeaders())->get($url, $params);

        if ($response->successful() && ! empty($response->json()['entry'])) {
            return $response->json()['entry'];
        }

        return null;
    }

    /**
     * Get a specific Condition by its UUID (path variable).
     * GET {baseUrl}/Condition/{id}
     *
     * @param  string  $conditionUuid  UUID Condition
     */
    public function getConditionById(string $conditionUuid)
    {
        $url = $this->getBaseUrl().'/Condition/'.$conditionUuid;

        $response = Http::withHeaders($this->getHeaders())->get($url);

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    /**
     * Create a new Condition (Diagnosis) for a patient who was examined.
     * POST {baseUrl}/Condition
     *
     * Uses ICD-10 coding system for the diagnosis code.
     *
     * @param  array  $data  Expecting keys:
     *                       - pasien (MstPasien model)
     *                       - encounter_uuid (string)
     *                       - encounter_display (string, e.g. "Kunjungan ... di hari ...")
     *                       - diagnosis_code (string, ICD-10 code, e.g. "K35.8")
     *                       - diagnosis_display (string, e.g. "Acute appendicitis, other and unspecified")
     *                       - clinical_status (string, default "active") — active, recurrence, relapse, etc.
     *                       - clinical_display (string, default "Active")
     */
    public function createCondition(array $data)
    {
        $pasien = $data['pasien']; // MstPasien model
        $instansi = MstInstansi::first();

        $clinicalCode = $data['clinical_status'] ?? 'active';
        $clinicalDisplay = $data['clinical_display'] ?? 'Active';

        $body = [
            'resourceType' => 'Condition',
            'clinicalStatus' => [
                'coding' => [
                    [
                        'system' => 'http://terminology.hl7.org/CodeSystem/condition-clinical',
                        'code' => $clinicalCode,
                        'display' => $clinicalDisplay,
                    ],
                ],
            ],
            'category' => [
                [
                    'coding' => [
                        [
                            'system' => 'http://terminology.hl7.org/CodeSystem/condition-category',
                            'code' => 'encounter-diagnosis',
                            'display' => 'Encounter Diagnosis',
                        ],
                    ],
                ],
            ],
            'code' => [
                'coding' => [
                    [
                        'system' => 'http://hl7.org/fhir/sid/icd-10',
                        'code' => $data['diagnosis_code'],
                        'display' => $data['diagnosis_display'],
                    ],
                ],
            ],
            'subject' => [
                'reference' => 'Patient/'.$pasien->satusehat_uuid,
                'display' => $pasien->nama_pasien,
            ],
            'encounter' => [
                'reference' => 'Encounter/'.$data['encounter_uuid'],
                'display' => $data['encounter_display'] ?? '',
            ],
        ];

        $url = $this->getBaseUrl().'/Condition';
        $response = Http::withHeaders($this->getHeaders())->post($url, $body);

        if ($response->successful()) {
            $result = $response->json();
            $this->logSatusehatData(
                $instansi?->organization_id,
                $result['id'] ?? null,
                $body,
                'Success',
                $data['nomor_kunjungan'] ?? null,
                'Condition',
                $pasien->satusehat_uuid,
                $result,
                null,
                $data['created_by'] ?? null
            );

            return $result;
        }

        $this->logSatusehatData(
            $instansi?->organization_id,
            null,
            $body,
            'Failed',
            $data['nomor_kunjungan'] ?? null,
            'Condition',
            $pasien->satusehat_uuid,
            [],
            $response->body(),
            $data['created_by'] ?? null
        );
        throw new \Exception('Gagal create Condition di SatuSehat: '.$response->body());
    }

    /**
     * Create a Condition for a patient who came but left the clinic healthy (Patient's condition stable).
     * POST {baseUrl}/Condition
     *
     * Uses SNOMED CT coding system with code "359746009" (Patient's condition stable).
     *
     * @param  array  $data  Expecting keys:
     *                       - pasien (MstPasien model)
     *                       - encounter_uuid (string)
     *                       - encounter_display (string)
     */
    public function createConditionStable(array $data)
    {
        $pasien = $data['pasien']; // MstPasien model
        $instansi = MstInstansi::first();

        $body = [
            'resourceType' => 'Condition',
            'clinicalStatus' => [
                'coding' => [
                    [
                        'system' => 'http://terminology.hl7.org/CodeSystem/condition-clinical',
                        'code' => 'active',
                        'display' => 'Active',
                    ],
                ],
            ],
            'category' => [
                [
                    'coding' => [
                        [
                            'system' => 'http://terminology.hl7.org/CodeSystem/condition-category',
                            'code' => 'encounter-diagnosis',
                            'display' => 'Encounter Diagnosis',
                        ],
                    ],
                ],
            ],
            'code' => [
                'coding' => [
                    [
                        'system' => 'http://snomed.info/sct',
                        'code' => '359746009',
                        'display' => "Patient's condition stable",
                    ],
                ],
            ],
            'subject' => [
                'reference' => 'Patient/'.$pasien->satusehat_uuid,
                'display' => $pasien->nama_pasien,
            ],
            'encounter' => [
                'reference' => 'Encounter/'.$data['encounter_uuid'],
                'display' => $data['encounter_display'] ?? '',
            ],
        ];

        $url = $this->getBaseUrl().'/Condition';
        $response = Http::withHeaders($this->getHeaders())->post($url, $body);

        if ($response->successful()) {
            $result = $response->json();
            $this->logSatusehatData(
                $instansi?->organization_id,
                $result['id'] ?? null,
                $body,
                'Success',
                $data['nomor_kunjungan'] ?? null,
                'Condition',
                $pasien->satusehat_uuid,
                $result,
                null,
                $data['created_by'] ?? null
            );

            return $result;
        }

        $this->logSatusehatData(
            $instansi?->organization_id,
            null,
            $body,
            'Failed',
            $data['nomor_kunjungan'] ?? null,
            'Condition',
            $pasien->satusehat_uuid,
            [],
            $response->body(),
            $data['created_by'] ?? null
        );
        throw new \Exception('Gagal create Condition (stable) di SatuSehat: '.$response->body());
    }

    /**
     * Update an existing Condition in SatuSehat.
     * PUT {baseUrl}/Condition/{id}
     *
     * @param  string  $conditionUuid  UUID Condition yang akan di-update
     * @param  array  $data  Expecting keys:
     *                       - pasien (MstPasien model)
     *                       - encounter_uuid (string)
     *                       - encounter_display (string)
     *                       - diagnosis_code (string, ICD-10 code)
     *                       - diagnosis_display (string)
     *                       - clinical_status (string, e.g. "remission", "active", "resolved")
     *                       - clinical_display (string, e.g. "Remission", "Active", "Resolved")
     */
    public function updateCondition(string $conditionUuid, array $data)
    {
        $pasien = $data['pasien']; // MstPasien model
        $instansi = MstInstansi::first();

        $clinicalCode = $data['clinical_status'] ?? 'active';
        $clinicalDisplay = $data['clinical_display'] ?? 'Active';

        $body = [
            'resourceType' => 'Condition',
            'id' => $conditionUuid,
            'clinicalStatus' => [
                'coding' => [
                    [
                        'system' => 'http://terminology.hl7.org/CodeSystem/condition-clinical',
                        'code' => $clinicalCode,
                        'display' => $clinicalDisplay,
                    ],
                ],
            ],
            'category' => [
                [
                    'coding' => [
                        [
                            'system' => 'http://terminology.hl7.org/CodeSystem/condition-category',
                            'code' => 'encounter-diagnosis',
                            'display' => 'Encounter Diagnosis',
                        ],
                    ],
                ],
            ],
            'code' => [
                'coding' => [
                    [
                        'system' => 'http://hl7.org/fhir/sid/icd-10',
                        'code' => $data['diagnosis_code'],
                        'display' => $data['diagnosis_display'],
                    ],
                ],
            ],
            'subject' => [
                'reference' => 'Patient/'.$pasien->satusehat_uuid,
                'display' => $pasien->nama_pasien,
            ],
            'encounter' => [
                'reference' => 'Encounter/'.$data['encounter_uuid'],
                'display' => $data['encounter_display'] ?? '',
            ],
        ];

        $url = $this->getBaseUrl().'/Condition/'.$conditionUuid;
        $response = Http::withHeaders($this->getHeaders())->put($url, $body);

        if ($response->successful()) {
            $result = $response->json();
            $this->logSatusehatData(
                $instansi?->organization_id,
                $conditionUuid,
                $body,
                'Success',
                $data['nomor_kunjungan'] ?? null,
                'Condition',
                $pasien->satusehat_uuid,
                $result,
                null,
                $data['created_by'] ?? null
            );

            return $result;
        }

        $this->logSatusehatData(
            $instansi?->organization_id,
            $conditionUuid,
            $body,
            'Failed',
            $data['nomor_kunjungan'] ?? null,
            'Condition',
            $pasien->satusehat_uuid,
            [],
            $response->body(),
            $data['created_by'] ?? null
        );
        throw new \Exception('Gagal update Condition di SatuSehat: '.$response->body());
    }

    // ==========================================
    // OBSERVATION RESOURCE
    // ==========================================

    /**
     * Mapping tipe vital sign ke LOINC code, display, unit, dan UCUM code.
     * Dipakai oleh createObservation dan createObservationAllVitalSigns.
     *
     * Key = identifier internal aplikasi
     * Setiap entry memiliki: loinc_code, display, unit, ucum_code
     *
     * Catatan: blood_pressure memiliki 2 komponen (systolic & diastolic),
     * sehingga ditangani secara khusus di createObservationBloodPressure().
     */
    const VITAL_SIGN_MAP = [
        'heart_rate' => [
            'loinc_code' => '8867-4',
            'display' => 'Heart rate',
            'unit' => 'beats/minute',
            'ucum_code' => '/min',
        ],
        'respiratory_rate' => [
            'loinc_code' => '9279-1',
            'display' => 'Respiratory rate',
            'unit' => 'breaths/minute',
            'ucum_code' => '/min',
        ],
        'body_temperature' => [
            'loinc_code' => '8310-5',
            'display' => 'Body temperature',
            'unit' => 'C',
            'ucum_code' => 'Cel',
        ],
        'body_weight' => [
            'loinc_code' => '29463-7',
            'display' => 'Body weight',
            'unit' => 'kg',
            'ucum_code' => 'kg',
        ],
        'body_height' => [
            'loinc_code' => '8302-2',
            'display' => 'Body height',
            'unit' => 'cm',
            'ucum_code' => 'cm',
        ],
        'body_mass_index' => [
            'loinc_code' => '39156-5',
            'display' => 'Body mass index (BMI)',
            'unit' => 'kg/m2',
            'ucum_code' => 'kg/m2',
        ],
        'head_circumference' => [
            'loinc_code' => '9843-4',
            'display' => 'Head Occipital-frontal circumference',
            'unit' => 'cm',
            'ucum_code' => 'cm',
        ],
        'pulse_oximetry' => [
            'loinc_code' => '2708-6',
            'display' => 'Oxygen saturation in Arterial blood',
            'unit' => '%',
            'ucum_code' => '%',
        ],
        // Blood pressure ditangani khusus (component systolic + diastolic)
        'blood_pressure' => [
            'loinc_code' => '85354-9',
            'display' => 'Blood pressure panel with all children optional',
            'systolic' => [
                'loinc_code' => '8480-6',
                'display' => 'Systolic blood pressure',
                'unit' => 'mm[Hg]',
                'ucum_code' => 'mm[Hg]',
            ],
            'diastolic' => [
                'loinc_code' => '8462-4',
                'display' => 'Diastolic blood pressure',
                'unit' => 'mm[Hg]',
                'ucum_code' => 'mm[Hg]',
            ],
        ],
    ];

    /**
     * Search Observations by Patient Subject UUID.
     * GET {baseUrl}/Observation?subject={subjectUuid}
     *
     * @param  string  $subjectUuid  SatuSehat UUID pasien (dari mst_pasien.satusehat_uuid)
     */
    public function searchObservationBySubject(string $subjectUuid)
    {
        $url = $this->getBaseUrl().'/Observation';
        $params = ['subject' => $subjectUuid];

        $response = Http::withHeaders($this->getHeaders())->get($url, $params);

        if ($response->successful() && ! empty($response->json()['entry'])) {
            return $response->json()['entry'];
        }

        return null;
    }

    /**
     * Search Observations by Patient Subject UUID and Encounter UUID.
     * GET {baseUrl}/Observation?subject={subjectUuid}&encounter={encounterUuid}
     *
     * @param  string  $subjectUuid  SatuSehat UUID pasien (dari mst_pasien.satusehat_uuid)
     * @param  string  $encounterUuid  UUID Encounter
     */
    public function searchObservationBySubjectAndEncounter(string $subjectUuid, string $encounterUuid)
    {
        $url = $this->getBaseUrl().'/Observation';
        $params = [
            'subject' => $subjectUuid,
            'encounter' => $encounterUuid,
        ];

        $response = Http::withHeaders($this->getHeaders())->get($url, $params);

        if ($response->successful() && ! empty($response->json()['entry'])) {
            return $response->json()['entry'];
        }

        return null;
    }

    /**
     * Search Observations by Encounter UUID.
     * GET {baseUrl}/Observation?encounter={encounterUuid}
     *
     * @param  string  $encounterUuid  UUID Encounter
     */
    public function searchObservationByEncounter(string $encounterUuid)
    {
        $url = $this->getBaseUrl().'/Observation';
        $params = ['encounter' => $encounterUuid];

        $response = Http::withHeaders($this->getHeaders())->get($url, $params);

        if ($response->successful() && ! empty($response->json()['entry'])) {
            return $response->json()['entry'];
        }

        return null;
    }

    /**
     * Get a specific Observation by its UUID (path variable).
     * GET {baseUrl}/Observation/{id}
     *
     * @param  string  $observationUuid  UUID Observation
     */
    public function getObservationById(string $observationUuid)
    {
        $url = $this->getBaseUrl().'/Observation/'.$observationUuid;

        $response = Http::withHeaders($this->getHeaders())->get($url);

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    /**
     * Create a single Observation (vital signs) resource in SatuSehat.
     * POST {baseUrl}/Observation
     *
     * Untuk vital sign biasa (non blood pressure) yang menggunakan valueQuantity.
     *
     * @param  array  $data  Expecting keys:
     *                       - pasien (MstPasien model)
     *                       - dokter (MstDokter model) — used as performer
     *                       - encounter_uuid (string)
     *                       - encounter_display (string)
     *                       - vital_type (string) — key dari VITAL_SIGN_MAP (e.g. 'heart_rate', 'body_temperature')
     *                       - value (numeric) — nilai pengukuran
     *                       - effective_date (string, Y-m-d, opsional, default now)
     *                       - issued (string, ISO8601, opsional, default now)
     *
     * Atau parameter manual (jika vital_type tidak diisi):
     *   - observation_code, observation_display, unit, unit_code
     */
    public function createObservation(array $data)
    {
        $pasien = $data['pasien']; // MstPasien model
        $dokter = $data['dokter']; // MstDokter model
        $instansi = MstInstansi::first();

        // Resolve dari VITAL_SIGN_MAP jika vital_type diberikan
        if (! empty($data['vital_type']) && isset(self::VITAL_SIGN_MAP[$data['vital_type']])) {
            $map = self::VITAL_SIGN_MAP[$data['vital_type']];
            $observationCode = $map['loinc_code'];
            $observationDisplay = $map['display'];
            $unit = $map['unit'];
            $unitCode = $map['ucum_code'];
        } else {
            // Fallback: manual parameter
            $observationCode = $data['observation_code'];
            $observationDisplay = $data['observation_display'];
            $unit = $data['unit'];
            $unitCode = $data['unit_code'];
        }

        $body = [
            'resourceType' => 'Observation',
            'status' => 'final',
            'category' => [
                [
                    'coding' => [
                        [
                            'system' => 'http://terminology.hl7.org/CodeSystem/observation-category',
                            'code' => 'vital-signs',
                            'display' => 'Vital Signs',
                        ],
                    ],
                ],
            ],
            'code' => [
                'coding' => [
                    [
                        'system' => 'http://loinc.org',
                        'code' => $observationCode,
                        'display' => $observationDisplay,
                    ],
                ],
            ],
            'subject' => [
                'reference' => 'Patient/'.$pasien->satusehat_uuid,
            ],
            'performer' => [
                [
                    'reference' => 'Practitioner/'.$dokter->practitioner_id,
                ],
            ],
            'encounter' => [
                'reference' => 'Encounter/'.$data['encounter_uuid'],
                'display' => $data['encounter_display'] ?? '',
            ],
            'effectiveDateTime' => $this->formatUtcDateTime($data['effective_date'] ?? null),
            'issued' => $this->formatUtcDateTime($data['issued'] ?? null),
            'valueQuantity' => [
                'value' => (float) $data['value'],
                'unit' => $unit,
                'system' => 'http://unitsofmeasure.org',
                'code' => $unitCode,
            ],
        ];

        $url = $this->getBaseUrl().'/Observation';
        $response = Http::withHeaders($this->getHeaders())->post($url, $body);

        if ($response->successful()) {
            $result = $response->json();
            $this->logSatusehatData(
                $instansi?->organization_id,
                $result['id'] ?? null,
                $body,
                'Success',
                $data['nomor_kunjungan'] ?? null,
                'Observation',
                $pasien->satusehat_uuid,
                $result,
                null,
                $data['created_by'] ?? null
            );

            return $result;
        }

        $this->logSatusehatData(
            $instansi?->organization_id,
            null,
            $body,
            'Failed',
            $data['nomor_kunjungan'] ?? null,
            'Observation',
            $pasien->satusehat_uuid,
            [],
            $response->body(),
            $data['created_by'] ?? null
        );
        throw new \Exception('Gagal create Observation di SatuSehat: '.$response->body());
    }

    /**
     * Create an Observation for Blood Pressure (FHIR component structure).
     * POST {baseUrl}/Observation
     *
     * Blood Pressure menggunakan "component" (systolic + diastolic),
     * bukan "valueQuantity" tunggal.
     *
     * @param  array  $data  Expecting keys:
     *                       - pasien (MstPasien model)
     *                       - dokter (MstDokter model)
     *                       - encounter_uuid (string)
     *                       - encounter_display (string)
     *                       - systolic (numeric) — tekanan sistolik (mmHg)
     *                       - diastolic (numeric) — tekanan diastolik (mmHg)
     *                       - effective_date (string, Y-m-d, opsional)
     *                       - issued (string, ISO8601, opsional)
     */
    public function createObservationBloodPressure(array $data)
    {
        $pasien = $data['pasien'];
        $dokter = $data['dokter'];
        $instansi = MstInstansi::first();
        $bp = self::VITAL_SIGN_MAP['blood_pressure'];

        $body = [
            'resourceType' => 'Observation',
            'status' => 'final',
            'category' => [
                [
                    'coding' => [
                        [
                            'system' => 'http://terminology.hl7.org/CodeSystem/observation-category',
                            'code' => 'vital-signs',
                            'display' => 'Vital Signs',
                        ],
                    ],
                ],
            ],
            'code' => [
                'coding' => [
                    [
                        'system' => 'http://loinc.org',
                        'code' => $bp['loinc_code'],
                        'display' => $bp['display'],
                    ],
                ],
            ],
            'subject' => [
                'reference' => 'Patient/'.$pasien->satusehat_uuid,
            ],
            'performer' => [
                [
                    'reference' => 'Practitioner/'.$dokter->practitioner_id,
                ],
            ],
            'encounter' => [
                'reference' => 'Encounter/'.$data['encounter_uuid'],
                'display' => $data['encounter_display'] ?? '',
            ],
            'effectiveDateTime' => $this->formatUtcDateTime($data['effective_date'] ?? null),
            'issued' => $this->formatUtcDateTime($data['issued'] ?? null),
            'component' => [
                [
                    'code' => [
                        'coding' => [
                            [
                                'system' => 'http://loinc.org',
                                'code' => $bp['systolic']['loinc_code'],
                                'display' => $bp['systolic']['display'],
                            ],
                        ],
                    ],
                    'valueQuantity' => [
                        'value' => (float) $data['systolic'],
                        'unit' => $bp['systolic']['unit'],
                        'system' => 'http://unitsofmeasure.org',
                        'code' => $bp['systolic']['ucum_code'],
                    ],
                ],
                [
                    'code' => [
                        'coding' => [
                            [
                                'system' => 'http://loinc.org',
                                'code' => $bp['diastolic']['loinc_code'],
                                'display' => $bp['diastolic']['display'],
                            ],
                        ],
                    ],
                    'valueQuantity' => [
                        'value' => (float) $data['diastolic'],
                        'unit' => $bp['diastolic']['unit'],
                        'system' => 'http://unitsofmeasure.org',
                        'code' => $bp['diastolic']['ucum_code'],
                    ],
                ],
            ],
        ];

        $url = $this->getBaseUrl().'/Observation';
        $response = Http::withHeaders($this->getHeaders())->post($url, $body);

        if ($response->successful()) {
            $result = $response->json();
            $this->logSatusehatData(
                $instansi?->organization_id,
                $result['id'] ?? null,
                $body,
                'Success',
                $data['nomor_kunjungan'] ?? null,
                'Observation',
                $pasien->satusehat_uuid,
                $result,
                null,
                $data['created_by'] ?? null
            );

            return $result;
        }

        $this->logSatusehatData(
            $instansi?->organization_id,
            null,
            $body,
            'Failed',
            $data['nomor_kunjungan'] ?? null,
            'Observation',
            $pasien->satusehat_uuid,
            [],
            $response->body(),
            $data['created_by'] ?? null
        );
        throw new \Exception('Gagal create Observation (Blood Pressure) di SatuSehat: '.$response->body());
    }

    /**
     * Kirim semua Observation vital signs dari data pendaftaran pasien.
     *
     * Method ini membaca field vital sign dari TrxPendaftaran
     * dan mengirim setiap tanda vital yang terisi sebagai Observation terpisah.
     *
     * Mapping field TrxPendaftaran ke tipe vital sign:
     *   - tekanan_darah (format "120/80") → Blood Pressure (systolic + diastolic)
     *   - nadi                            → Heart Rate
     *   - suhu                            → Body Temperature
     *   - berat_badan                     → Body Weight
     *   - tinggi_badan                    → Body Height
     *   - Auto-hitung BMI jika BB dan TB terisi
     *
     * @param  array  $data  Expecting keys:
     *                       - pendaftaran (TrxPendaftaran model, sudah di-load beserta relasi pasien & dokter)
     *                       - encounter_uuid (string)
     *                       - encounter_display_prefix (string, opsional, e.g. "Pemeriksaan Fisik")
     *                       - effective_date (string, Y-m-d, opsional)
     *                       - issued (string, ISO8601, opsional)
     * @return array Hasil dari setiap Observation yang berhasil dibuat
     */
    public function createObservationAllVitalSigns(array $data): array
    {
        $pendaftaran = $data['pendaftaran'];
        $pasien = $pendaftaran->pasien;
        $dokter = $pendaftaran->dokter;
        $retryVitalTypes = $data['retry_vital_types'] ?? []; // LOINC codes untuk diretry

        if (! $pasien || ! $pasien->satusehat_uuid) {
            throw new \Exception('Pasien belum memiliki SatuSehat UUID.');
        }
        if (! $dokter || ! $dokter->practitioner_id) {
            throw new \Exception('Dokter belum memiliki Practitioner ID SatuSehat.');
        }

        $encounterUuid = $data['encounter_uuid'];
        $displayPrefix = $data['encounter_display_prefix'] ?? 'Pemeriksaan Fisik';
        $effectiveDate = $this->formatUtcDateTime($data['effective_date'] ?? $pendaftaran->created_at ?? null);
        $issued = $this->formatUtcDateTime($data['issued'] ?? $pendaftaran->created_at ?? null);

        // Helper: cek apakah LOINC code ada di list retry
        $shouldProcess = function (string $vitalType, ?string $loincCode = null) use ($retryVitalTypes): bool {
            if (empty($retryVitalTypes)) {
                return true; // Jika tidak ada filter, proses semua
            }
            if ($vitalType === 'blood_pressure' && in_array('blood_pressure', $retryVitalTypes)) {
                return true;
            }
            if ($loincCode && in_array($loincCode, $retryVitalTypes)) {
                return true;
            }

            return false;
        };

        $results = [];
        $baseData = [
            'pasien' => $pasien,
            'dokter' => $dokter,
            'nomor_kunjungan' => $pendaftaran->nomor_kunjungan,
            'encounter_uuid' => $encounterUuid,
            'effective_date' => $effectiveDate,
            'issued' => $issued,
            'created_by' => $data['created_by'] ?? null,
        ];

        // 1. Blood Pressure (tekanan_darah format: "120/80")
        if (! empty($pendaftaran->tekanan_darah) && $shouldProcess('blood_pressure')) {
            $parts = explode('/', $pendaftaran->tekanan_darah);
            if (count($parts) === 2 && is_numeric(trim($parts[0])) && is_numeric(trim($parts[1]))) {
                try {
                    $results['blood_pressure'] = $this->createObservationBloodPressure(array_merge($baseData, [
                        'encounter_display' => "{$displayPrefix} Tekanan Darah {$pasien->nama_pasien}",
                        'systolic' => (float) trim($parts[0]),
                        'diastolic' => (float) trim($parts[1]),
                    ]));
                } catch (\Exception $e) {
                    Log::warning('Observation Blood Pressure gagal: '.$e->getMessage());
                    $results['blood_pressure'] = ['error' => $e->getMessage()];
                }
            }
        }

        // 2. Heart Rate (nadi)
        if (! empty($pendaftaran->nadi) && is_numeric($pendaftaran->nadi) && $shouldProcess('heart_rate', '8867-4')) {
            try {
                $results['heart_rate'] = $this->createObservation(array_merge($baseData, [
                    'vital_type' => 'heart_rate',
                    'value' => (float) $pendaftaran->nadi,
                    'encounter_display' => "{$displayPrefix} Nadi {$pasien->nama_pasien}",
                ]));
            } catch (\Exception $e) {
                Log::warning('Observation Heart Rate gagal: '.$e->getMessage());
                $results['heart_rate'] = ['error' => $e->getMessage()];
            }
        }

        // 3. Body Temperature (suhu)
        if (! empty($pendaftaran->suhu) && is_numeric($pendaftaran->suhu) && $shouldProcess('body_temperature', '8310-5')) {
            try {
                $results['body_temperature'] = $this->createObservation(array_merge($baseData, [
                    'vital_type' => 'body_temperature',
                    'value' => (float) $pendaftaran->suhu,
                    'encounter_display' => "{$displayPrefix} Suhu Tubuh {$pasien->nama_pasien}",
                ]));
            } catch (\Exception $e) {
                Log::warning('Observation Body Temperature gagal: '.$e->getMessage());
                $results['body_temperature'] = ['error' => $e->getMessage()];
            }
        }

        // 4. Body Weight (berat_badan)
        if (! empty($pendaftaran->berat_badan) && is_numeric($pendaftaran->berat_badan) && $shouldProcess('body_weight', '29463-7')) {
            try {
                $results['body_weight'] = $this->createObservation(array_merge($baseData, [
                    'vital_type' => 'body_weight',
                    'value' => (float) $pendaftaran->berat_badan,
                    'encounter_display' => "{$displayPrefix} Berat Badan {$pasien->nama_pasien}",
                ]));
            } catch (\Exception $e) {
                Log::warning('Observation Body Weight gagal: '.$e->getMessage());
                $results['body_weight'] = ['error' => $e->getMessage()];
            }
        }

        // 5. Body Height (tinggi_badan)
        if (! empty($pendaftaran->tinggi_badan) && is_numeric($pendaftaran->tinggi_badan) && $shouldProcess('body_height', '8302-2')) {
            try {
                $results['body_height'] = $this->createObservation(array_merge($baseData, [
                    'vital_type' => 'body_height',
                    'value' => (float) $pendaftaran->tinggi_badan,
                    'encounter_display' => "{$displayPrefix} Tinggi Badan {$pasien->nama_pasien}",
                ]));
            } catch (\Exception $e) {
                Log::warning('Observation Body Height gagal: '.$e->getMessage());
                $results['body_height'] = ['error' => $e->getMessage()];
            }
        }

        // 6. BMI (auto-hitung dari berat_badan & tinggi_badan)
        if (
            ! empty($pendaftaran->berat_badan) && ! empty($pendaftaran->tinggi_badan)
            && is_numeric($pendaftaran->berat_badan) && is_numeric($pendaftaran->tinggi_badan)
            && (float) $pendaftaran->tinggi_badan > 0
            && $shouldProcess('body_mass_index', '39156-5')
        ) {
            $heightM = (float) $pendaftaran->tinggi_badan / 100;
            $bmi = round((float) $pendaftaran->berat_badan / ($heightM * $heightM), 1);

            try {
                $results['body_mass_index'] = $this->createObservation(array_merge($baseData, [
                    'vital_type' => 'body_mass_index',
                    'value' => $bmi,
                    'encounter_display' => "{$displayPrefix} BMI {$pasien->nama_pasien}",
                ]));
            } catch (\Exception $e) {
                Log::warning('Observation BMI gagal: '.$e->getMessage());
                $results['body_mass_index'] = ['error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Update an existing Observation in SatuSehat.
     * PUT {baseUrl}/Observation/{id}
     *
     * @param  string  $observationUuid  UUID Observation yang akan di-update
     * @param  array  $data  Expecting keys:
     *                       - pasien (MstPasien model)
     *                       - encounter_uuid (string)
     *                       - encounter_display (string)
     *                       - vital_type (string, opsional) — key dari VITAL_SIGN_MAP
     *                       - value (numeric)
     *                       - effective_date (string, Y-m-d, opsional)
     *                       - issued (string, ISO8601, opsional)
     *
     * Atau parameter manual (jika vital_type tidak diisi):
     *   - observation_code, observation_display, unit, unit_code
     */
    public function updateObservation(string $observationUuid, array $data)
    {
        $pasien = $data['pasien']; // MstPasien model
        $instansi = MstInstansi::first();

        // Resolve dari VITAL_SIGN_MAP jika vital_type diberikan
        if (! empty($data['vital_type']) && isset(self::VITAL_SIGN_MAP[$data['vital_type']])) {
            $map = self::VITAL_SIGN_MAP[$data['vital_type']];
            $observationCode = $map['loinc_code'];
            $observationDisplay = $map['display'];
            $unit = $map['unit'];
            $unitCode = $map['ucum_code'];
        } else {
            $observationCode = $data['observation_code'];
            $observationDisplay = $data['observation_display'];
            $unit = $data['unit'];
            $unitCode = $data['unit_code'];
        }

        $body = [
            'resourceType' => 'Observation',
            'id' => $observationUuid,
            'status' => 'final',
            'category' => [
                [
                    'coding' => [
                        [
                            'system' => 'http://terminology.hl7.org/CodeSystem/observation-category',
                            'code' => 'vital-signs',
                            'display' => 'Vital Signs',
                        ],
                    ],
                ],
            ],
            'code' => [
                'coding' => [
                    [
                        'system' => 'http://loinc.org',
                        'code' => $observationCode,
                        'display' => $observationDisplay,
                    ],
                ],
            ],
            'subject' => [
                'reference' => 'Patient/'.$pasien->satusehat_uuid,
            ],
            'encounter' => [
                'reference' => 'Encounter/'.$data['encounter_uuid'],
                'display' => $data['encounter_display'] ?? '',
            ],
            'effectiveDateTime' => $this->formatUtcDateTime($data['effective_date'] ?? null),
            'issued' => $this->formatUtcDateTime($data['issued'] ?? null),
            'valueQuantity' => [
                'value' => (float) $data['value'],
                'unit' => $unit,
                'system' => 'http://unitsofmeasure.org',
                'code' => $unitCode,
            ],
        ];

        $url = $this->getBaseUrl().'/Observation/'.$observationUuid;
        $response = Http::withHeaders($this->getHeaders())->put($url, $body);

        if ($response->successful()) {
            $result = $response->json();
            $this->logSatusehatData(
                $instansi?->organization_id,
                $observationUuid,
                $body,
                'Success',
                $data['nomor_kunjungan'] ?? null,
                'Observation',
                $pasien->satusehat_uuid,
                $result,
                null,
                $data['created_by'] ?? null
            );

            return $result;
        }

        $this->logSatusehatData(
            $instansi?->organization_id,
            $observationUuid,
            $body,
            'Failed',
            $data['nomor_kunjungan'] ?? null,
            'Observation',
            $pasien->satusehat_uuid,
            [],
            $response->body(),
            $data['created_by'] ?? null
        );
        throw new \Exception('Gagal update Observation di SatuSehat: '.$response->body());
    }

    /**
     * Update Observation Blood Pressure (FHIR component structure).
     * PUT {baseUrl}/Observation/{id}
     *
     * @param  string  $observationUuid  UUID Observation Blood Pressure
     * @param  array  $data  Expecting keys:
     *                       - pasien (MstPasien model)
     *                       - encounter_uuid (string)
     *                       - encounter_display (string)
     *                       - systolic (numeric)
     *                       - diastolic (numeric)
     *                       - effective_date (string, opsional)
     *                       - issued (string, opsional)
     */
    public function updateObservationBloodPressure(string $observationUuid, array $data)
    {
        $pasien = $data['pasien'];
        $dokter = $data['dokter'];
        $instansi = MstInstansi::first();
        $bp = self::VITAL_SIGN_MAP['blood_pressure'];

        $body = [
            'resourceType' => 'Observation',
            'id' => $observationUuid,
            'status' => 'final',
            'category' => [
                [
                    'coding' => [
                        [
                            'system' => 'http://terminology.hl7.org/CodeSystem/observation-category',
                            'code' => 'vital-signs',
                            'display' => 'Vital Signs',
                        ],
                    ],
                ],
            ],
            'code' => [
                'coding' => [
                    [
                        'system' => 'http://loinc.org',
                        'code' => $bp['loinc_code'],
                        'display' => $bp['display'],
                    ],
                ],
            ],
            'subject' => [
                'reference' => 'Patient/'.$pasien->satusehat_uuid,
            ],
            'performer' => [
                [
                    'reference' => 'Practitioner/'.$dokter->practitioner_id,
                ],
            ],
            'encounter' => [
                'reference' => 'Encounter/'.$data['encounter_uuid'],
                'display' => $data['encounter_display'] ?? '',
            ],
            'effectiveDateTime' => $this->formatUtcDateTime($data['effective_date'] ?? null),
            'issued' => $this->formatUtcDateTime($data['issued'] ?? null),
            'component' => [
                [
                    'code' => [
                        'coding' => [
                            [
                                'system' => 'http://loinc.org',
                                'code' => $bp['systolic']['loinc_code'],
                                'display' => $bp['systolic']['display'],
                            ],
                        ],
                    ],
                    'valueQuantity' => [
                        'value' => (float) $data['systolic'],
                        'unit' => $bp['systolic']['unit'],
                        'system' => 'http://unitsofmeasure.org',
                        'code' => $bp['systolic']['ucum_code'],
                    ],
                ],
                [
                    'code' => [
                        'coding' => [
                            [
                                'system' => 'http://loinc.org',
                                'code' => $bp['diastolic']['loinc_code'],
                                'display' => $bp['diastolic']['display'],
                            ],
                        ],
                    ],
                    'valueQuantity' => [
                        'value' => (float) $data['diastolic'],
                        'unit' => $bp['diastolic']['unit'],
                        'system' => 'http://unitsofmeasure.org',
                        'code' => $bp['diastolic']['ucum_code'],
                    ],
                ],
            ],
        ];

        $url = $this->getBaseUrl().'/Observation/'.$observationUuid;
        $response = Http::withHeaders($this->getHeaders())->put($url, $body);

        if ($response->successful()) {
            $result = $response->json();
            $this->logSatusehatData(
                $instansi?->organization_id,
                $observationUuid,
                $body,
                'Success',
                $data['nomor_kunjungan'] ?? null,
                'Observation',
                $pasien->satusehat_uuid,
                $result,
                null,
                $data['created_by'] ?? null
            );

            return $result;
        }

        $this->logSatusehatData(
            $instansi?->organization_id,
            $observationUuid,
            $body,
            'Failed',
            $data['nomor_kunjungan'] ?? null,
            'Observation',
            $pasien->satusehat_uuid,
            [],
            $response->body(),
            $data['created_by'] ?? null
        );
        throw new \Exception('Gagal update Observation (Blood Pressure) di SatuSehat: '.$response->body());
    }

    // ==========================================
    // UTILITY: FORMAT WAKTU & VALIDASI TANGGAL
    // ==========================================

    /**
     * Konversi datetime ke format UTC+00 sesuai standar SatuSehat.
     *
     * SatuSehat mengharuskan semua waktu dikirim dalam UTC+00.
     * Contoh: 17:35 WIB (UTC+7) → 10:35 UTC → "2023-08-23T10:35:00+00:00"
     *
     * @param  string|Carbon|\DateTimeInterface|null  $datetime  Waktu input (lokal/any timezone)
     * @return string Format: "Y-m-d\TH:i:s+00:00"
     */
    protected function formatUtcDateTime($datetime = null): string
    {
        if ($datetime instanceof Carbon || $datetime instanceof \DateTimeInterface) {
            $carbon = Carbon::parse($datetime);
        } elseif (is_string($datetime) && ! empty($datetime)) {
            $carbon = Carbon::parse($datetime);
        } else {
            // Default: waktu sekarang
            $carbon = Carbon::now();
        }

        // Validasi tanggal minimum
        $this->validateDateNotBeforeMinimum($carbon);

        // Convert ke UTC+00
        return $carbon->setTimezone('UTC')->format('Y-m-d\TH:i:s+00:00');
    }

    /**
     * Konversi date ke format UTC date (Y-m-d) sesuai standar SatuSehat.
     *
     * Digunakan untuk field yang hanya butuh tanggal saja (misal: birthDate).
     *
     * @param  string|Carbon|\DateTimeInterface|null  $date  Tanggal input
     * @return string Format: "Y-m-d"
     */
    protected function formatUtcDate($date = null): string
    {
        if ($date instanceof Carbon || $date instanceof \DateTimeInterface) {
            $carbon = Carbon::parse($date);
        } elseif (is_string($date) && ! empty($date)) {
            $carbon = Carbon::parse($date);
        } else {
            $carbon = Carbon::now();
        }

        // Validasi tanggal minimum
        $this->validateDateNotBeforeMinimum($carbon);

        return $carbon->format('Y-m-d');
    }

    /**
     * Validasi bahwa tanggal tidak kurang dari tanggal minimum SatuSehat (03 Juni 2014).
     *
     * @throws \Exception jika tanggal sebelum minimum
     */
    protected function validateDateNotBeforeMinimum(Carbon $date): void
    {
        $minDate = Carbon::parse(self::SATUSEHAT_MIN_DATE);

        if ($date->lt($minDate)) {
            throw new \Exception(
                'Tanggal pengiriman SatuSehat tidak boleh kurang dari '
                .$minDate->format('d F Y').'. '
                .'Tanggal yang dikirim: '.$date->format('d F Y')
            );
        }
    }

    /**
     * Mendapatkan waktu sekarang dalam format UTC+00 untuk SatuSehat.
     *
     * @return string Format: "Y-m-d\TH:i:s+00:00"
     */
    protected function nowUtc(): string
    {
        return Carbon::now()->setTimezone('UTC')->format('Y-m-d\TH:i:s+00:00');
    }

    /**
     * Log payload yang dikirim ke SatuSehat ke tabel trx_satusehat_log.
     *
     * @param  string|null  $resourceType  Encounter, Condition, Observation
     */
    protected function logSatusehatData(
        ?string $organizationId,
        ?string $resourceUuid,
        array $payload,
        string $status = 'pending',
        ?string $nomorKunjungan = null,
        ?string $resourceType = null,
        ?string $patientId = null,
        array $responseJson = [],
        ?string $errorMessage = null,
        ?string $createdBy = null
    ) {
        try {
            TrxSatusehatLog::create([
                'nomor_kunjungan' => $nomorKunjungan,
                'patient_id' => $patientId,
                'organization_id' => $organizationId,
                'resource_type' => $resourceType,
                'resource_uuid' => $resourceUuid,
                'request_json' => json_encode($payload),
                'response_json' => ! empty($responseJson) ? json_encode($responseJson) : null,
                'status' => $status,
                'error_message' => $errorMessage,
                'created_by' => $createdBy,
            ]);
        } catch (\Exception $e) {
            Log::warning('Gagal menyimpan log SatuSehat data: '.$e->getMessage());
        }
    }

    // ==========================================
    // PROCEDURE RESOURCE
    // ==========================================

    /**
     * Search Procedures by Patient Subject UUID.
     * GET {baseUrl}/Procedure?subject={subjectUuid}
     *
     * @param  string  $subjectUuid  SatuSehat UUID pasien (dari mst_pasien.satusehat_uuid)
     */
    public function searchProcedureBySubject(string $subjectUuid)
    {
        $url = $this->getBaseUrl().'/Procedure';
        $params = ['subject' => $subjectUuid];

        $response = Http::withHeaders($this->getHeaders())->get($url, $params);

        if ($response->successful() && ! empty($response->json()['entry'])) {
            return $response->json()['entry'];
        }

        return null;
    }

    /**
     * Search Procedures by Patient Subject UUID and Encounter UUID.
     * GET {baseUrl}/Procedure?subject={subjectUuid}&encounter={encounterUuid}
     *
     * @param  string  $subjectUuid  SatuSehat UUID pasien (dari mst_pasien.satusehat_uuid)
     * @param  string  $encounterUuid  UUID Encounter
     */
    public function searchProcedureBySubjectAndEncounter(string $subjectUuid, string $encounterUuid)
    {
        $url = $this->getBaseUrl().'/Procedure';
        $params = [
            'subject' => $subjectUuid,
            'encounter' => $encounterUuid,
        ];

        $response = Http::withHeaders($this->getHeaders())->get($url, $params);

        if ($response->successful() && ! empty($response->json()['entry'])) {
            return $response->json()['entry'];
        }

        return null;
    }

    /**
     * Search Procedures by Encounter UUID.
     * GET {baseUrl}/Procedure?encounter={encounterUuid}
     *
     * @param  string  $encounterUuid  UUID Encounter
     */
    public function searchProcedureByEncounter(string $encounterUuid)
    {
        $url = $this->getBaseUrl().'/Procedure';
        $params = ['encounter' => $encounterUuid];

        $response = Http::withHeaders($this->getHeaders())->get($url, $params);

        if ($response->successful() && ! empty($response->json()['entry'])) {
            return $response->json()['entry'];
        }

        return null;
    }

    /**
     * Get a specific Procedure by its UUID (path variable).
     * GET {baseUrl}/Procedure/{id}
     *
     * @param  string  $procedureUuid  UUID Procedure
     */
    public function getProcedureById(string $procedureUuid)
    {
        $url = $this->getBaseUrl().'/Procedure/'.$procedureUuid;

        $response = Http::withHeaders($this->getHeaders())->get($url);

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    /**
     * Create a new Procedure resource in SatuSehat.
     * POST {baseUrl}/Procedure
     *
     * Data tindakan diambil dari trx_tindakan, kode ICD-9-CM dan SNOMED dari mst_tindakan.
     *
     * @param  array  $data  Expecting keys:
     *                       - pasien (MstPasien model)
     *                       - dokter (MstDokter model)
     *                       - encounter_uuid (string)
     *                       - encounter_display (string)
     *                       - tindakan (TrxTindakan model with relation 'tindakan' loaded)
     *                       - performed_start (datetime, opsional)
     *                       - performed_end (datetime, opsional)
     *                       - reason_code (string, ICD-10 code, opsional)
     *                       - reason_display (string, opsional)
     *                       - note (string, opsional)
     */
    public function createProcedure(array $data)
    {
        $pasien = $data['pasien'];
        $dokter = $data['dokter'];
        $trxTindakan = $data['tindakan'];
        $masterTindakan = $trxTindakan->tindakan;
        $instansi = MstInstansi::first();

        $body = [
            'resourceType' => 'Procedure',
            'status' => 'completed',
            'category' => [
                'coding' => [
                    [
                        'system' => 'http://snomed.info/sct',
                        'code' => $masterTindakan->snomed_code ?: '103693007',
                        'display' => $masterTindakan->snomed_name ?: 'Diagnostic procedure',
                    ],
                ],
                'text' => $masterTindakan->snomed_name ?: 'Diagnostic procedure',
            ],
            'code' => [
                'coding' => [
                    [
                        'system' => 'http://hl7.org/fhir/sid/icd-9-cm',
                        'code' => $masterTindakan->icd9cm_code ?: $masterTindakan->kode_tindakan,
                        'display' => $masterTindakan->icd9cm_name ?: $masterTindakan->nama_tindakan,
                    ],
                ],
            ],
            'subject' => [
                'reference' => 'Patient/'.$pasien->satusehat_uuid,
                'display' => $pasien->nama_pasien,
            ],
            'encounter' => [
                'reference' => 'Encounter/'.$data['encounter_uuid'],
                'display' => $data['encounter_display'] ?? '',
            ],
            'performedPeriod' => [
                'start' => $this->formatUtcDateTime($data['performed_start'] ?? null),
                'end' => $this->formatUtcDateTime($data['performed_end'] ?? null),
            ],
            'performer' => [
                [
                    'actor' => [
                        'reference' => 'Practitioner/'.$dokter->practitioner_id,
                        'display' => $dokter->nama_dokter,
                    ],
                ],
            ],
        ];

        // Tambahkan reasonCode jika ada diagnosis terkait
        if (! empty($data['reason_code'])) {
            $body['reasonCode'] = [
                [
                    'coding' => [
                        [
                            'system' => 'http://hl7.org/fhir/sid/icd-10',
                            'code' => $data['reason_code'],
                            'display' => $data['reason_display'] ?? $data['reason_code'],
                        ],
                    ],
                ],
            ];
        }

        // Tambahkan bodySite jika SNOMED code tersedia
        if (! empty($masterTindakan->snomed_code)) {
            $body['bodySite'] = [
                [
                    'coding' => [
                        [
                            'system' => 'http://snomed.info/sct',
                            'code' => $masterTindakan->snomed_code,
                            'display' => $masterTindakan->snomed_name ?: '',
                        ],
                    ],
                ],
            ];
        }

        // Tambahkan catatan jika ada
        if (! empty($data['note'])) {
            $body['note'] = [
                [
                    'text' => $data['note'],
                ],
            ];
        }

        $url = $this->getBaseUrl().'/Procedure';
        $response = Http::withHeaders($this->getHeaders())->post($url, $body);

        if ($response->successful()) {
            $result = $response->json();
            $this->logSatusehatData(
                $instansi?->organization_id,
                $result['id'] ?? null,
                $body,
                'Success',
                $data['nomor_kunjungan'] ?? null,
                'Procedure',
                $pasien->satusehat_uuid,
                $result,
                null,
                $data['created_by'] ?? null
            );

            return $result;
        }

        $this->logSatusehatData(
            $instansi?->organization_id,
            null,
            $body,
            'Failed',
            $data['nomor_kunjungan'] ?? null,
            'Procedure',
            $pasien->satusehat_uuid,
            [],
            $response->body(),
            $data['created_by'] ?? null
        );
        throw new \Exception('Gagal create Procedure di SatuSehat: '.$response->body());
    }

    /**
     * Update an existing Procedure in SatuSehat.
     * PUT {baseUrl}/Procedure/{id}
     *
     * @param  string  $procedureUuid  UUID Procedure yang akan di-update
     * @param  array  $data  (sama seperti createProcedure)
     */
    public function updateProcedure(string $procedureUuid, array $data)
    {
        $pasien = $data['pasien'];
        $dokter = $data['dokter'];
        $trxTindakan = $data['tindakan'];
        $masterTindakan = $trxTindakan->tindakan;
        $instansi = MstInstansi::first();

        $body = [
            'resourceType' => 'Procedure',
            'id' => $procedureUuid,
            'status' => 'completed',
            'category' => [
                'coding' => [
                    [
                        'system' => 'http://snomed.info/sct',
                        'code' => $masterTindakan->snomed_code ?: '103693007',
                        'display' => $masterTindakan->snomed_name ?: 'Diagnostic procedure',
                    ],
                ],
                'text' => $masterTindakan->snomed_name ?: 'Diagnostic procedure',
            ],
            'code' => [
                'coding' => [
                    [
                        'system' => 'http://hl7.org/fhir/sid/icd-9-cm',
                        'code' => $masterTindakan->icd9cm_code ?: $masterTindakan->kode_tindakan,
                        'display' => $masterTindakan->icd9cm_name ?: $masterTindakan->nama_tindakan,
                    ],
                ],
            ],
            'subject' => [
                'reference' => 'Patient/'.$pasien->satusehat_uuid,
                'display' => $pasien->nama_pasien,
            ],
            'encounter' => [
                'reference' => 'Encounter/'.$data['encounter_uuid'],
                'display' => $data['encounter_display'] ?? '',
            ],
            'performedPeriod' => [
                'start' => $this->formatUtcDateTime($data['performed_start'] ?? null),
                'end' => $this->formatUtcDateTime($data['performed_end'] ?? null),
            ],
            'performer' => [
                [
                    'actor' => [
                        'reference' => 'Practitioner/'.$dokter->practitioner_id,
                        'display' => $dokter->nama_dokter,
                    ],
                ],
            ],
        ];

        // Tambahkan reasonCode jika ada
        if (! empty($data['reason_code'])) {
            $body['reasonCode'] = [
                [
                    'coding' => [
                        [
                            'system' => 'http://hl7.org/fhir/sid/icd-10',
                            'code' => $data['reason_code'],
                            'display' => $data['reason_display'] ?? $data['reason_code'],
                        ],
                    ],
                ],
            ];
        }

        // Tambahkan bodySite jika SNOMED code tersedia
        if (! empty($masterTindakan->snomed_code)) {
            $body['bodySite'] = [
                [
                    'coding' => [
                        [
                            'system' => 'http://snomed.info/sct',
                            'code' => $masterTindakan->snomed_code,
                            'display' => $masterTindakan->snomed_name ?: '',
                        ],
                    ],
                ],
            ];
        }

        // Tambahkan catatan jika ada
        if (! empty($data['note'])) {
            $body['note'] = [
                [
                    'text' => $data['note'],
                ],
            ];
        }

        $url = $this->getBaseUrl().'/Procedure/'.$procedureUuid;
        $response = Http::withHeaders($this->getHeaders())->put($url, $body);

        if ($response->successful()) {
            $result = $response->json();
            $this->logSatusehatData(
                $instansi?->organization_id,
                $procedureUuid,
                $body,
                'Success',
                $data['nomor_kunjungan'] ?? null,
                'Procedure',
                $pasien->satusehat_uuid,
                $result,
                null,
                $data['created_by'] ?? null
            );

            return $result;
        }

        $this->logSatusehatData(
            $instansi?->organization_id,
            $procedureUuid,
            $body,
            'Failed',
            $data['nomor_kunjungan'] ?? null,
            'Procedure',
            $pasien->satusehat_uuid,
            [],
            $response->body(),
            $data['created_by'] ?? null
        );
        throw new \Exception('Gagal update Procedure di SatuSehat: '.$response->body());
    }

    /**
     * Kirim semua tindakan dari kunjungan sebagai Procedure resources.
     *
     * @param  array  $data  Expecting keys:
     *                       - pendaftaran (TrxPendaftaran model with relations)
     *                       - encounter_uuid (string)
     * @return array Hasil per-tindakan
     */
    public function createProcedureAllTindakan(array $data): array
    {
        $pendaftaran = $data['pendaftaran'];
        $encounterUuid = $data['encounter_uuid'];
        $retryProcedureCodes = $data['retry_procedure_codes'] ?? []; // ICD-9-CM codes untuk diretry

        $pasien = $pendaftaran->pasien;
        $dokter = $pendaftaran->dokter;

        // Load tindakan beserta relasi master
        $tindakanList = TrxTindakan::with('tindakan')
            ->where('nomor_kunjungan', $pendaftaran->nomor_kunjungan)
            ->get();

        $results = [];

        if ($tindakanList->isEmpty()) {
            return $results;
        }

        // Helper: cek apakah procedure code ada di list retry
        $shouldProcess = function ($trxTindakan, $masterTindakan) use ($retryProcedureCodes): bool {
            if (empty($retryProcedureCodes)) {
                return true; // Jika tidak ada filter, proses semua
            }
            $icd9Code = $masterTindakan->icd9cm_code ?? $trxTindakan->kode_tindakan;

            return in_array($icd9Code, $retryProcedureCodes);
        };

        // Ambil diagnosis utama untuk reasonCode (opsional)
        $diagnoses = $pendaftaran->diagnoses ?? collect();
        $primaryDiag = $diagnoses->first();
        $reasonCode = null;
        $reasonDisplay = null;
        if ($primaryDiag) {
            $reasonCode = $primaryDiag->kode_diagnosa;
            $reasonDisplay = $primaryDiag->masterDiagnosis ? $primaryDiag->masterDiagnosis->nama_diagnosa : $reasonCode;
        }

        foreach ($tindakanList as $trxTindakan) {
            $masterTindakan = $trxTindakan->tindakan;

            // Skip jika master tindakan tidak ditemukan
            if (! $masterTindakan) {
                $results[$trxTindakan->kode_tindakan] = [
                    'error' => "Master tindakan tidak ditemukan untuk kode: {$trxTindakan->kode_tindakan}",
                ];

                continue;
            }

            // Skip jika tidak termasuk dalam list retry
            if (! $shouldProcess($trxTindakan, $masterTindakan)) {
                continue;
            }

            try {
                $result = $this->createProcedure([
                    'pasien' => $pasien,
                    'dokter' => $dokter,
                    'nomor_kunjungan' => $pendaftaran->nomor_kunjungan,
                    'encounter_uuid' => $encounterUuid,
                    'encounter_display' => "Tindakan {$masterTindakan->nama_tindakan} {$pasien->nama_pasien} tanggal ".($pendaftaran->created_at ? $pendaftaran->created_at->format('d/m/Y') : '-'),
                    'tindakan' => $trxTindakan,
                    'performed_start' => $pendaftaran->created_at,
                    'performed_end' => $pendaftaran->updated_at ?? $pendaftaran->created_at,
                    'reason_code' => $reasonCode,
                    'reason_display' => $reasonDisplay,
                    'note' => $masterTindakan->deskripsi,
                    'created_by' => $data['created_by'] ?? null,
                ]);

                $results[$trxTindakan->kode_tindakan] = $result;

            } catch (\Exception $e) {
                $results[$trxTindakan->kode_tindakan] = [
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    // ==========================================
    // RESUME MEDIS ORCHESTRATOR
    // ==========================================

    /**
     * Kirim Resume Medis lengkap ke SatuSehat untuk satu kunjungan.
     *
     * Flow: Encounter (create→in-progress→discharge→finished) → Condition → Observation
     *
     * Setiap resource yang berhasil/gagal disimpan per-baris di trx_satusehat_status.
     * Data payload dikirim ke trx_satusehat_data.
     *
     * @param  string|null  $createdBy  User yang memicu pengiriman
     * @return array Ringkasan hasil pengiriman
     */
    public function sendResumeMedis(string $nomorKunjungan, ?string $createdBy = null): array
    {
        $results = [
            'nomor_kunjungan' => $nomorKunjungan,
            'encounter' => null,
            'conditions' => [],
            'observations' => [],
            'procedures' => [],
            'errors' => [],
        ];

        // ── Load data pendaftaran ──
        $pendaftaran = TrxPendaftaran::with(['pasien', 'dokter', 'poli', 'diagnoses.masterDiagnosis'])
            ->where('nomor_kunjungan', $nomorKunjungan)
            ->first();

        if (! $pendaftaran) {
            throw new \Exception("Data pendaftaran dengan nomor kunjungan {$nomorKunjungan} tidak ditemukan.");
        }

        $pasien = $pendaftaran->pasien;
        $dokter = $pendaftaran->dokter;

        if (! $pasien || ! $pasien->satusehat_uuid) {
            throw new \Exception('Pasien belum memiliki SatuSehat UUID. Silakan sinkronisasi pasien terlebih dahulu.');
        }
        if (! $dokter || ! $dokter->practitioner_id) {
            throw new \Exception('Dokter belum memiliki Practitioner ID SatuSehat. Silakan sinkronisasi dokter terlebih dahulu.');
        }

        // ── Ambil Location & Instansi ──
        $location = MstLocation::where('status', 'active')->first()
            ?? MstLocation::first();

        if (! $location || ! $location->location_id) {
            throw new \Exception('Location SatuSehat belum tersedia. Silakan setup Location terlebih dahulu.');
        }

        $instansi = MstInstansi::first();
        if (! $instansi || ! $instansi->organization_id) {
            throw new \Exception('Data instansi/Organization SatuSehat belum tersedia.');
        }

        $periodStart = $pendaftaran->created_at;
        $periodEnd = $pendaftaran->updated_at ?? $pendaftaran->created_at;

        // ================================================================
        // STEP 1: ENCOUNTER
        // ================================================================
        $encounterUuid = null;
        try {
            // 1a. Create Encounter (arrived)
            $encounterData = [
                'pasien' => $pasien,
                'dokter' => $dokter,
                'location' => $location,
                'nomor_kunjungan' => $nomorKunjungan,
                'period_start' => $periodStart,
                'created_by' => $createdBy,
            ];

            $encounterResult = $this->createEncounter($encounterData);
            $encounterUuid = $encounterResult['id'] ?? null;

            if (! $encounterUuid) {
                throw new \Exception('Encounter UUID tidak ditemukan dalam response.');
            }

            // 1b. Update to in-progress
            $inProgressData = array_merge($encounterData, [
                'period_end' => $periodEnd,
                'arrived_start' => $periodStart,
                'arrived_end' => $periodStart,
                'inprogress_start' => $periodStart,
                'inprogress_end' => $periodEnd,
            ]);
            $this->updateEncounterInProgress($encounterUuid, $inProgressData);

            $results['encounter'] = ['uuid' => $encounterUuid, 'status' => 'Success'];

        } catch (\Exception $e) {
            Log::error("SendResumeMedis Encounter gagal [{$nomorKunjungan}]: ".$e->getMessage());
            $results['encounter'] = ['uuid' => null, 'status' => 'Failed', 'error' => $e->getMessage()];
            $results['errors'][] = 'Encounter: '.$e->getMessage();

            // Encounter gagal → tidak bisa lanjut
            return $results;
        }

        // ================================================================
        // STEP 2: CONDITION (Diagnosis)
        // ================================================================
        $conditionUuids = [];
        $diagnoses = $pendaftaran->diagnoses;

        if ($diagnoses->isNotEmpty()) {
            foreach ($diagnoses as $idx => $trxDiag) {
                try {
                    $diagCode = $trxDiag->kode_diagnosa;
                    $diagDisplay = $trxDiag->masterDiagnosis ? $trxDiag->masterDiagnosis->nama_diagnosa : $diagCode;

                    $conditionResult = $this->createCondition([
                        'pasien' => $pasien,
                        'encounter_uuid' => $encounterUuid,
                        'encounter_display' => "Kunjungan {$pasien->nama_pasien} tanggal ".($pendaftaran->created_at ? $pendaftaran->created_at->format('d/m/Y') : '-'),
                        'diagnosis_code' => $diagCode,
                        'diagnosis_display' => $diagDisplay,
                        'nomor_kunjungan' => $nomorKunjungan,
                        'created_by' => $createdBy,
                    ]);

                    $condUuid = $conditionResult['id'] ?? null;
                    $conditionUuids[] = [
                        'condition_uuid' => $condUuid,
                        'condition_display' => $diagDisplay,
                        'rank' => $idx + 1,
                    ];

                    $results['conditions'][] = ['uuid' => $condUuid, 'code' => $diagCode, 'status' => 'Success'];

                } catch (\Exception $e) {
                    Log::error("SendResumeMedis Condition gagal [{$nomorKunjungan}] {$diagCode}: ".$e->getMessage());
                    $results['conditions'][] = ['uuid' => null, 'code' => $diagCode ?? '-', 'status' => 'Failed', 'error' => $e->getMessage()];
                    $results['errors'][] = "Condition ({$diagCode}): ".$e->getMessage();
                }
            }
        } else {
            // Tidak ada diagnosis → kirim Condition Stable
            try {
                $conditionResult = $this->createConditionStable([
                    'pasien' => $pasien,
                    'encounter_uuid' => $encounterUuid,
                    'encounter_display' => "Kunjungan {$pasien->nama_pasien} tanggal ".($pendaftaran->created_at ? $pendaftaran->created_at->format('d/m/Y') : '-'),
                    'nomor_kunjungan' => $nomorKunjungan,
                    'created_by' => $createdBy,
                ]);

                $condUuid = $conditionResult['id'] ?? null;
                $conditionUuids[] = [
                    'condition_uuid' => $condUuid,
                    'condition_display' => "Patient's condition stable",
                    'rank' => 1,
                ];

                $results['conditions'][] = ['uuid' => $condUuid, 'code' => 'Stable', 'status' => 'Success'];

            } catch (\Exception $e) {
                Log::error("SendResumeMedis ConditionStable gagal [{$nomorKunjungan}]: ".$e->getMessage());
                $results['conditions'][] = ['uuid' => null, 'code' => 'Stable', 'status' => 'Failed', 'error' => $e->getMessage()];
                $results['errors'][] = 'Condition (Stable): '.$e->getMessage();
            }
        }

        // ── Update Encounter discharge + finished ──
        try {
            $dischargeData = array_merge($encounterData, [
                'period_end' => $periodEnd,
                'arrived_start' => $periodStart,
                'arrived_end' => $periodStart,
                'inprogress_start' => $periodStart,
                'inprogress_end' => $periodEnd,
                'discharge_code' => 'home',
                'discharge_display' => 'Home',
                'discharge_text' => 'Pulang dalam keadaan sehat',
            ]);
            $this->updateEncounterDischargeDisposition($encounterUuid, $dischargeData);

            $finishedData = array_merge($encounterData, [
                'period_end' => $periodEnd,
                'arrived_start' => $periodStart,
                'arrived_end' => $periodStart,
                'inprogress_start' => $periodStart,
                'inprogress_end' => $periodEnd,
                'finished_start' => $periodEnd,
                'finished_end' => $periodEnd,
                'diagnosis' => $conditionUuids,
            ]);
            $this->updateEncounterFinished($encounterUuid, $finishedData);

        } catch (\Exception $e) {
            Log::error("SendResumeMedis Encounter finalize gagal [{$nomorKunjungan}]: ".$e->getMessage());
            $results['errors'][] = 'Encounter Finalize: '.$e->getMessage();
        }

        // ================================================================
        // STEP 3: OBSERVATION (Vital Signs)
        // ================================================================
        try {
            $obsResults = $this->createObservationAllVitalSigns([
                'pendaftaran' => $pendaftaran,
                'encounter_uuid' => $encounterUuid,
                'created_by' => $createdBy,
            ]);

            foreach ($obsResults as $vitalType => $obsResult) {
                if (isset($obsResult['error'])) {
                    $results['observations'][] = ['type' => $vitalType, 'status' => 'Failed', 'error' => $obsResult['error']];
                    $results['errors'][] = "Observation ({$vitalType}): ".$obsResult['error'];
                } else {
                    $obsUuid = $obsResult['id'] ?? null;
                    $results['observations'][] = ['type' => $vitalType, 'uuid' => $obsUuid, 'status' => 'Success'];
                }
            }
        } catch (\Exception $e) {
            Log::error("SendResumeMedis Observation gagal [{$nomorKunjungan}]: ".$e->getMessage());
            $results['observations'][] = ['type' => 'all', 'status' => 'Failed', 'error' => $e->getMessage()];
            $results['errors'][] = 'Observation: '.$e->getMessage();
        }

        // ================================================================
        // STEP 4: PROCEDURE (Tindakan)
        // ================================================================
        try {
            $procResults = $this->createProcedureAllTindakan([
                'pendaftaran' => $pendaftaran,
                'encounter_uuid' => $encounterUuid,
                'created_by' => $createdBy,
            ]);

            foreach ($procResults as $tindakanCode => $procResult) {
                if (isset($procResult['error'])) {
                    $results['procedures'][] = ['code' => $tindakanCode, 'status' => 'Failed', 'error' => $procResult['error']];
                    $results['errors'][] = "Procedure ({$tindakanCode}): ".$procResult['error'];
                } else {
                    $procUuid = $procResult['id'] ?? null;
                    $results['procedures'][] = ['code' => $tindakanCode, 'uuid' => $procUuid, 'status' => 'Success'];
                }
            }
        } catch (\Exception $e) {
            Log::error("SendResumeMedis Procedure gagal [{$nomorKunjungan}]: ".$e->getMessage());
            $results['procedures'][] = ['code' => 'all', 'status' => 'Failed', 'error' => $e->getMessage()];
            $results['errors'][] = 'Procedure: '.$e->getMessage();
        }

        // ================================================================
        // STEP 5: COMPOSITION (Discharge Summary)
        // ================================================================
        try {
            $compositionData = [
                'pasien' => $pasien,
                'dokter' => $dokter,
                'encounter_uuid' => $encounterUuid,
                'encounter_display' => "Kunjungan {$pasien->nama_pasien} tanggal ".($pendaftaran->created_at ? $pendaftaran->created_at->format('d/m/Y') : '-'),
                'date' => $pendaftaran->created_at ? $pendaftaran->created_at->format('Y-m-d') : now()->format('Y-m-d'),
                'nomor_kunjungan' => $nomorKunjungan,
                'created_by' => $createdBy,
            ];

            $compositionResult = $this->createComposition($compositionData);
            $compUuid = $compositionResult['id'] ?? null;

            $results['composition'] = ['uuid' => $compUuid, 'status' => 'Success'];

        } catch (\Exception $e) {
            Log::error("SendResumeMedis Composition gagal [{$nomorKunjungan}]: ".$e->getMessage());
            $results['composition'] = ['uuid' => null, 'status' => 'Failed', 'error' => $e->getMessage()];
            $results['errors'][] = 'Composition: '.$e->getMessage();
        }

        return $results;
    }

    /**
     * Kirim ulang (retry) resource yang gagal untuk satu kunjungan.
     *
     * @param  string  $resourceType  'Encounter', 'Condition', 'Observation', 'Procedure', atau 'Composition'
     * @return array Hasil pengiriman ulang
     */
    public function retrySendResource(string $nomorKunjungan, string $resourceType, ?string $createdBy = null): array
    {
        // Hapus status lama yang gagal untuk resource type ini
        TrxSatusehatLog::where('nomor_kunjungan', $nomorKunjungan)
            ->where('resource_type', $resourceType)
            ->where('status', 'Failed')
            ->delete();

        $pendaftaran = TrxPendaftaran::with(['pasien', 'dokter', 'poli', 'diagnoses.masterDiagnosis'])
            ->where('nomor_kunjungan', $nomorKunjungan)
            ->first();

        if (! $pendaftaran) {
            throw new \Exception('Data pendaftaran tidak ditemukan.');
        }

        $pasien = $pendaftaran->pasien;
        $dokter = $pendaftaran->dokter;
        $location = MstLocation::where('status', 'active')->first() ?? MstLocation::first();
        $instansi = MstInstansi::first();

        if (! $pasien?->satusehat_uuid || ! $dokter?->practitioner_id || ! $location?->location_id || ! $instansi?->organization_id) {
            throw new \Exception('Pre-requisite data belum lengkap untuk retry.');
        }

        // Cari Encounter UUID yang sudah berhasil (jika ada)
        $existingEncounter = TrxSatusehatLog::where('nomor_kunjungan', $nomorKunjungan)
            ->where('resource_type', 'Encounter')
            ->where('status', 'Success')
            ->first();

        $encounterUuid = $existingEncounter?->resource_uuid;

        $periodStart = $pendaftaran->created_at;
        $periodEnd = $pendaftaran->updated_at ?? $pendaftaran->created_at;
        $results = ['resource_type' => $resourceType, 'items' => [], 'errors' => []];

        if ($resourceType === 'Encounter') {
            // Retry seluruh flow karena Encounter adalah fondasi
            return $this->sendResumeMedis($nomorKunjungan, $createdBy);
        }

        if (! $encounterUuid) {
            throw new \Exception('Encounter UUID belum tersedia. Kirim ulang Encounter terlebih dahulu.');
        }

        if ($resourceType === 'Condition') {
            // Ambil kode diagnosis yang gagal dari log lama (sebelum dihapus)
            $failedLogs = TrxSatusehatLog::where('nomor_kunjungan', $nomorKunjungan)
                ->where('resource_type', 'Condition')
                ->where('status', 'Failed')
                ->get();

            $failedDiagnosisCodes = [];
            foreach ($failedLogs as $log) {
                $request = $log->request_json ?? [];
                if (isset($request['code']['coding'][0]['code'])) {
                    $failedDiagnosisCodes[] = $request['code']['coding'][0]['code'];
                }
            }

            // Jika tidak ada log gagal, tidak perlu retry
            if (empty($failedDiagnosisCodes)) {
                $results['items'][] = ['code' => '-', 'status' => 'Skipped', 'message' => 'Tidak ada resource Condition yang gagal'];

                return $results;
            }

            $diagnoses = $pendaftaran->diagnoses;

            if ($diagnoses->isNotEmpty()) {
                foreach ($diagnoses as $trxDiag) {
                    $diagCode = $trxDiag->kode_diagnosa;

                    // Skip jika diagnosis ini tidak termasuk yang gagal
                    if (! in_array($diagCode, $failedDiagnosisCodes)) {
                        continue;
                    }

                    try {
                        $diagDisplay = $trxDiag->masterDiagnosis ? $trxDiag->masterDiagnosis->nama_diagnosa : $diagCode;

                        $condResult = $this->createCondition([
                            'pasien' => $pasien,
                            'encounter_uuid' => $encounterUuid,
                            'encounter_display' => "Kunjungan {$pasien->nama_pasien}",
                            'diagnosis_code' => $diagCode,
                            'diagnosis_display' => $diagDisplay,
                            'nomor_kunjungan' => $nomorKunjungan,
                            'created_by' => $createdBy,
                        ]);

                        $condUuid = $condResult['id'] ?? null;
                        $results['items'][] = ['uuid' => $condUuid, 'code' => $diagCode, 'status' => 'Success'];
                    } catch (\Exception $e) {
                        $results['items'][] = ['code' => $diagCode, 'status' => 'Failed', 'error' => $e->getMessage()];
                        $results['errors'][] = $e->getMessage();
                    }
                }
            } else {
                // Stable condition untuk pasien tanpa diagnosis
                if (in_array('stable', $failedDiagnosisCodes) || in_array('STABLE', $failedDiagnosisCodes)) {
                    try {
                        $condResult = $this->createConditionStable([
                            'pasien' => $pasien,
                            'encounter_uuid' => $encounterUuid,
                            'encounter_display' => "Kunjungan {$pasien->nama_pasien}",
                            'nomor_kunjungan' => $nomorKunjungan,
                            'created_by' => $createdBy,
                        ]);
                        $condUuid = $condResult['id'] ?? null;
                        $results['items'][] = ['uuid' => $condUuid, 'code' => 'Stable', 'status' => 'Success'];
                    } catch (\Exception $e) {
                        $results['items'][] = ['code' => 'Stable', 'status' => 'Failed', 'error' => $e->getMessage()];
                        $results['errors'][] = $e->getMessage();
                    }
                }
            }

            // --- PASTIKAN UPDATE ENCOUNTER IKUT DIKIRIM ---
            // Laporan pengiriman ini dilakukan di akhir, pastikan Encounter mendapatkan info discharge & up-to-date diagnosis
            try {
                $successConditions = TrxSatusehatLog::where('nomor_kunjungan', $nomorKunjungan)
                    ->where('resource_type', 'Condition')
                    ->where('status', 'Success')
                    ->get();

                $conditionUuidsForEncounter = [];
                $rank = 1;
                foreach ($successConditions as $stat) {
                    $conditionUuidsForEncounter[] = [
                        'condition_uuid' => $stat->resource_uuid,
                        'condition_display' => "Diagnosis $rank",
                        'rank' => $rank++,
                    ];
                }

                $encounterData = [
                    'pasien' => $pasien,
                    'dokter' => $dokter,
                    'location' => $location,
                    'nomor_kunjungan' => $nomorKunjungan,
                    'period_start' => $periodStart,
                ];

                $dischargeData = array_merge($encounterData, [
                    'period_end' => $periodEnd,
                    'arrived_start' => $periodStart,
                    'arrived_end' => $periodStart,
                    'inprogress_start' => $periodStart,
                    'inprogress_end' => $periodEnd,
                    'discharge_code' => 'home',
                    'discharge_display' => 'Home',
                    'discharge_text' => 'Pulang dalam keadaan sehat',
                ]);
                $this->updateEncounterDischargeDisposition($encounterUuid, $dischargeData);

                $finishedData = array_merge($encounterData, [
                    'period_end' => $periodEnd,
                    'arrived_start' => $periodStart,
                    'arrived_end' => $periodStart,
                    'inprogress_start' => $periodStart,
                    'inprogress_end' => $periodEnd,
                    'finished_start' => $periodEnd,
                    'finished_end' => $periodEnd,
                    'diagnosis' => $conditionUuidsForEncounter,
                ]);
                $this->updateEncounterFinished($encounterUuid, $finishedData);

            } catch (\Exception $e) {
                Log::error("Retry Condition: Encounter finalize gagal [{$nomorKunjungan}]: ".$e->getMessage());
                $results['errors'][] = 'Encounter Finalize Update: '.$e->getMessage();
            }
        }

        if ($resourceType === 'Observation') {
            // Ambil vital types yang gagal dari log lama
            $failedLogs = TrxSatusehatLog::where('nomor_kunjungan', $nomorKunjungan)
                ->where('resource_type', 'Observation')
                ->where('status', 'Failed')
                ->get();

            $failedVitalTypes = [];
            foreach ($failedLogs as $log) {
                $request = $log->request_json ?? [];
                // Extract loinc code dari request_json
                if (isset($request['code']['coding'][0]['code'])) {
                    $loincCode = $request['code']['coding'][0]['code'];
                    $failedVitalTypes[] = $loincCode;
                }
                // Untuk blood pressure, cek component
                if (isset($request['component']) && is_array($request['component'])) {
                    $failedVitalTypes[] = 'blood_pressure';
                    break;
                }
            }

            if (empty($failedVitalTypes)) {
                $results['items'][] = ['type' => '-', 'status' => 'Skipped', 'message' => 'Tidak ada resource Observation yang gagal'];

                return $results;
            }

            try {
                $obsResults = $this->createObservationAllVitalSigns([
                    'pendaftaran' => $pendaftaran,
                    'encounter_uuid' => $encounterUuid,
                    'created_by' => $createdBy,
                    'retry_vital_types' => array_unique($failedVitalTypes),
                ]);

                foreach ($obsResults as $vitalType => $obsResult) {
                    if (isset($obsResult['error'])) {
                        $results['items'][] = ['type' => $vitalType, 'status' => 'Failed', 'error' => $obsResult['error']];
                        $results['errors'][] = $obsResult['error'];
                    } else {
                        $obsUuid = $obsResult['id'] ?? null;
                        $results['items'][] = ['type' => $vitalType, 'uuid' => $obsUuid, 'status' => 'Success'];
                    }
                }
            } catch (\Exception $e) {
                $results['items'][] = ['type' => 'all', 'status' => 'Failed', 'error' => $e->getMessage()];
                $results['errors'][] = $e->getMessage();
            }
        }

        if ($resourceType === 'Procedure') {
            // Ambil procedure codes yang gagal dari log lama
            $failedLogs = TrxSatusehatLog::where('nomor_kunjungan', $nomorKunjungan)
                ->where('resource_type', 'Procedure')
                ->where('status', 'Failed')
                ->get();

            $failedProcedureCodes = [];
            foreach ($failedLogs as $log) {
                $request = $log->request_json ?? [];
                if (isset($request['code']['coding'][0]['code'])) {
                    $failedProcedureCodes[] = $request['code']['coding'][0]['code'];
                }
            }

            if (empty($failedProcedureCodes)) {
                $results['items'][] = ['code' => '-', 'status' => 'Skipped', 'message' => 'Tidak ada resource Procedure yang gagal'];

                return $results;
            }

            try {
                $procResults = $this->createProcedureAllTindakan([
                    'pendaftaran' => $pendaftaran,
                    'encounter_uuid' => $encounterUuid,
                    'created_by' => $createdBy,
                    'retry_procedure_codes' => array_unique($failedProcedureCodes),
                ]);

                foreach ($procResults as $tindakanCode => $procResult) {
                    if (isset($procResult['error'])) {
                        $results['items'][] = ['code' => $tindakanCode, 'status' => 'Failed', 'error' => $procResult['error']];
                        $results['errors'][] = $procResult['error'];
                    } else {
                        $procUuid = $procResult['id'] ?? null;
                        $results['items'][] = ['code' => $tindakanCode, 'uuid' => $procUuid, 'status' => 'Success'];
                    }
                }
            } catch (\Exception $e) {
                $results['items'][] = ['code' => 'all', 'status' => 'Failed', 'error' => $e->getMessage()];
                $results['errors'][] = $e->getMessage();
            }
        }

        if ($resourceType === 'Composition') {
            try {
                $compositionData = [
                    'pasien' => $pasien,
                    'dokter' => $dokter,
                    'encounter_uuid' => $encounterUuid,
                    'encounter_display' => "Kunjungan {$pasien->nama_pasien} tanggal ".($pendaftaran->created_at ? $pendaftaran->created_at->format('d/m/Y') : '-'),
                    'date' => $pendaftaran->created_at ? $pendaftaran->created_at->format('Y-m-d') : now()->format('Y-m-d'),
                    'nomor_kunjungan' => $nomorKunjungan,
                    'created_by' => $createdBy,
                ];

                $compositionResult = $this->createComposition($compositionData);
                $compUuid = $compositionResult['id'] ?? null;

                $results['items'][] = ['type' => 'Composition', 'uuid' => $compUuid, 'status' => 'Success'];
            } catch (\Exception $e) {
                $results['items'][] = ['type' => 'Composition', 'status' => 'Failed', 'error' => $e->getMessage()];
                $results['errors'][] = $e->getMessage();
            }
        }

        return $results;
    }

    // ==========================================
    // COMPOSITION RESOURCE
    // ==========================================

    /**
     * Search Composition by Patient Subject UUID.
     * GET {baseUrl}/Composition?subject={subjectUuid}
     */
    public function searchCompositionBySubject(string $subjectUuid)
    {
        $url = $this->getBaseUrl().'/Composition';
        $params = ['subject' => $subjectUuid];

        $response = Http::withHeaders($this->getHeaders())->get($url, $params);

        if ($response->successful() && ! empty($response->json()['entry'])) {
            return $response->json()['entry'];
        }

        return null;
    }

    /**
     * Search Composition by Subject and Encounter UUID.
     * GET {baseUrl}/Composition?subject={subjectUuid}&encounter={encounterUuid}
     */
    public function searchCompositionBySubjectAndEncounter(string $subjectUuid, string $encounterUuid)
    {
        $url = $this->getBaseUrl().'/Composition';
        $params = [
            'subject' => $subjectUuid,
            'encounter' => $encounterUuid,
        ];

        $response = Http::withHeaders($this->getHeaders())->get($url, $params);

        if ($response->successful() && ! empty($response->json()['entry'])) {
            return $response->json()['entry'];
        }

        return null;
    }

    /**
     * Search Composition by Encounter UUID.
     * GET {baseUrl}/Composition?encounter={encounterUuid}
     */
    public function searchCompositionByEncounter(string $encounterUuid)
    {
        $url = $this->getBaseUrl().'/Composition';
        $params = ['encounter' => $encounterUuid];

        $response = Http::withHeaders($this->getHeaders())->get($url, $params);

        if ($response->successful() && ! empty($response->json()['entry'])) {
            return $response->json()['entry'];
        }

        return null;
    }

    /**
     * Get a specific Composition by its UUID (path variable).
     * GET {baseUrl}/Composition/{id}
     */
    public function getCompositionById(string $compositionUuid)
    {
        $url = $this->getBaseUrl().'/Composition/'.$compositionUuid;

        $response = Http::withHeaders($this->getHeaders())->get($url);

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    /**
     * Format Composition payload for FHIR R4
     */
    protected function formatCompositionPayload(array $data, ?string $id = null)
    {
        $instansi = MstInstansi::first();
        if (! $instansi) {
            throw new \Exception('Data profil klinik (mst_instansi) tidak ditemukan.');
        }

        $pasien = $data['pasien'];
        $dokter = $data['dokter'];
        $encounterUuid = $data['encounter_uuid'];
        $encounterDisplay = $data['encounter_display'] ?? 'Kunjungan '.$pasien->nama_pasien;
        $date = $data['date'] ?? now()->format('Y-m-d');
        $nomor_kunjungan = $data['nomor_kunjungan'];

        $pemeriksaan = DB::table('trx_pemeriksaan')->where('nomor_kunjungan', $nomor_kunjungan)->first();
        $text = $pemeriksaan && ! empty($pemeriksaan->rekomendasi_diet)
            ? $pemeriksaan->rekomendasi_diet
            : 'Tidak ada rekomendasi khusus';

        $payload = [
            'resourceType' => 'Composition',
            'identifier' => [
                'system' => 'http://sys-ids.kemkes.go.id/composition/'.$instansi->organization_id,
                'value' => $nomor_kunjungan,
            ],
            'status' => 'final',
            'type' => [
                'coding' => [
                    [
                        'system' => 'http://loinc.org',
                        'code' => '18842-5',
                        'display' => 'Discharge summary',
                    ],
                ],
            ],
            'category' => [
                [
                    'coding' => [
                        [
                            'system' => 'http://loinc.org',
                            'code' => 'LP173421-1',
                            'display' => 'Report',
                        ],
                    ],
                ],
            ],
            'subject' => [
                'reference' => 'Patient/'.$pasien->satusehat_uuid,
                'display' => $pasien->nama_pasien,
            ],
            'encounter' => [
                'reference' => 'Encounter/'.$encounterUuid,
                'display' => $encounterDisplay,
            ],
            'date' => $date,
            'author' => [
                [
                    'reference' => 'Practitioner/'.$dokter->practitioner_id,
                    'display' => $dokter->nama_dokter,
                ],
            ],
            'title' => 'Resume Medis Rawat Jalan',
            'custodian' => [
                'reference' => 'Organization/'.$instansi->organization_id,
            ],
            'section' => [
                [
                    'code' => [
                        'coding' => [
                            [
                                'system' => 'http://loinc.org',
                                'code' => '42344-2',
                                'display' => 'Discharge diet (narrative)',
                            ],
                        ],
                    ],
                    'text' => [
                        'status' => 'additional',
                        'div' => $text,
                    ],
                ],
            ],
        ];

        if ($id) {
            $payload['id'] = $id;
        }

        return $payload;
    }

    /**
     * Create a new Composition.
     * POST {baseUrl}/Composition
     */
    public function createComposition(array $data)
    {
        $instansi = MstInstansi::first();
        if (! $instansi) {
            throw new \Exception('Data profil klinik (mst_instansi) tidak ditemukan.');
        }

        $body = $this->formatCompositionPayload($data);
        $pasien = $data['pasien'];

        $url = $this->getBaseUrl().'/Composition';
        $response = Http::withHeaders($this->getHeaders())->post($url, $body);

        if ($response->successful()) {
            $result = $response->json();
            $this->logSatusehatData(
                $instansi->organization_id,
                $result['id'] ?? null,
                $body,
                'Success',
                $data['nomor_kunjungan'] ?? null,
                'Composition',
                $pasien->satusehat_uuid ?? null,
                $result,
                null,
                $data['created_by'] ?? null
            );

            return $result;
        }

        $this->logSatusehatData(
            $instansi->organization_id,
            null,
            $body,
            'Failed',
            $data['nomor_kunjungan'] ?? null,
            'Composition',
            $pasien->satusehat_uuid ?? null,
            [],
            $response->body(),
            $data['created_by'] ?? null
        );
        throw new \Exception('Gagal create Composition di SatuSehat: '.$response->body());
    }

    /**
     * Update an existing Composition.
     * PUT {baseUrl}/Composition/{id}
     */
    public function updateComposition(string $compositionUuid, array $data)
    {
        $instansi = MstInstansi::first();
        if (! $instansi) {
            throw new \Exception('Data profil klinik (mst_instansi) tidak ditemukan.');
        }

        $body = $this->formatCompositionPayload($data, $compositionUuid);
        $pasien = $data['pasien'];

        $url = $this->getBaseUrl().'/Composition/'.$compositionUuid;
        $response = Http::withHeaders($this->getHeaders())->put($url, $body);

        if ($response->successful()) {
            $result = $response->json();
            $this->logSatusehatData(
                $instansi->organization_id,
                $result['id'] ?? null,
                $body,
                'Success',
                $data['nomor_kunjungan'] ?? null,
                'Composition',
                $pasien->satusehat_uuid ?? null,
                $result,
                null,
                $data['created_by'] ?? null
            );

            return $result;
        }

        $this->logSatusehatData(
            $instansi->organization_id,
            $compositionUuid,
            $body,
            'Failed',
            $data['nomor_kunjungan'] ?? null,
            'Composition',
            $pasien->satusehat_uuid ?? null,
            [],
            $response->body(),
            $data['created_by'] ?? null
        );
        throw new \Exception('Gagal update Composition di SatuSehat: '.$response->body());
    }

    /**
     * Request a fresh token from SatuSehat OAuth2 endpoint.
     */
    public function requestNewToken()
    {
        $baseUrl = $this->settings->token_url ?: 'https://api-satusehat.kemkes.go.id/oauth2/v1';
        $url = rtrim($baseUrl, '/').'/accesstoken';

        try {
            $response = Http::asForm()
                ->withoutVerifying()
                ->withQueryParameters([
                    'grant_type' => 'client_credentials',
                ])
                ->post($url, [
                    'client_id' => trim($this->clientId),
                    'client_secret' => trim($this->clientSecret),
                ]);

            if ($response->successful()) {
                $data = $response->json();

                return $data['access_token'] ?? null;
            }

            Log::error('SatuSehat Auth Error: '.$response->body());
            throw new \Exception('Gagal mendapatkan token SatuSehat: '.($response->json()['message'] ?? 'Unknown Error'));
        } catch (\Exception $e) {
            Log::error('SatuSehat Connection Exception: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Clear cached token (use this if getting 401 Unauthorized from clinical APIs).
     */
    public function clearToken()
    {
        if ($this->clientId) {
            $cacheKey = 'satusehat_access_token_'.md5($this->clientId);
            Cache::forget($cacheKey);
        }
    }

    /**
     * Get standard FHIR headers for subsequent requests.
     */
    public function getHeaders()
    {
        return [
            'Authorization' => 'Bearer '.$this->getToken(),
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

    // ==========================================
    // KFA KEMENKES V2 API
    // ==========================================

    /**
     * Utility to get KFA Base URL
     */
    public function getKfaBaseUrl()
    {
        return 'https://api-satusehat-stg.kemkes.go.id/kfa-v2';
    }

    /**
     * Cari produk farmasi di KFA
     */
    public function searchKfaProduct(string $keyword, int $page = 1, int $size = 100)
    {
        $url = $this->getKfaBaseUrl().'/products/all';
        $params = [
            'page' => $page,
            'size' => $size,
            'product_type' => 'farmasi',
            'keyword' => $keyword,
        ];

        $response = Http::withHeaders($this->getHeaders())->get($url, $params);

        try {
            LogSatusehat::create([
                'request_json' => json_encode(['url' => $url, 'params' => $params]),
                'response_json' => $response->body(),
                'status' => $response->status(),
            ]);
        } catch (\Exception $e) {
            Log::warning('Gagal menyimpan log KFA: '.$e->getMessage());
        }

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('Gagal mencari produk KFA: '.$response->body());
    }

    /**
     * Simpan data KFA dari response API ke tabel mst_kfa_obat dan mst_kfa_ingredient
     */
    public function syncKfaProduct(array $kfaData)
    {
        $kfaCode = $kfaData['kfa_code'] ?? $kfaData['product_template_code'] ?? null;
        if (! $kfaCode) {
            $kfaCode = $kfaData['kode_kfa'] ?? null; // Try another common key mapping
            if (! $kfaCode) {
                throw new \Exception('Data KFA tidak memiliki kfa_code yang valid.');
            }
        }

        // Manufacturer logic
        $manufacturer = $kfaData['manufacturer'] ?? null;
        if (! $manufacturer && isset($kfaData['kfa_poa']['name'])) {
            $manufacturer = $kfaData['kfa_poa']['name'];
        }

        $obatKfa = MstKfaObat::updateOrCreate(
            ['kfa_code' => (string) $kfaCode],
            [
                'name' => $kfaData['name'] ?? $kfaData['nama_produk'] ?? null,
                'manufacturer' => $manufacturer,
                'dosage_form_code' => $kfaData['dosage_form']['code'] ?? null,
                'dosage_form_name' => $kfaData['dosage_form']['name'] ?? null,
                'produk_template_kfa' => $kfaData['kfa_code'] ?? null,
                'last_synced_at' => now(),
            ]
        );

        // Process ingredients if they exist
        if (isset($kfaData['active_ingredients']) && is_array($kfaData['active_ingredients'])) {
            $obatKfa->ingredients()->delete();

            foreach ($kfaData['active_ingredients'] as $ingredient) {
                // Determine active substance (zat_aktif)
                $zatAktif = null;
                if (isset($ingredient['kfa_poa']['name'])) {
                    $zatAktif = $ingredient['kfa_poa']['name'];
                } elseif (isset($ingredient['name'])) {
                    $zatAktif = $ingredient['name'];
                } elseif (isset($ingredient['zat_aktif'])) {
                    $zatAktif = $ingredient['zat_aktif'];
                }

                $obatKfa->ingredients()->create([
                    'zat_aktif' => $zatAktif,
                    'kfa_code_ingredient' => $ingredient['kfa_code'] ?? null,
                    'kekuatan_zat_aktif' => $ingredient['kekuatan_zat_aktif'] ?? $ingredient['strength'] ?? $ingredient['kekuatan'] ?? null,
                ]);
            }
        }

        return $obatKfa;
    }
}
