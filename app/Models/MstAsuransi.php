<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class MstAsuransi extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mst_asuransi';

    protected $fillable = [
        'kode_asuransi',
        'nama_asuransi',
        'tipe_asuransi',
        'diskon',
        'no_telepon',
        'email',
        'alamat',
        'status',
    ];
}
