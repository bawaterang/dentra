<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstSettingAntrianDetail extends Model
{
    protected $table = 'mst_setting_antrian_detail';

    public $timestamps = false; // only created_at, managed manually

    protected $fillable = [
        'hari',
        'waktu',
        'nomor_urut',
        'created_by',
        'created_at',
    ];
}
