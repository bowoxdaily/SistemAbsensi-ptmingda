<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Holiday;
use Carbon\Carbon;

echo "Creating test holidays...\n";

// Delete existing test holidays
Holiday::where('name', 'LIKE', 'Test %')->delete();

// Create Libur Nasional test (Monday, 17 Feb 2026)
$h1 = Holiday::create([
    'date' => '2026-02-17',
    'name' => 'Test Libur Nasional',
    'type' => 'nasional',
    'is_active' => true,
    'description' => 'Test holiday for system'
]);

echo "✓ Created: {$h1->name} ({$h1->type}) - {$h1->date}\n";

// Create Cuti Bersama test (Tuesday, 18 Feb 2026)
$h2 = Holiday::create([
    'date' => '2026-02-18',
    'name' => 'Test Cuti Bersama',
    'type' => 'cuti_bersama',
    'is_active' => true,
    'description' => 'Test holiday for system'
]);

echo "✓ Created: {$h2->name} ({$h2->type}) - {$h2->date}\n";

echo "\nTest holidays created successfully!\n";
