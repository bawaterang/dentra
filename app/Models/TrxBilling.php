<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrxBilling extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'trx_billing';

    protected $fillable = [
        'nomor_kunjungan',
        'pasien_id',
        'no_faktur',
        'total_tagihan',
        'total_bayar',
        'kembalian',
        'hutang',
        'status',
        'tanggal_bayar',
        'created_by',
    ];

    protected $casts = [
        'total_tagihan' => 'decimal:2',
        'total_bayar' => 'decimal:2',
        'kembalian' => 'decimal:2',
        'hutang' => 'decimal:2',
        'tanggal_bayar' => 'datetime',
    ];

    public function pendaftaran()
    {
        return $this->belongsTo(TrxPendaftaran::class, 'nomor_kunjungan', 'nomor_kunjungan');
    }

    public function pasien()
    {
        return $this->belongsTo(MstPasien::class, 'pasien_id');
    }

    public function details()
    {
        return $this->hasMany(TrxBillingDetail::class, 'billing_id');
    }
}
