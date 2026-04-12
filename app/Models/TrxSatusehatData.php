<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrxSatusehatData extends Model
{
    use HasFactory;

    protected $table = 'trx_satusehat_data';
    protected $guarded = ['id'];

    protected $casts = [
        'isi_json' => 'array',
    ];
}
