<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Modules\Bridging\Services\SatuSehatService;

try {
    $service = new SatuSehatService();
    echo "Testing SatuSehat Organization Search...\n";
    
    // Test with a dummy Organization Name
    $name = "Klinik"; 
    $orgs = $service->searchOrganization($name);
    
    if ($orgs) {
        echo "Found " . count($orgs) . " Organizations.\n";
        foreach (array_slice($orgs, 0, 3) as $org) {
            $resource = $org['resource'] ?? [];
            echo "- " . ($resource['name'] ?? 'No Name') . " (ID: " . ($resource['id'] ?? 'No ID') . ")\n";
        }
    } else {

        echo "Organization not found in SatuSehat.\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
