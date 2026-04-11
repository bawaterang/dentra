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
        'satusehat_uuid',
        'no_rm',
        'nama_pasien',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'alamat',
        'kode_pos',
        'provinsi_id',
        'kabupaten_id',
        'kecamatan_id',
        'kelurahan_id',
        'no_telepon',
        'agama',
        'pekerjaan',
        'no_penjamin',
        'nik',
        'marital_status',
        'golongan_darah',
        'alergi',
        'status',
    ];


    protected $casts = [
        'tanggal_lahir' => 'date',
    ];
}
