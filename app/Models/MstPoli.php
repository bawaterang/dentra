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
        'poli_bpjs_id',
        'status',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'trx_user_poli', 'poli_id', 'user_id')->withTimestamps();
    }

    public function dokters()
    {
        return $this->belongsToMany(MstDokter::class, 'mst_poli_dokter', 'poli_id', 'dokter_id')->withTimestamps();
    }
}
