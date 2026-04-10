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

        // Cache key unik per client_id agar token tidak tertukar antara klinik/dokter
        $cacheKey = 'satusehat_access_token_' . md5($this->clientId);

        return Cache::remember($cacheKey, 3500, function () {
            return $this->requestNewToken();
        });
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
