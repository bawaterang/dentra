<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class MstTarif extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mst_tarif';

    protected $fillable = [
        'kode_tindakan',
        'kode_asuransi',
        'tarif',
        'jasmed',
        'satuan_jasmed',
        'bhp',
        'adm_fee',
        'satuan',
        'status',
    ];
}
