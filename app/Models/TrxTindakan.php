<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrxTindakan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'trx_tindakan';

    protected $fillable = [
        'nomor_kunjungan',
        'kode_tindakan',
        'kode_asuransi',
        'biaya',
        'jasa_medis',
        'satuan',
        'bhp',
        'created_by',
    ];

    public function tindakan()
    {
        return $this->belongsTo(MstTindakan::class, 'kode_tindakan', 'kode_tindakan');
    }

    public function pendaftaran()
    {
        return $this->belongsTo(TrxPendaftaran::class, 'nomor_kunjungan', 'nomor_kunjungan');
    }
}
