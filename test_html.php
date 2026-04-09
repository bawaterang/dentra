<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$controller = new App\Modules\Laporan\Http\Controllers\LaporanKritikSaranExportController();
$request = new Illuminate\Http\Request();
$request->merge(['periodType' => 'DAILY']);
$export = new App\Modules\Laporan\Exports\LaporanKritikSaranExport('DAILY', '2026-04-09', 4, 2026, '');
$html = $export->view()->render();

$dom = new \DOMDocument();
$dom->loadHTML($html);
echo "DOM LOADED SUCCESSFULLY\n";
