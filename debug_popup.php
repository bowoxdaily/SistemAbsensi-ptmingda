<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;

$emp = \App\Models\Employee::whereNotNull('user_id')->first();
$user = User::find($emp->user_id);
Auth::login($user);

echo "Emp: " . $emp->name . PHP_EOL;

$controller = new \App\Http\Controllers\Employee\AnnouncementController();
$response = $controller->getPopups();
echo "Controller response: " . $response->getContent() . PHP_EOL;
