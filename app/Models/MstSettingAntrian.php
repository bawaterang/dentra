<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MstSettingAntrian extends Model
{
    use HasFactory;

    protected $table = 'mst_setting_antrian';

    protected $fillable = [
        'mode_antrian',
        'format_nomor_antrian',
        'jam_buka',
        'jam_tutup',
        'durasi_slot',
        'max_antrian',
        'is_active',
        'running_text',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
