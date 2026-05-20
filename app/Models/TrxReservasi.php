<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrxReservasi extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'trx_reservasi';

    protected $fillable = [
        'kode_reservasi',
        'tanggal_reservasi',
        'time_slot',
        'pasien_id',
        'nama_pasien_manual',
        'no_telepon_manual',
        'nik_manual',
        'poli_id',
        'dokter_id',
        'keterangan',
        'status',
        'antrian_id',
        'created_by',
    ];

    protected $casts = [
        'tanggal_reservasi' => 'date',
    ];

    public function pasien()
    {
        return $this->belongsTo(MstPasien::class, 'pasien_id');
    }

    public function poli()
    {
        return $this->belongsTo(MstPoli::class, 'poli_id');
    }

    public function dokter()
    {
        return $this->belongsTo(MstDokter::class, 'dokter_id');
    }

    public function antrian()
    {
        return $this->belongsTo(TrxAntrian::class, 'antrian_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get display name — prioritize pasien relation, fallback to manual input.
     */
    public function getNamaPasienDisplayAttribute(): string
    {
        return $this->pasien?->nama_pasien ?? $this->nama_pasien_manual ?? '-';
    }

    /**
     * Generate unique reservation code: RSV-YYYYMMDD-XXXX
     */
    public static function generateKodeReservasi(): string
    {
        $today = now()->format('Ymd');
        $prefix = 'RSV-' . $today . '-';

        $last = self::withTrashed()
            ->where('kode_reservasi', 'like', $prefix . '%')
            ->orderBy('kode_reservasi', 'desc')
            ->first();

        if ($last) {
            $lastNum = (int) substr($last->kode_reservasi, -4);
            $next = $lastNum + 1;
        } else {
            $next = 1;
        }

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
