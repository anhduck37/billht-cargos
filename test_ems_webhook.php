<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = app(\App\Services\EmsService::class);

$trackingCode = 'EQ688703731VN';

$order = \App\Models\Order::where('order_partner_code', $trackingCode)->first();
if (!$order) {
    echo "Order with order_partner_code $trackingCode not found.\n";
} else {
    echo "Order found with ID {$order->id}. Current delivery_status: " . $order->delivery_status . "\n";
}

$data = [
    'tracking_code' => $trackingCode,
    'status_code' => 'TEST_STATUS_P', // Not in map to trigger the fallback logic
    'status_name' => 'Phát thành công',
    'datetime' => now()->toDateTimeString(),
    'note' => 'Test Fallback mapping Phát thành công',
];

echo "Triggering EmsService->webhookTracking...\n";
$service->webhookTracking($data);

if ($order) {
    $order->refresh();
    echo "Order new delivery_status: " . $order->delivery_status . "\n";
}
echo "For reference, DELIVER_STATUS_OK = " . \App\Models\Order::DELIVERY_STATUS_OK . "\n";
echo "Done.\n";
