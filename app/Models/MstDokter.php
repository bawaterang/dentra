<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class MstDokter extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mst_dokter';

    protected $fillable = [
        'kode_dokter',
        'nama_dokter',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'alamat',
        'no_telepon',
        'agama',
        'nik',
        'spesialisasi',
        'no_sip',
        'no_str',
        'status',
        'color',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
    ];

    public function polis()
    {
        return $this->belongsToMany(MstPoli::class, 'mst_poli_dokter', 'dokter_id', 'poli_id')->withTimestamps();
    }
}
