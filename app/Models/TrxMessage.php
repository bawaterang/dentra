<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrxMessage extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $table = 'trx_message';

    protected $guarded = ['id'];
}
