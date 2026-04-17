<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrxPenunjangDokumen extends Model
{
    use SoftDeletes;

    protected $table = 'trx_penunjang_dokumen';

    protected $fillable = [
        'nomor_kunjungan',
        'no_rm',
        'document_name',
        'jenis',
        'file_path',
        'created_by',
    ];

    public function pendaftaran()
    {
        return $this->belongsTo(TrxPendaftaran::class, 'nomor_kunjungan', 'nomor_kunjungan');
    }
}
