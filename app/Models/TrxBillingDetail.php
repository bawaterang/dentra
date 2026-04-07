<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrxBillingDetail extends Model
{
    use HasFactory;

    protected $table = 'trx_billing_detail';

    protected $fillable = [
        'billing_id',
        'kode_tindakan',
        'nama_tindakan',
        'biaya',
    ];

    protected $casts = [
        'biaya' => 'decimal:2',
    ];

    public function billing()
    {
        return $this->belongsTo(TrxBilling::class, 'billing_id');
    }
}
