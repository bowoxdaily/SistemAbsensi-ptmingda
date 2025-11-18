<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Checking attendances table status column:\n";
echo "==========================================\n\n";

$columns = DB::select("SHOW COLUMNS FROM attendances WHERE Field = 'status'");

if (!empty($columns)) {
    $column = $columns[0];
    echo "Column: {$column->Field}\n";
    echo "Type: {$column->Type}\n";
    echo "Null: {$column->Null}\n";
    echo "Default: {$column->Default}\n";
    echo "Extra: {$column->Extra}\n";
} else {
    echo "Status column not found\n";
}
