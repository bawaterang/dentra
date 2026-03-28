<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class MstDiagnosis extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mst_diagnosis';

    protected $fillable = [
        'kode_diagnosa',
        'nama_diagnosa',
        'kategori',
        'deskripsi',
        'status',
    ];
}
