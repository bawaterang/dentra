<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstSettingBpjs extends Model
{
    protected $table = 'mst_setting_bpjs';
    protected $fillable = ['consid','secret_key','username','password','kd_aplikasi','user_key','base_url_pcare','base_url_vclaim','base_url_antrian','bridging'];
}
