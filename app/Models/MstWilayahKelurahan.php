<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstWilayahKelurahan extends Model
{
    protected $table = 'mst_wilayah_kelurahan';
    protected $primaryKey = 'kode';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['kode', 'kecamatan_kode', 'nama'];

    public function kecamatan()
    {
        return $this->belongsTo(MstWilayahKecamatan::class, 'kecamatan_kode', 'kode');
    }
}
