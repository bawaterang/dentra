<?php

namespace App\Livewire\Layouts;

use App\Models\MstMenu;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class DynamicMenu extends Component
{
    /**
     * In-memory cache to prevent re-querying within the same request.
     */
    protected static array $menuCache = [];

    /**
     * Load menus with per-request memoization to avoid repeated DB queries.
     */
    protected function loadMenus()
    {
        $userId = auth()->id();

        if (isset(static::$menuCache[$userId])) {
            return static::$menuCache[$userId];
        }

        $roleIds = DB::table('trx_role_user')
            ->where('user_id', $userId)
            ->pluck('role_id');

        $accessibleMenuIds = DB::table('mst_role_user_access')
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

        static::$menuCache[$userId] = $menus;

        return $menus;
    }

    public function render()
    {
        return view('livewire.layouts.dynamic-menu', [
            'menus' => $this->loadMenus()
        ]);
    }
}
