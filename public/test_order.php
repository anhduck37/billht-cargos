<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$order = App\Models\Order::where('order_code', 'HE806431')->first();
if ($order) {
    echo "Order ID: " . $order->id . "\n";
    echo "Partner Code: " . $order->partner_code . "\n";
    echo "Order Partner Code: " . $order->order_partner_code . "\n";
    echo "Status: " . $order->delivery_status . "\n";
    
    $trackings = App\PartnerTracking::where('order_id', $order->id)->get();
    echo "Trackings count: " . count($trackings) . "\n";
    foreach ($trackings as $t) {
        echo " - " . $t->order_statusdate . " : " . $t->status_name . "\n";
    }
} else {
    echo "Order not found";
}
