<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstKesadaran extends Model
{
    protected $table = 'mst_kesadaran';
    protected $fillable = ['kdSadar', 'nmSadar'];
}
