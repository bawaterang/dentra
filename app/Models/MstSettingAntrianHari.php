<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstSettingAntrianHari extends Model
{
    protected $table = 'mst_setting_antrian_hari';

    protected $fillable = [
        'hari',
        'jam_buka',
        'jam_tutup',
        'durasi_slot',
        'max_antrian',
        'is_holiday',
    ];
}
