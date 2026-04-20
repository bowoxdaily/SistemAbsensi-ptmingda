<?php
// Quick API response test
require 'vendor/autoload.php';
require 'bootstrap/app.php';

use Illuminate\Http\Request;

$app = new Illuminate\Foundation\Application(getcwd());

// Create a mock request
$request = Request::create('/api/admin/rekapitulasi/geographic-data?group_level=kabupaten', 'GET');

// Import Illuminate\Contracts\Foundation\Application
$app['request'] = $request;
$app['config'] = require 'bootstrap/app.php';

try {
    // Manually instantiate and call controller
    $controller = new App\Http\Controllers\Admin\RekapitulasiController();
    
    echo "Testing API Response Format:\n";
    echo "═══════════════════════════════════════════════════════════\n\n";
    
    // Get geographic data
    $response = $controller->getGeographicData($request);
    $data = $response->getData(true);
    
    if ($data['success'] && count($data['data']) > 0) {
        echo "✓ API Response Success\n";
        echo "Total Groups: " . count($data['data']) . "\n";
        echo "First Group Structure:\n";
        
        $firstItem = $data['data'][0];
        echo json_encode($firstItem, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
        
        // Check if employees array exists
        if (isset($firstItem['employees'])) {
            echo "⚠ WARNING: Still has 'employees' array (should be removed)\n";
        } else if (isset($firstItem['_employee_ids'])) {
            echo "✓ Correct: Has '_employee_ids' array only\n";
        }
    } else {
        echo "Error in response\n";
        echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
