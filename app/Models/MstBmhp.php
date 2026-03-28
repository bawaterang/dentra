<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class MstBmhp extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mst_bmhp';

    protected $fillable = [
        'kode_bmhp',
        'nama_bmhp',
        'satuan',
        'stok',
        'stok_minimal',
        'harga_satuan',
        'keterangan',
        'status',
    ];
}
