<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

trait HasAccessControl
{
    /**
     * Cache for user permissions in the current request.
     */
    protected static array $permissionsCache = [];

    /**
     * Check if the current user has access to a menu link/slug.
     * 
     * @param string $menuLink The link as defined in mst_menu (e.g. '/laporan/pendapatan')
     * @param string $action 'view', 'create', 'update', or 'delete'
     * @return bool
     */
    public function userHasAccess($menuLink, $action = 'view')
    {
        $userId = Auth::id();
        if (!$userId) return false;

        $cacheKey = "{$userId}_{$menuLink}";
        
        if (!isset(static::$permissionsCache[$cacheKey])) {
            $roleIds = DB::table('trx_role_user')
                ->where('user_id', $userId)
                ->pluck('role_id');

            if ($roleIds->isEmpty()) {
                static::$permissionsCache[$cacheKey] = null;
            } else {
                static::$permissionsCache[$cacheKey] = DB::table('mst_role_user_access')
                    ->join('mst_menu', 'mst_role_user_access.menu_id', '=', 'mst_menu.id')
                    ->whereIn('mst_role_user_access.role_id', $roleIds)
                    ->where('mst_menu.menu_link', $menuLink)
                    ->select(
                        DB::raw('MAX(can_view) as can_view'),
                        DB::raw('MAX(can_create) as can_create'),
                        DB::raw('MAX(can_update) as can_update'),
                        DB::raw('MAX(can_delete) as can_delete')
                    )
                    ->first();
            }
        }

        $perms = static::$permissionsCache[$cacheKey];
        if (!$perms) return false;

        $field = "can_{$action}";
        return (bool) ($perms->$field ?? false);
    }

    /**
     * Abort if the user doesn't have access.
     */
    public function authorizeAccess($menuLink, $action = 'view')
    {
        if (!$this->userHasAccess($menuLink, $action)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }
    }
}
