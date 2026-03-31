<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $table = 'mst_user';

    protected $fillable = [
        'user_code',
        'username',
        'email',
        'password',
        'full_name',
        'role',
        'phone',
        'avatar',
        'color',
        'signature',
        'is_active',
        'login_terakhir',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password'   => 'hashed',
            'is_active'  => 'boolean',
            'login_terakhir' => 'datetime',
        ];
    }

    public function roles()
    {
        return $this->belongsToMany(MstRoleUser::class, 'trx_role_user', 'user_id', 'role_id')->withTimestamps();
    }

    public function polis()
    {
        return $this->belongsToMany(MstPoli::class, 'trx_user_poli', 'user_id', 'poli_id')->withTimestamps();
    }
}
