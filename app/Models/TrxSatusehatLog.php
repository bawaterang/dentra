<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class TrxSatusehatLog extends Model
{
    use HasFactory;

    protected $table = 'trx_satusehat_log';

    protected $guarded = ['id'];

    protected $casts = [
        'request_json' => 'array',
        'response_json' => 'array',
    ];

    public function pendaftaran(): BelongsTo
    {
        return $this->belongsTo(TrxPendaftaran::class, 'nomor_kunjungan', 'nomor_kunjungan');
    }

    public static function latestByResourceType(string $nomorKunjungan, string $resourceType): ?self
    {
        return static::where('nomor_kunjungan', $nomorKunjungan)
            ->where('resource_type', $resourceType)
            ->latest()
            ->first();
    }

    public static function latestByResourceTypeGrouped(string $nomorKunjungan): Collection
    {
        return static::where('nomor_kunjungan', $nomorKunjungan)
            ->orderBy('resource_type')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('resource_type');
    }
}
