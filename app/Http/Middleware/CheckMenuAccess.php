<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use App\Models\MstMenu;

class CheckMenuAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Require auth
        if (!auth()->check()) {
            return $next($request); // Allow auth middleware to handle redirect to login
        }

        $userId = auth()->id();
        $path = '/' . trim($request->path(), '/'); // Normalize path
        
        // Find if this path belongs to any menu
        $menus = MstMenu::where('is_active', true)
            ->whereNotNull('menu_link')
            ->where('menu_link', '!=', '#')
            ->get();
            
        $matchedMenu = null;
        $matchedLength = 0;
        
        foreach ($menus as $menu) {
            $menuLink = rtrim($menu->menu_link, '/');
            if (empty($menuLink)) continue;
            
            // Check exact or prefix link match
            if ($path === $menuLink || str_starts_with($path, $menuLink . '/')) {
                if (strlen($menuLink) > $matchedLength) {
                    $matchedMenu = $menu;
                    $matchedLength = strlen($menuLink);
                }
            }
        }
        
        if ($matchedMenu) {
            $roleIds = DB::table('trx_role_user')->where('user_id', $userId)->pluck('role_id');
            
            $hasAccess = DB::table('mst_role_user_access')
                ->whereIn('role_id', $roleIds)
                ->where('menu_id', $matchedMenu->id)
                ->where('can_view', true)
                ->exists();
                
            if (!$hasAccess) {
                // Return 403 Forbidden
                abort(403, 'Akses Ditolak: Anda tidak memiliki hak akses (can_view) untuk halaman ' . $matchedMenu->menu_name);
            }
        }
        
        return $next($request);
    }
}
