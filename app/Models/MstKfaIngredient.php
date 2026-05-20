<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MstKfaIngredient extends Model
{
    use HasFactory;

    protected $table = 'mst_kfa_ingredient';
    protected $fillable = ['kfa_code', 'zat_aktif', 'kfa_code_ingredient', 'kekuatan_zat_aktif'];

    public function kfaObat()
    {
        return $this->belongsTo(MstKfaObat::class, 'kfa_code', 'kfa_code');
    }
}
