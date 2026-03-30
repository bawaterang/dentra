<?php

namespace App\Livewire\Layouts;

use App\Models\MstMenu;
use Livewire\Component;

class DynamicMenu extends Component
{
    public function render()
    {
        $userId = auth()->id();
        
        $roleIds = \Illuminate\Support\Facades\DB::table('trx_role_user')
            ->where('user_id', $userId)
            ->pluck('role_id');

        $accessibleMenuIds = \Illuminate\Support\Facades\DB::table('mst_role_user_access')
            ->whereIn('role_id', $roleIds)
            ->where('can_view', true)
            ->pluck('menu_id');

        $menus = MstMenu::whereNull('parent_id')
            ->where('is_active', true)
            ->whereIn('id', $accessibleMenuIds)
            ->with(['submenus' => function ($query) use ($accessibleMenuIds) {
                $query->where('is_active', true)
                      ->whereIn('id', $accessibleMenuIds)
                      ->orderBy('order_no');
            }])
            ->orderBy('order_no')
            ->get();

        return view('livewire.layouts.dynamic-menu', [
            'menus' => $menus
        ]);
    }
}
