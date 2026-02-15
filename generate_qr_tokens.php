<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Interview;

$interviews = Interview::whereNull('qr_code_token')->get();

echo "Found {$interviews->count()} interviews without QR token\n";

foreach ($interviews as $interview) {
    $interview->qr_code_token = Interview::generateQrToken();
    $interview->save();
    echo "Generated QR for: {$interview->candidate_name}\n";
}

echo "\nDone! All interviews now have QR tokens.\n";
