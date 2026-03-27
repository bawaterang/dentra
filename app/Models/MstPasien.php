<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class MstPasien extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mst_pasien';

    protected $fillable = [
        'no_rm',
        'nama_pasien',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'alamat',
        'no_telepon',
        'agama',
        'pekerjaan',
        'no_penjamin',
        'nik',
        'golongan_darah',
        'alergi',
        'status',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
    ];
}
