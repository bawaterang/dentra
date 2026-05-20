<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MstKfaObat extends Model
{
    use HasFactory;

    protected $table = 'mst_kfa_obat';
    
    protected $primaryKey = 'kfa_code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['kfa_code','name','manufacturer','dosage_form_code','dosage_form_name','produk_template_kfa','last_synced_at'];

    protected $casts = [
        'last_synced_at' => 'datetime',
    ];

    public function ingredients()
    {
        return $this->hasMany(MstKfaIngredient::class, 'kfa_code', 'kfa_code');
    }

    public function mappings()
    {
        return $this->hasMany(MstMapObatKfa::class, 'kfa_code', 'kfa_code');
    }
}
