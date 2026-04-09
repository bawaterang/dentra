<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrxSatusehatStatus extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'trx_satusehat_status';
    protected $guarded = ['id'];

    public function pendaftaran()
    {
        return $this->belongsTo(TrxPendaftaran::class, 'nomor_kunjungan', 'nomor_kunjungan');
    }
}
