<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrxKunjunganBpjs extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'trx_kunjungan_bpjs';

    protected $fillable = [
        'nomor_kunjungan',
        'pasien_id',
        'no_kunjungan_bpjs',
        'status',
        'created_by',
    ];

    /**
     * Relasi ke pendaftaran (kunjungan internal)
     */
    public function pendaftaran()
    {
        return $this->belongsTo(TrxPendaftaran::class, 'nomor_kunjungan', 'nomor_kunjungan');
    }

    /**
     * Relasi ke pasien
     */
    public function pasien()
    {
        return $this->belongsTo(MstPasien::class, 'pasien_id');
    }

    /**
     * Relasi ke user yang membuat
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
