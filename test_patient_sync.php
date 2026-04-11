<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Modules\Bridging\Services\SatuSehatService;

try {
    $service = new SatuSehatService();
    echo "Testing SatuSehat Patient Search...\n";

    // Test with a dummy NIK
    $nik = "9271060312000001";
    $patient = $service->searchPatient($nik);

    if ($patient) {
        echo "Patient Found: " . ($patient['name'][0]['text'] ?? 'No Name') . "\n";
        echo "UUID: " . $patient['id'] . "\n";
    } else {
        echo "Patient not found in SatuSehat.\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
