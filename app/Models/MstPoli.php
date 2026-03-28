<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class MstPoli extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mst_poli';

    protected $fillable = [
        'kode_poli',
        'nama_poli',
        'status',
    ];
}
