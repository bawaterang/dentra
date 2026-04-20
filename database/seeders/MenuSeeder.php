<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        // Clear existing menu data
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
            DB::table('mst_menu')->delete();
            DB::statement('PRAGMA foreign_keys = ON;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('mst_menu')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        // =============================================
        // PARENT MENUS (parent_id = NULL)
        // =============================================

        // 1. DASHBOARD
        DB::table('mst_menu')->insert([
            'id' => 1,
            'menu_name' => 'Dashboard',
            'menu_link' => '/dashboard',
            'menu_icon' => 'ri-dashboard-2-line',
            'parent_id' => null,
            'order_no' => 1,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 2. ADMISI
        DB::table('mst_menu')->insert([
            'id' => 2,
            'menu_name' => 'Admisi',
            'menu_link' => '#',
            'menu_icon' => 'ri-hospital-line',
            'parent_id' => null,
            'order_no' => 2,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 3. BRIDGING
        DB::table('mst_menu')->insert([
            'id' => 3,
            'menu_name' => 'Bridging',
            'menu_link' => '#',
            'menu_icon' => 'ri-links-line',
            'parent_id' => null,
            'order_no' => 3,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 4. TRANSAKSI
        DB::table('mst_menu')->insert([
            'id' => 4,
            'menu_name' => 'Transaksi',
            'menu_link' => '/transaksi',
            'menu_icon' => 'ri-exchange-funds-line',
            'parent_id' => null,
            'order_no' => 4,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 5. KEUANGAN
        DB::table('mst_menu')->insert([
            'id' => 5,
            'menu_name' => 'Keuangan',
            'menu_link' => '#',
            'menu_icon' => 'ri-money-dollar-circle-line',
            'parent_id' => null,
            'order_no' => 5,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 6. MASTER
        DB::table('mst_menu')->insert([
            'id' => 6,
            'menu_name' => 'Master',
            'menu_link' => '#',
            'menu_icon' => 'ri-database-2-line',
            'parent_id' => null,
            'order_no' => 6,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 7. SETTING
        DB::table('mst_menu')->insert([
            'id' => 7,
            'menu_name' => 'Setting',
            'menu_link' => '#',
            'menu_icon' => 'ri-settings-3-line',
            'parent_id' => null,
            'order_no' => 7,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 8. LAPORAN
        DB::table('mst_menu')->insert([
            'id' => 8,
            'menu_name' => 'Laporan',
            'menu_link' => '#',
            'menu_icon' => 'ri-file-chart-line',
            'parent_id' => null,
            'order_no' => 8,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // =============================================
        // SUB MENUS - ADMISI (parent_id = 2)
        // =============================================

        DB::table('mst_menu')->insert([
            'id' => 9,
            'menu_name' => 'Antrian',
            'menu_link' => '/admisi/antrian',
            'menu_icon' => 'ri-list-ordered',
            'parent_id' => 2,
            'order_no' => 1,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('mst_menu')->insert([
            'id' => 10,
            'menu_name' => 'Pendaftaran',
            'menu_link' => '/admisi/pendaftaran',
            'menu_icon' => 'ri-user-add-line',
            'parent_id' => 2,
            'order_no' => 2,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('mst_menu')->insert([
            'id' => 11,
            'menu_name' => 'Screening Pasien',
            'menu_link' => '/admisi/screening-pasien',
            'menu_icon' => 'ri-stethoscope-line',
            'parent_id' => 2,
            'order_no' => 3,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // =============================================
        // SUB MENUS - BRIDGING (parent_id = 3)
        // =============================================

        DB::table('mst_menu')->insert([
            'id' => 12,
            'menu_name' => 'Data Pasien BPJS',
            'menu_link' => '/bridging/data-pasien-bpjs',
            'menu_icon' => 'ri-shield-user-line',
            'parent_id' => 3,
            'order_no' => 1,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('mst_menu')->insert([
            'id' => 13,
            'menu_name' => 'Setting API',
            'menu_link' => '/bridging/setting-api',
            'menu_icon' => 'ri-code-s-slash-line',
            'parent_id' => 3,
            'order_no' => 2,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('mst_menu')->insert([
            'id' => 35,
            'menu_name' => 'API Monitoring',
            'menu_link' => '/bridging/api-monitoring',
            'menu_icon' => 'ri-signal-tower-line',
            'parent_id' => 3,
            'order_no' => 3,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // =============================================
        // SUB MENUS - KEUANGAN (parent_id = 5)
        // =============================================

        DB::table('mst_menu')->insert([
            'id' => 14,
            'menu_name' => 'Billing',
            'menu_link' => '/keuangan/billing',
            'menu_icon' => 'ri-bill-line',
            'parent_id' => 5,
            'order_no' => 1,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // =============================================
        // SUB MENUS - MASTER (parent_id = 6)
        // =============================================

        DB::table('mst_menu')->insert([
            'id' => 15,
            'menu_name' => 'Data Pasien',
            'menu_link' => '/master/data-pasien',
            'menu_icon' => 'ri-user-heart-line',
            'parent_id' => 6,
            'order_no' => 1,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('mst_menu')->insert([
            'id' => 16,
            'menu_name' => 'Data Dokter',
            'menu_link' => '/master/data-dokter',
            'menu_icon' => 'ri-nurse-line',
            'parent_id' => 6,
            'order_no' => 2,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('mst_menu')->insert([
            'id' => 17,
            'menu_name' => 'Data Asuransi',
            'menu_link' => '/master/data-asuransi',
            'menu_icon' => 'ri-shield-check-line',
            'parent_id' => 6,
            'order_no' => 3,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('mst_menu')->insert([
            'id' => 18,
            'menu_name' => 'Data Obat',
            'menu_link' => '/master/data-obat',
            'menu_icon' => 'ri-capsule-line',
            'parent_id' => 6,
            'order_no' => 4,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('mst_menu')->insert([
            'id' => 19,
            'menu_name' => 'Data Diagnosis',
            'menu_link' => '/master/data-diagnosis',
            'menu_icon' => 'ri-heart-pulse-line',
            'parent_id' => 6,
            'order_no' => 5,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('mst_menu')->insert([
            'id' => 20,
            'menu_name' => 'Data Gigi',
            'menu_link' => '/master/data-gigi',
            'menu_icon' => 'ri-mastodon-line',
            'parent_id' => 6,
            'order_no' => 6,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('mst_menu')->insert([
            'id' => 21,
            'menu_name' => 'Data Tindakan',
            'menu_link' => '/master/data-tindakan',
            'menu_icon' => 'ri-surgical-mask-line',
            'parent_id' => 6,
            'order_no' => 7,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('mst_menu')->insert([
            'id' => 22,
            'menu_name' => 'Data Tarif',
            'menu_link' => '/master/data-tarif',
            'menu_icon' => 'ri-price-tag-3-line',
            'parent_id' => 6,
            'order_no' => 8,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('mst_menu')->insert([
            'id' => 23,
            'menu_name' => 'Data BMHP',
            'menu_link' => '/master/data-bmhp',
            'menu_icon' => 'ri-first-aid-kit-line',
            'parent_id' => 6,
            'order_no' => 9,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('mst_menu')->insert([
            'id' => 24,
            'menu_name' => 'Data Menu',
            'menu_link' => '/master/data-menu',
            'menu_icon' => 'ri-menu-line',
            'parent_id' => 6,
            'order_no' => 10,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('mst_menu')->insert([
            'id' => 38,
            'menu_name' => 'Data Poli',
            'menu_link' => '/master/data-poli',
            'menu_icon' => 'ri-hospital-line',
            'parent_id' => 6,
            'order_no' => 11,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('mst_menu')->insert([
            'id' => 39,
            'menu_name' => 'Data Survei',
            'menu_link' => '/master/data-survei',
            'menu_icon' => 'ri-survey-line',
            'parent_id' => 6,
            'order_no' => 12,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // =============================================
        // SUB MENUS - SETTING (parent_id = 7)
        // =============================================

        DB::table('mst_menu')->insert([
            'id' => 25,
            'menu_name' => 'Informasi',
            'menu_link' => '/setting/informasi',
            'menu_icon' => 'ri-information-line',
            'parent_id' => 7,
            'order_no' => 1,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('mst_menu')->insert([
            'id' => 26,
            'menu_name' => 'Jadwal Dokter',
            'menu_link' => '/setting/jadwal-dokter',
            'menu_icon' => 'ri-calendar-check-line',
            'parent_id' => 7,
            'order_no' => 2,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('mst_menu')->insert([
            'id' => 27,
            'menu_name' => 'User',
            'menu_link' => '/setting/user',
            'menu_icon' => 'ri-user-line',
            'parent_id' => 7,
            'order_no' => 3,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('mst_menu')->insert([
            'id' => 28,
            'menu_name' => 'Role User',
            'menu_link' => '/setting/role-user',
            'menu_icon' => 'ri-admin-line',
            'parent_id' => 7,
            'order_no' => 4,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('mst_menu')->insert([
            'id' => 29,
            'menu_name' => 'Akses Menu',
            'menu_link' => '/setting/akses-menu',
            'menu_icon' => 'ri-shield-keyhole-line',
            'parent_id' => 7,
            'order_no' => 5,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('mst_menu')->insert([
            'id' => 30,
            'menu_name' => 'Backup Database',
            'menu_link' => '/setting/backup-database',
            'menu_icon' => 'ri-database-line',
            'parent_id' => 7,
            'order_no' => 6,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('mst_menu')->insert([
            'id' => 36,
            'menu_name' => 'Setting Klinik',
            'menu_link' => '/setting-klinik',
            'menu_icon' => 'ri-hospital-line',
            'parent_id' => 7,
            'order_no' => 7,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('mst_menu')->insert([
            'id' => 37,
            'menu_name' => 'Setting Antrian',
            'menu_link' => '/setting/antrian',
            'menu_icon' => 'ri-list-ordered',
            'parent_id' => 7,
            'order_no' => 8,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // =============================================
        // SUB MENUS - LAPORAN (parent_id = 8)
        // =============================================

        DB::table('mst_menu')->insert([
            'id' => 31,
            'menu_name' => 'Laporan Jasa Medis',
            'menu_link' => '/laporan/jasa-medis',
            'menu_icon' => 'ri-money-dollar-box-line',
            'parent_id' => 8,
            'order_no' => 1,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('mst_menu')->insert([
            'id' => 32,
            'menu_name' => 'Laporan Kunjungan',
            'menu_link' => '/laporan/kunjungan',
            'menu_icon' => 'ri-walk-line',
            'parent_id' => 8,
            'order_no' => 2,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('mst_menu')->insert([
            'id' => 33,
            'menu_name' => 'Laporan Kritik dan Saran',
            'menu_link' => '/laporan/kritik-saran',
            'menu_icon' => 'ri-chat-quote-line',
            'parent_id' => 8,
            'order_no' => 3,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('mst_menu')->insert([
            'id' => 34,
            'menu_name' => 'Laporan Satu Sehat',
            'menu_link' => '/laporan/satu-sehat',
            'menu_icon' => 'ri-hearts-line',
            'parent_id' => 8,
            'order_no' => 4,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('mst_menu')->insert([
            'id' => 40,
            'menu_name' => 'Laporan Pendapatan',
            'menu_link' => '/laporan/pendapatan',
            'menu_icon' => 'ri-money-dollar-circle-line',
            'parent_id' => 8,
            'order_no' => 5,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}