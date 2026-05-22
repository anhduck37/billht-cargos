<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\OrderPartnerLog;

$logs = OrderPartnerLog::where('partner_code', 'ems')->orderBy('id','desc')->limit(5)->get();

echo "EMS LOGS PAYLOAD DUMP:\n";
foreach ($logs as $log) {
    echo "ID: " . $log->id . "\n";
    echo "Payload: " . $log->payload . "\n";
    echo "----------------------------------------\n";
}
