<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$controller = new App\Modules\Laporan\Http\Controllers\LaporanKritikSaranExportController();
$request = new Illuminate\Http\Request();
$request->merge(['periodType' => 'DAILY']);
try {
    $pdf = $controller->print($request);
    echo "PDF SUCCESS\n";
} catch (\Exception $e) {
    echo "PDF ERROR: " . $e->getMessage() . "\n";
}
try {
    $excel = $controller->exportExcel($request);
    echo "EXCEL SUCCESS\n";
} catch (\Exception $e) {
    echo "EXCEL ERROR: " . $e->getMessage() . "\n";
}
