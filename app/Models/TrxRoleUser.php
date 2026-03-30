<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrxRoleUser extends Model
{
    use HasFactory;

    protected $table = 'trx_role_user';

    protected $fillable = [
        'user_id',
        'role_id',
    ];
}
