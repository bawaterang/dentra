<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstSettingSatusehat extends Model
{
    protected $table = 'mst_setting_satusehat';
    protected $guarded = [];

    protected $casts = [
        'doctor_credentials' => 'array',
    ];
}
