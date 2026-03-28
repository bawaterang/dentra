<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TrxPendaftaran extends Model
{
    use HasFactory;

    protected $table = 'trx_pendaftaran';

    protected $fillable = [
        'nomor_kunjungan',
        'antrian_id',
        'pasien_id',
        'poli_id',
        'dokter_id',
        'asuransi_id',
        'no_kartu_asuransi',
        'kesadaran',
        'tekanan_darah',
        'nadi',
        'suhu',
        'berat_badan',
        'tinggi_badan',
        'riwayat_penyakit',
        'alergi',
        'keterangan_lain',
        'status',
    ];

    protected $casts = [
        'suhu' => 'decimal:1',
        'berat_badan' => 'decimal:1',
        'tinggi_badan' => 'decimal:1',
    ];

    public function antrian()
    {
        return $this->belongsTo(TrxAntrian::class, 'antrian_id');
    }

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

    public function asuransi()
    {
        return $this->belongsTo(MstAsuransi::class, 'asuransi_id');
    }

    public function screenings()
    {
        return $this->hasMany(TrxScreening::class, 'pendaftaran_id');
    }

    /**
     * Generate nomor kunjungan otomatis format: YYYYMMDDXXXX
     */
    public static function generateNomorKunjungan(): string
    {
        $today = now()->format('Ymd');
        $last = self::where('nomor_kunjungan', 'like', $today . '%')
            ->orderBy('nomor_kunjungan', 'desc')
            ->first();

        if ($last) {
            $lastNumber = (int) substr($last->nomor_kunjungan, 8);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $today . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
