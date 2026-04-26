<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstAlergi extends Model
{
    protected $table = 'mst_alergi';
    protected $fillable = ['kdAlergi', 'nmAlergi', 'kdJenis'];
}
