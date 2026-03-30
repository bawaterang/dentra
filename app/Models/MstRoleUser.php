<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MstRoleUser extends Model
{
    use HasFactory;

    protected $table = 'mst_role_user';

    protected $fillable = [
        'nama_role',
        'deskripsi',
        'is_active',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'trx_role_user', 'role_id', 'user_id')->withTimestamps();
    }
}
