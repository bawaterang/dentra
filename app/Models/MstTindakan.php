<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class MstTindakan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mst_tindakan';

    protected $fillable = [
        'kode_tindakan',
        'nama_tindakan',
        'kategori_tindakan',
        'icd9cm_code',
        'icd9cm_name',
        'snomed_code',
        'snomed_name',
        'harga_default',
        'deskripsi',
        'status',
    ];
}
