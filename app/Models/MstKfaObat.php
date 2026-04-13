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

    protected $guarded = [];

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
