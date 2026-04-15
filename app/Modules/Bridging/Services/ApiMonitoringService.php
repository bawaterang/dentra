<?php

namespace App\Modules\Bridging\Services;

use App\Models\MstSettingBpjs;
use App\Models\MstSettingSatusehat;
use App\Models\TrxApiMonitoringLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiMonitoringService
{
    /**
     * Perform a comprehensive check on SatuSehat API.
     *
     * @return array Result of the check
     */
    public function checkSatuSehat(?string $checkedBy = null, string $endpointType = 'organization'): array
    {
        $settings = MstSettingSatusehat::first();
        $result = [
            'api_type' => 'satusehat',
            'endpoint_url' => '',
            'http_status_code' => null,
            'is_up' => false,
            'response_time_ms' => null,
            'token_status' => 'error',
            'error_message' => null,
            'request_headers' => [],
            'response_headers' => [],
            'response_body' => null,
            'cpu_usage' => null,
            'memory_usage_mb' => null,
            'checked_by' => $checkedBy,
        ];

        if (!$settings || !$settings->client_id || !$settings->client_secret) {
            $result['error_message'] = 'Konfigurasi SatuSehat belum lengkap. Silakan isi Client ID dan Client Secret di Setting API.';
            $this->saveLog($result);
            return $result;
        }

        // Measure system resources before request
        $cpuBefore = $this->getCpuUsage();
        $memBefore = memory_get_usage(true) / 1024 / 1024;

        // Step 1: Test token acquisition
        $tokenUrl = rtrim($settings->token_url ?: 'https://api-satusehat.kemkes.go.id/oauth2/v1', '/') . '/accesstoken';
        $result['endpoint_url'] = $tokenUrl;

        $startTime = microtime(true);

        try {
            $tokenResponse = Http::asForm()
                ->withoutVerifying()
                ->timeout(30)
                ->withQueryParameters(['grant_type' => 'client_credentials'])
                ->post($tokenUrl, [
                    'client_id' => trim($settings->client_id),
                    'client_secret' => trim($settings->client_secret),
                ]);

            $tokenElapsed = (int) round((microtime(true) - $startTime) * 1000);

            $result['http_status_code'] = $tokenResponse->status();
            $result['response_headers'] = $this->sanitizeHeaders($tokenResponse->headers());
            $result['request_headers'] = [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'grant_type' => 'client_credentials',
                'client_id' => $this->maskString($settings->client_id),
            ];

            if ($tokenResponse->successful()) {
                $tokenData = $tokenResponse->json();
                $accessToken = $tokenData['access_token'] ?? null;

                if ($accessToken) {
                    $result['token_status'] = 'valid';

                    // Step 2: Test FHIR endpoint with obtained token
                    $fhirUrl = rtrim($settings->url ?: 'https://api-satusehat.kemkes.go.id/fhir-r4/v1', '/');
                    $orgId = $settings->organization_id;

                    $testEndpoint = '';
                    if ($endpointType === 'organization') {
                        $orgId = \DB::table('mst_instansi')->whereNotNull('organization_id')->value('organization_id');
                        if (!$orgId) $orgId = $settings->organization_id; // Fallback to setting

                        if ($orgId) {
                            $testEndpoint = $fhirUrl . '/Organization/' . $orgId;
                        } else {
                            $result['response_time_ms'] = $tokenElapsed;
                            $result['is_up'] = true;
                            $result['endpoint_url'] = $tokenUrl;
                            $result['response_body'] = json_encode(['message' => 'Token berhasil didapatkan. Organization ID belum diset di master instansi maupun setting.']);
                        }
                    } elseif ($endpointType === 'location') {
                        $locId = \DB::table('mst_location')->where('status', true)->whereNotNull('location_id')->value('location_id');
                        $testEndpoint = $locId ? $fhirUrl . '/Location/' . $locId : $fhirUrl . '/Location?_count=1';
                    } elseif ($endpointType === 'practitioner') {
                        $pracId = \DB::table('mst_dokter')->where('status', true)->whereNotNull('practitioner_id')->value('practitioner_id');
                        $testEndpoint = $pracId ? $fhirUrl . '/Practitioner/' . $pracId : $fhirUrl . '/Practitioner?_count=1';
                    } elseif ($endpointType === 'patient') {
                        $patId = \DB::table('mst_pasien')->where('status', true)->whereNotNull('satusehat_uuid')->value('satusehat_uuid');
                        $testEndpoint = $patId ? $fhirUrl . '/Patient/' . $patId : $fhirUrl . '/Patient?_count=1';
                    } elseif (in_array($endpointType, ['encounter', 'condition', 'observation', 'procedure', 'composition'])) {
                        $resourceType = ucfirst($endpointType);
                        $resId = \DB::table('trx_satusehat_log')
                            ->where('resource_type', $resourceType)
                            ->where('status', 'like', '%uccess%')
                            ->whereNotNull('resource_uuid')
                            ->value('resource_uuid');
                        $testEndpoint = $resId ? $fhirUrl . '/' . $resourceType . '/' . $resId : $fhirUrl . '/' . $resourceType . '?_count=1';
                    }

                    if ($testEndpoint) {
                        $result['endpoint_url'] = $testEndpoint;

                        $fhirStart = microtime(true);

                        $fhirResponse = Http::withHeaders([
                            'Authorization' => 'Bearer ' . $accessToken,
                            'Content-Type' => 'application/json',
                        ])
                        ->withoutVerifying()
                        ->timeout(30)
                        ->get($testEndpoint);

                        $fhirElapsed = (int) round((microtime(true) - $fhirStart) * 1000);

                        $result['http_status_code'] = $fhirResponse->status();
                        $result['response_time_ms'] = $fhirElapsed;
                        $result['response_headers'] = $this->sanitizeHeaders($fhirResponse->headers());
                        $result['request_headers'] = [
                            'Authorization' => 'Bearer ' . $this->maskString($accessToken, 10),
                            'Content-Type' => 'application/json',
                        ];
                        $result['response_body'] = mb_substr($fhirResponse->body(), 0, 2000);
                        $result['is_up'] = $fhirResponse->successful();

                        if (!$fhirResponse->successful()) {
                            $result['error_message'] = 'FHIR Endpoint Error (HTTP ' . $fhirResponse->status() . '): ' . mb_substr($fhirResponse->body(), 0, 500);
                            
                            if ($fhirResponse->status() === 401) {
                                $result['token_status'] = 'expired';
                            }
                        }
                    }
                } else {
                    $result['token_status'] = 'invalid';
                    $result['error_message'] = 'Token response tidak mengandung access_token.';
                    $result['response_time_ms'] = $tokenElapsed;
                    $result['response_body'] = mb_substr($tokenResponse->body(), 0, 2000);
                }
            } else {
                $result['token_status'] = 'invalid';
                $result['response_time_ms'] = $tokenElapsed;
                $result['response_body'] = mb_substr($tokenResponse->body(), 0, 2000);

                $errorBody = $tokenResponse->json();
                $result['error_message'] = 'Autentikasi gagal (HTTP ' . $tokenResponse->status() . '): '
                    . ($errorBody['error_description'] ?? $errorBody['message'] ?? 'Unknown error');
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $elapsed = (int) round((microtime(true) - $startTime) * 1000);
            $result['response_time_ms'] = $elapsed;
            $result['error_message'] = 'Connection Timeout / DNS Error: ' . $e->getMessage();
            $result['token_status'] = 'error';
        } catch (\Exception $e) {
            $elapsed = (int) round((microtime(true) - $startTime) * 1000);
            $result['response_time_ms'] = $elapsed;
            $result['error_message'] = 'Exception: ' . $e->getMessage();
            $result['token_status'] = 'error';
        }

        // Measure system resources after request
        $cpuAfter = $this->getCpuUsage();
        $memAfter = memory_get_usage(true) / 1024 / 1024;

        $result['cpu_usage'] = round($cpuAfter, 2);
        $result['memory_usage_mb'] = round($memAfter, 2);

        $this->saveLog($result);
        return $result;
    }

    /**
     * Perform a comprehensive check on BPJS API.
     *
     * @return array Result of the check
     */
    public function checkBpjs(?string $checkedBy = null, string $endpointType = 'referensi_poli'): array
    {
        $settings = MstSettingBpjs::first();
        $result = [
            'api_type' => 'bpjs',
            'endpoint_url' => '',
            'http_status_code' => null,
            'is_up' => false,
            'response_time_ms' => null,
            'token_status' => 'error',
            'error_message' => null,
            'request_headers' => [],
            'response_headers' => [],
            'response_body' => null,
            'cpu_usage' => null,
            'memory_usage_mb' => null,
            'checked_by' => $checkedBy,
        ];

        if (!$settings || !$settings->consid || !$settings->secret_key) {
            $result['error_message'] = 'Konfigurasi BPJS belum lengkap. Silakan isi ConsID dan Secret Key di Setting API.';
            $this->saveLog($result);
            return $result;
        }

        // Build BPJS authentication headers
        $consid = trim($settings->consid);
        $secretKey = trim($settings->secret_key);
        $userKey = trim($settings->user_key ?? '');

        // Construct timestamp & signature per BPJS spec
        $timestamp = gmdate('U');
        $signature = hash_hmac('sha256', $consid . '&' . $timestamp, $secretKey, true);
        $encodedSignature = base64_encode($signature);

        // Build auth string: base64(consid:pwd:timestamp)
        $username = trim($settings->username ?? '');
        $password = trim($settings->password ?? '');
        $kdAplikasi = trim($settings->kd_aplikasi ?? '');
        $authString = base64_encode($username . ':' . $password . ':' . $kdAplikasi);

        $baseUrl = rtrim($settings->base_url ?? 'https://apijkn.bpjs-kesehatan.go.id/vclaim-rest', '/');

        if ($endpointType === 'referensi_poli') {
            $testEndpoint = $baseUrl . '/referensi/poli/1/10';
        } elseif ($endpointType === 'referensi_faskes') {
            $testEndpoint = $baseUrl . '/referensi/faskes/01140131/1';
        } elseif ($endpointType === 'referensi_dokter') {
            $testEndpoint = $baseUrl . '/referensi/dokter/pelayanan/1/tglPelayanan/' . date('Y-m-d');
        } elseif ($endpointType === 'referensi_diagnosa') {
            $testEndpoint = $baseUrl . '/referensi/diagnosa/A00';
        } else {
            $testEndpoint = $baseUrl . '/referensi/poli/1/10';
        }
        
        $result['endpoint_url'] = $testEndpoint;

        $requestHeaders = [
            'X-cons-id' => $consid,
            'X-timestamp' => $timestamp,
            'X-signature' => $encodedSignature,
            'user_key' => $userKey,
            'Content-Type' => 'application/json',
        ];

        $result['request_headers'] = [
            'X-cons-id' => $this->maskString($consid),
            'X-timestamp' => $timestamp,
            'X-signature' => $this->maskString($encodedSignature, 8),
            'user_key' => $this->maskString($userKey),
            'Content-Type' => 'application/json',
        ];

        // Measure system resources
        $cpuBefore = $this->getCpuUsage();
        $memBefore = memory_get_usage(true) / 1024 / 1024;

        $startTime = microtime(true);

        try {
            $response = Http::withHeaders($requestHeaders)
                ->withoutVerifying()
                ->timeout(30)
                ->get($testEndpoint);

            $elapsed = (int) round((microtime(true) - $startTime) * 1000);

            $result['http_status_code'] = $response->status();
            $result['response_time_ms'] = $elapsed;
            $result['response_headers'] = $this->sanitizeHeaders($response->headers());
            $result['response_body'] = mb_substr($response->body(), 0, 2000);

            if ($response->successful()) {
                $body = $response->json();
                $metaCode = $body['metaData']['code'] ?? $body['metadata']['code'] ?? null;

                if ($metaCode === '200' || $metaCode === 200) {
                    $result['is_up'] = true;
                    $result['token_status'] = 'valid';
                } elseif ($metaCode === '401' || $metaCode === 401) {
                    $result['is_up'] = true; // Server reachable, but auth failed
                    $result['token_status'] = 'invalid';
                    $result['error_message'] = 'Autentikasi BPJS gagal: ' . ($body['metaData']['message'] ?? $body['metadata']['message'] ?? 'Invalid credentials');
                } else {
                    // Server responded with other code
                    $result['is_up'] = true;
                    $result['token_status'] = 'valid';
                    $msg = $body['metaData']['message'] ?? $body['metadata']['message'] ?? 'Unknown response';
                    $result['error_message'] = 'BPJS Response Code ' . $metaCode . ': ' . $msg;
                }
            } else {
                $result['error_message'] = 'BPJS HTTP Error ' . $response->status() . ': ' . mb_substr($response->body(), 0, 500);

                if ($response->status() === 401 || $response->status() === 403) {
                    $result['token_status'] = 'invalid';
                } elseif ($response->status() >= 500) {
                    $result['token_status'] = 'error';
                    $result['error_message'] = 'BPJS Server Error (HTTP ' . $response->status() . ')';
                }
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $elapsed = (int) round((microtime(true) - $startTime) * 1000);
            $result['response_time_ms'] = $elapsed;
            $result['error_message'] = 'Connection Timeout / DNS Error: ' . $e->getMessage();
        } catch (\Exception $e) {
            $elapsed = (int) round((microtime(true) - $startTime) * 1000);
            $result['response_time_ms'] = $elapsed;
            $result['error_message'] = 'Exception: ' . $e->getMessage();
        }

        // Measure system resources after request
        $cpuAfter = $this->getCpuUsage();
        $memAfter = memory_get_usage(true) / 1024 / 1024;

        $result['cpu_usage'] = round($cpuAfter, 2);
        $result['memory_usage_mb'] = round($memAfter, 2);

        $this->saveLog($result);
        return $result;
    }

    /**
     * Get recent monitoring logs for the given API type.
     */
    public function getRecentLogs(string $apiType, int $limit = 20)
    {
        return TrxApiMonitoringLog::recentLogs($apiType, $limit);
    }

    /**
     * Get uptime stats for the given API type.
     */
    public function getUptimeStats(string $apiType, int $days = 7): array
    {
        return [
            'uptime_percentage' => TrxApiMonitoringLog::uptimePercentage($apiType, $days),
            'avg_response_time' => round(TrxApiMonitoringLog::averageResponseTime($apiType, $days)),
            'error_rate' => TrxApiMonitoringLog::errorRate($apiType, $days),
            'total_checks' => TrxApiMonitoringLog::where('api_type', $apiType)
                ->where('created_at', '>=', now()->subDays($days))
                ->count(),
        ];
    }

    /**
     * Clear old monitoring logs.
     */
    public function clearLogs(string $apiType, ?int $keepLast = null): int
    {
        $query = TrxApiMonitoringLog::where('api_type', $apiType);

        if ($keepLast) {
            $cutoffId = TrxApiMonitoringLog::where('api_type', $apiType)
                ->orderBy('id', 'desc')
                ->skip($keepLast)
                ->value('id');

            if ($cutoffId) {
                return TrxApiMonitoringLog::where('api_type', $apiType)
                    ->where('id', '<=', $cutoffId)
                    ->delete();
            }
            return 0;
        }

        return $query->delete();
    }

    /**
     * Save the check result to the log table.
     */
    protected function saveLog(array $data): void
    {
        try {
            TrxApiMonitoringLog::create($data);
        } catch (\Exception $e) {
            Log::warning('Gagal menyimpan log API monitoring: ' . $e->getMessage());
        }
    }

    /**
     * Mask a sensitive string, showing only the first few characters.
     */
    protected function maskString(?string $value, int $show = 4): string
    {
        if (!$value || strlen($value) <= $show) {
            return str_repeat('*', 8);
        }
        return substr($value, 0, $show) . str_repeat('*', min(strlen($value) - $show, 20));
    }

    /**
     * Sanitize HTTP response headers for storage.
     */
    protected function sanitizeHeaders($headers): array
    {
        $result = [];
        if (is_array($headers)) {
            foreach ($headers as $key => $values) {
                $result[$key] = is_array($values) ? implode(', ', $values) : (string) $values;
            }
        } elseif (is_object($headers) && method_exists($headers, 'all')) {
            foreach ($headers->all() as $key => $values) {
                $result[$key] = is_array($values) ? implode(', ', $values) : (string) $values;
            }
        }
        return $result;
    }

    /**
     * Get current CPU usage percentage. Returns an approximation.
     */
    protected function getCpuUsage(): float
    {
        try {
            if (PHP_OS_FAMILY === 'Linux') {
                $load = sys_getloadavg();
                // Normalize to percentage (assuming single core baseline)
                return $load ? round($load[0] * 100 / max(1, (int) shell_exec('nproc')), 2) : 0;
            } elseif (PHP_OS_FAMILY === 'Darwin') {
                $load = sys_getloadavg();
                $cpuCount = (int) trim(shell_exec('sysctl -n hw.ncpu') ?: '1');
                return $load ? round($load[0] * 100 / max(1, $cpuCount), 2) : 0;
            }
        } catch (\Exception $e) {
            // Silently fail
        }
        return 0;
    }
}
