<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstWilayahKecamatan extends Model
{
    protected $table = 'mst_wilayah_kecamatan';
    protected $primaryKey = 'kode';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['kode', 'kabupaten_kode', 'nama'];

    public function kabupaten()
    {
        return $this->belongsTo(MstWilayahKabupaten::class, 'kabupaten_kode', 'kode');
    }

    public function kelurahan()
    {
        return $this->hasMany(MstWilayahKelurahan::class, 'kecamatan_kode', 'kode');
    }
}
