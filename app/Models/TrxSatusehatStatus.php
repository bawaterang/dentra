<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrxSatusehatStatus extends Model
{
    use HasFactory;

    protected $table = 'trx_satusehat_status';
    protected $guarded = ['id'];

    public function pendaftaran()
    {
        return $this->belongsTo(TrxPendaftaran::class, 'nomor_kunjungan', 'nomor_kunjungan');
    }
}
