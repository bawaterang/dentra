<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrxDiagnosis extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'trx_diagnosis';

    protected $fillable = [
        'nomor_kunjungan',
        'kode_diagnosa',
        'jenis_icd',
        'kasus_icd',
        'created_by',
    ];

    /**
     * Relasi ke master diagnosis (ICD-10)
     */
    public function masterDiagnosis()
    {
        return $this->belongsTo(MstDiagnosis::class, 'kode_diagnosa', 'kode_diagnosa');
    }

    /**
     * Relasi ke pendaftaran
     */
    public function pendaftaran()
    {
        return $this->belongsTo(TrxPendaftaran::class, 'nomor_kunjungan', 'nomor_kunjungan');
    }
}
