<?php
/**
 * Test script for EMS API integration fixes
 * Run this script to verify the EMS push functionality is working
 * 
 * Usage: php test_ems_fix.php
 */

require_once 'vendor/autoload.php';
require_once 'bootstrap/app.php';

use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Services\EmsService;

// Enable debugging
\Log::info('=== EMS API Fix Validation Test Started ===');

echo "\n=== EMS API Integration Test ===\n";
echo "Testing fixes for order pushing to EMS API\n\n";

// Test 1: Check configuration
echo "[TEST 1] Checking EMS Configuration\n";
$crmCode = config('ems.crm_code');
$accessKey = config('ems.access_key');
$secretKey = config('ems.secret_key');
$url = config('ems.url');

if (empty($crmCode)) {
    echo "❌ FAILED: EMS_CRM_CODE not configured\n";
} else {
    echo "✅ EMS_CRM_CODE: $crmCode\n";
}

if (empty($accessKey)) {
    echo "❌ FAILED: EMS_ACCESS_KEY not configured\n";
} else {
    echo "✅ EMS_ACCESS_KEY: " . substr($accessKey, 0, 10) . "...\n";
}

if (empty($secretKey)) {
    echo "❌ FAILED: EMS_SECRET_KEY not configured\n";
} else {
    echo "✅ EMS_SECRET_KEY: " . substr($secretKey, 0, 10) . "...\n";
}

if (empty($url)) {
    echo "❌ FAILED: EMS_URL not configured\n";
} else {
    echo "✅ EMS_URL: $url\n";
}

// Test 2: Check database schema
echo "\n[TEST 2] Checking Database Schema\n";
$columns = DB::getSchemaBuilder()->getColumnListing('orders');
if (in_array('push_error', $columns)) {
    echo "✅ push_error column exists in orders table\n";
} else {
    echo "❌ FAILED: push_error column not found in orders table\n";
}

if (in_array('order_partner_code', $columns)) {
    echo "✅ order_partner_code column exists in orders table\n";
} else {
    echo "❌ FAILED: order_partner_code column not found in orders table\n";
}

// Test 3: Check recent failed orders
echo "\n[TEST 3] Checking Recent Failed Orders\n";
$failedOrders = Order::where('partner_code', 'EMS')
    ->whereNotNull('push_error')
    ->orderBy('updated_at', 'desc')
    ->limit(5)
    ->get();

if ($failedOrders->isEmpty()) {
    echo "ℹ️  No recent failed orders found\n";
} else {
    echo "Found " . count($failedOrders) . " failed orders:\n";
    foreach ($failedOrders as $order) {
        echo "  - Order ID: {$order->id}, Code: {$order->order_code}\n";
        echo "    Error: " . substr($order->push_error, 0, 80) . "...\n";
    }
}

// Test 4: Validate EmsService methods
echo "\n[TEST 4] Validating EmsService Implementation\n";
$emsService = new EmsService();
if (method_exists($emsService, 'executeRequest')) {
    echo "✅ executeRequest method exists\n";
}
if (method_exists($emsService, 'createOrder')) {
    echo "✅ createOrder method exists\n";
}
if (method_exists($emsService, 'formatDataBody')) {
    echo "✅ formatDataBody method exists\n";
}

// Test 5: Test with a sample order (if available)
echo "\n[TEST 5] Testing with Sample Order\n";
$testOrder = Order::where('partner_code', 'EMS')
    ->whereNotNull('invoice_code')
    ->whereNotNull('sender_id')
    ->whereNotNull('receiver_id')
    ->orderBy('id', 'desc')
    ->first();

if ($testOrder) {
    echo "Found test order: ID {$testOrder->id}\n";
    try {
        echo "  - Testing formatDataBody...\n";
        $formatData = $emsService->formatDataBody($testOrder);
        
        if (!empty($formatData['OrderCode'])) {
            echo "  ✅ OrderCode: {$formatData['OrderCode']}\n";
        } else {
            echo "  ❌ OrderCode is empty!\n";
        }
        
        if (!empty($formatData['BuyerInfo']['FullName'])) {
            echo "  ✅ Receiver Name: {$formatData['BuyerInfo']['FullName']}\n";
        } else {
            echo "  ❌ Receiver name is empty!\n";
        }
        
        if (!empty($formatData['SenderInfo']['FullName'])) {
            echo "  ✅ Sender Name: {$formatData['SenderInfo']['FullName']}\n";
        } else {
            echo "  ❌ Sender name is empty!\n";
        }
        
    } catch (\Exception $e) {
        echo "  ❌ Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "ℹ️  No test orders available\n";
}

echo "\n=== Test Complete ===\n";
echo "Check logs at: storage/logs/laravel.log\n";
echo "For detailed API interactions, check: storage/logs/ems.log\n\n";
