<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrxInformasi extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'trx_informasi';

    protected $fillable = [
        'description',
        'date_start',
        'date_expired',
        'created_by',
    ];

    protected $casts = [
        'date_start' => 'date',
        'date_expired' => 'date',
    ];
}
