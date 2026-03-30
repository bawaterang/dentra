<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstRoleUserAccess extends Model
{
    protected $table = 'mst_role_user_access';
    
    protected $fillable = [
        'role_id',
        'menu_id',
        'can_view',
        'can_create',
        'can_update',
        'can_delete'
    ];

    public function role()
    {
        return $this->belongsTo(MstRoleUser::class, 'role_id');
    }

    public function menu()
    {
        return $this->belongsTo(MstMenu::class, 'menu_id');
    }
}
