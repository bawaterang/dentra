<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MstMenu extends Model
{
    use HasFactory;

    protected $table = 'mst_menu';

    protected $fillable = [
        'menu_name',
        'menu_link',
        'menu_icon',
        'parent_id',
        'order_no',
        'is_active',
        'module_id',
    ];

    /**
     * Get the submenus for this menu.
     */
    public function submenus()
    {
        return $this->hasMany(MstMenu::class, 'parent_id')->orderBy('order_no');
    }

    /**
     * Get the parent menu if this is a submenu.
     */
    public function parent()
    {
        return $this->belongsTo(MstMenu::class, 'parent_id');
    }
}
