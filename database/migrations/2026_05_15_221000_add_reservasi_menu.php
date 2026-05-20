<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        $now = Carbon::now();

        // Shift existing Admisi sub-menus order_no by +1 to make room
        DB::table('mst_menu')
            ->where('parent_id', 2)
            ->orderByDesc('order_no')
            ->each(function ($menu) {
                DB::table('mst_menu')
                    ->where('id', $menu->id)
                    ->update(['order_no' => $menu->order_no + 1]);
            });

        // Insert Reservasi menu as first sub-menu under Admisi (parent_id = 2)
        $menuId = DB::table('mst_menu')->insertGetId([
            'menu_name' => 'Reservasi',
            'menu_link' => '/admisi/reservasi',
            'menu_icon' => 'ri-calendar-schedule-line',
            'parent_id' => 2,
            'order_no' => 1,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Grant access to all roles that already have access to Admisi parent (id=2)
        $roleIds = DB::table('mst_role_user_access')
            ->where('menu_id', 2)
            ->where('can_view', true)
            ->pluck('role_id');

        foreach ($roleIds as $roleId) {
            DB::table('mst_role_user_access')->insert([
                'role_id' => $roleId,
                'menu_id' => $menuId,
                'can_view' => true,
                'can_create' => true,
                'can_update' => true,
                'can_delete' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        $menu = DB::table('mst_menu')
            ->where('menu_link', '/admisi/reservasi')
            ->first();

        if ($menu) {
            DB::table('mst_role_user_access')->where('menu_id', $menu->id)->delete();
            DB::table('mst_menu')->where('id', $menu->id)->delete();

            // Restore order_no
            DB::table('mst_menu')
                ->where('parent_id', 2)
                ->orderBy('order_no')
                ->each(function ($m) {
                    DB::table('mst_menu')
                        ->where('id', $m->id)
                        ->update(['order_no' => $m->order_no - 1]);
                });
        }
    }
};
