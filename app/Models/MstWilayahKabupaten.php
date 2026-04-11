<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstWilayahKabupaten extends Model
{
    protected $table = 'mst_wilayah_kabupaten';
    protected $primaryKey = 'kode';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['kode', 'provinsi_kode', 'nama'];

    public function provinsi()
    {
        return $this->belongsTo(MstWilayahProvinsi::class, 'provinsi_kode', 'kode');
    }

    public function kecamatan()
    {
        return $this->hasMany(MstWilayahKecamatan::class, 'kabupaten_kode', 'kode');
    }
}
