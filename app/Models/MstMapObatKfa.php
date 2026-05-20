<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MstMapObatKfa extends Model
{
    use HasFactory;

    protected $table = 'mst_map_obat_kfa';
    protected $fillable = ['obat_id', 'kfa_code', 'is_active'];

    public function obat()
    {
        return $this->belongsTo(MstObat::class, 'obat_id');
    }

    public function kfaObat()
    {
        return $this->belongsTo(MstKfaObat::class, 'kfa_code', 'kfa_code');
    }
}
