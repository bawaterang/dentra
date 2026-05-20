<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstSettingSatusehat extends Model
{
    protected $table = 'mst_setting_satusehat';
    protected $fillable = ['client_id','client_secret','url','token_url','mode_bridging','doctor_credentials'];

    protected $casts = [
        'doctor_credentials' => 'array',
    ];
}
