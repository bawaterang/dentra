<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstWilayahProvinsi extends Model
{
    protected $table = 'mst_wilayah_provinsi';
    protected $primaryKey = 'kode';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['kode', 'nama'];

    public function kabupaten()
    {
        return $this->hasMany(MstWilayahKabupaten::class, 'provinsi_kode', 'kode');
    }
}
