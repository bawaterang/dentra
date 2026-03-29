<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MstInstansi extends Model
{
    use HasFactory;

    protected $table = 'mst_instansi';

    protected $fillable = [
        'nama_instansi',
        'alamat',
        'telepon',
        'email',
        'website',
        'logo',
        'pimpinan',
    ];
}
