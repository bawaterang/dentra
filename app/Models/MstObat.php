<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class MstObat extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mst_obat';

    protected $fillable = [
        'kode_obat',
        'nama_obat',
        'satuan',
        'stok',
        'stok_minimal',
        'harga_beli',
        'harga_jual',
        'tanggal_beli',
        'tanggal_expired',
        'keterangan',
        'status',
    ];

    protected $casts = [
        'tanggal_beli' => 'date',
        'tanggal_expired' => 'date',
    ];

    public function kfaMapping()
    {
        return $this->hasOne(MstMapObatKfa::class, 'obat_id');
    }

    public function kfaObat()
    {
        return $this->hasOneThrough(MstKfaObat::class, MstMapObatKfa::class, 'obat_id', 'kfa_code', 'id', 'kfa_code');
    }
}
