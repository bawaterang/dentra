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
     * In-memory cache to avoid redundant queries within the same process.
     */
    protected static ?array $menuLinks = null;
    protected static array $userAccess = [];

    /**
     * Paths that should be skipped from menu access checks.
     */
    protected array $excludedPrefixes = [
        '/livewire',
        '/chat',
        '/dashboard',
        '/login',
        '/logout',
        '/register',
        '/up',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip for unauthenticated users
        if (!auth()->check()) {
            return $next($request);
        }

        // Skip for AJAX/Livewire requests — they inherit access from the parent page
        if ($request->ajax() || $request->hasHeader('X-Livewire')) {
            return $next($request);
        }

        $path = '/' . trim($request->path(), '/');

        // Skip for excluded paths
        foreach ($this->excludedPrefixes as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return $next($request);
            }
        }

        // Load menu links once per process (static cache)
        if (static::$menuLinks === null) {
            static::$menuLinks = MstMenu::where('is_active', true)
                ->whereNotNull('menu_link')
                ->where('menu_link', '!=', '#')
                ->select('id', 'menu_name', 'menu_link')
                ->get()
                ->map(fn ($m) => [
                    'id' => $m->id,
                    'name' => $m->menu_name,
                    'link' => rtrim($m->menu_link, '/'),
                ])
                ->filter(fn ($m) => !empty($m['link']))
                ->values()
                ->toArray();
        }

        // Find the best matching menu
        $matchedMenu = null;
        $matchedLength = 0;

        foreach (static::$menuLinks as $menu) {
            if ($path === $menu['link'] || str_starts_with($path, $menu['link'] . '/')) {
                if (strlen($menu['link']) > $matchedLength) {
                    $matchedMenu = $menu;
                    $matchedLength = strlen($menu['link']);
                }
            }
        }

        if (!$matchedMenu) {
            return $next($request);
        }

        // Load user access once per user per process
        $userId = auth()->id();
        if (!isset(static::$userAccess[$userId])) {
            $roleIds = DB::table('trx_role_user')
                ->where('user_id', $userId)
                ->pluck('role_id');

            static::$userAccess[$userId] = DB::table('mst_role_user_access')
                ->whereIn('role_id', $roleIds)
                ->where('can_view', true)
                ->pluck('menu_id')
                ->toArray();
        }

        if (!in_array($matchedMenu['id'], static::$userAccess[$userId])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki hak akses (can_view) untuk halaman ' . $matchedMenu['name']);
        }

        return $next($request);
    }
}
