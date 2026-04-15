<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrxApiMonitoringLog extends Model
{
    protected $table = 'trx_api_monitoring_log';

    protected $guarded = ['id'];

    protected $casts = [
        'request_headers' => 'array',
        'response_headers' => 'array',
        'is_up' => 'boolean',
        'cpu_usage' => 'float',
        'memory_usage_mb' => 'float',
    ];

    /**
     * Get recent logs by API type.
     */
    public static function recentLogs(string $apiType, int $limit = 20)
    {
        return static::where('api_type', $apiType)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Calculate uptime percentage for the given API type over a number of days.
     */
    public static function uptimePercentage(string $apiType, int $days = 7): float
    {
        $since = now()->subDays($days);

        $total = static::where('api_type', $apiType)
            ->where('created_at', '>=', $since)
            ->count();

        if ($total === 0) {
            return 0;
        }

        $up = static::where('api_type', $apiType)
            ->where('created_at', '>=', $since)
            ->where('is_up', true)
            ->count();

        return round(($up / $total) * 100, 2);
    }

    /**
     * Get average response time for the given API type over a number of days.
     */
    public static function averageResponseTime(string $apiType, int $days = 7): float
    {
        $since = now()->subDays($days);

        return (float) static::where('api_type', $apiType)
            ->where('created_at', '>=', $since)
            ->whereNotNull('response_time_ms')
            ->avg('response_time_ms') ?? 0;
    }

    /**
     * Get error rate percentage for the given API type over a number of days.
     */
    public static function errorRate(string $apiType, int $days = 7): float
    {
        $since = now()->subDays($days);

        $total = static::where('api_type', $apiType)
            ->where('created_at', '>=', $since)
            ->count();

        if ($total === 0) {
            return 0;
        }

        $errors = static::where('api_type', $apiType)
            ->where('created_at', '>=', $since)
            ->where('is_up', false)
            ->count();

        return round(($errors / $total) * 100, 2);
    }
}
