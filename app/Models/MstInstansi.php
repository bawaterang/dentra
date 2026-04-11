<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MstInstansi extends Model
{
    use HasFactory;

    protected $table = 'mst_instansi';

    protected $fillable = [
        'organization_id',
        'nama_instansi',
        'alamat',
        'kode_pos',
        'provinsi_id',
        'kabupaten_id',
        'kecamatan_id',
        'kelurahan_id',
        'telepon',
        'email',
        'website',
        'logo',
        'pimpinan',
    ];
}

