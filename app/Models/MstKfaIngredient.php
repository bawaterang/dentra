<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MstKfaIngredient extends Model
{
    use HasFactory;

    protected $table = 'mst_kfa_ingredient';
    protected $guarded = ['id'];

    public function kfaObat()
    {
        return $this->belongsTo(MstKfaObat::class, 'kfa_code', 'kfa_code');
    }
}
