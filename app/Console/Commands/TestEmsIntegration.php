<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Services\EmsService;

class TestEmsIntegration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ems:test-integration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test EMS API integration and validate fixes';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('=== Kiểm Tra Tích Hợp EMS API ===');
        $this->line('');

        // Test 1: Kiểm tra cấu hình
        $this->info('[TEST 1] Kiểm Tra Cấu Hình EMS');
        $this->testConfiguration();
        $this->line('');

        // Test 2: Kiểm tra schema database
        $this->info('[TEST 2] Kiểm Tra Schema Database');
        $this->testDatabaseSchema();
        $this->line('');

        // Test 3: Kiểm tra các đơn hàng thất bại gần đây
        $this->info('[TEST 3] Kiểm Tra Các Đơn Hàng Thất Bại');
        $this->testFailedOrders();
        $this->line('');

        // Test 4: Kiểm tra EmsService
        $this->info('[TEST 4] Kiểm Tra EmsService Methods');
        $this->testEmsServiceMethods();
        $this->line('');

        // Test 5: Kiểm tra với đơn hàng mẫu
        $this->info('[TEST 5] Kiểm Tra Với Đơn Hàng Mẫu');
        $this->testSampleOrder();
        $this->line('');

        $this->info('=== Kiểm Tra Hoàn Tất ===');
        $this->line('Xem logs tại: <comment>storage/logs/laravel.log</comment>');
        $this->line('Xem EMS logs tại: <comment>storage/logs/ems.log</comment>');

        return 0;
    }

    /**
     * Test EMS configuration
     */
    private function testConfiguration()
    {
        $crmCode = config('ems.crm_code');
        $accessKey = config('ems.access_key');
        $secretKey = config('ems.secret_key');
        $url = config('ems.url');

        if (empty($crmCode)) {
            $this->error('❌ EMS_CRM_CODE không được cấu hình');
        } else {
            $this->line('<fg=green>✅</> EMS_CRM_CODE: <comment>' . $crmCode . '</comment>');
        }

        if (empty($accessKey)) {
            $this->error('❌ EMS_ACCESS_KEY không được cấu hình');
        } else {
            $this->line('<fg=green>✅</> EMS_ACCESS_KEY: <comment>' . substr($accessKey, 0, 10) . '...</comment>');
        }

        if (empty($secretKey)) {
            $this->error('❌ EMS_SECRET_KEY không được cấu hình');
        } else {
            $this->line('<fg=green>✅</> EMS_SECRET_KEY: <comment>' . substr($secretKey, 0, 10) . '...</comment>');
        }

        if (empty($url)) {
            $this->error('❌ EMS_URL không được cấu hình');
        } else {
            $this->line('<fg=green>✅</> EMS_URL: <comment>' . $url . '</comment>');
        }
    }

    /**
     * Test database schema
     */
    private function testDatabaseSchema()
    {
        $columns = DB::getSchemaBuilder()->getColumnListing('orders');

        if (in_array('push_error', $columns)) {
            $this->line('<fg=green>✅</> Cột push_error tồn tại trong bảng orders');
        } else {
            $this->error('❌ Cột push_error không tìm thấy trong bảng orders');
        }

        if (in_array('order_partner_code', $columns)) {
            $this->line('<fg=green>✅</> Cột order_partner_code tồn tại trong bảng orders');
        } else {
            $this->error('❌ Cột order_partner_code không tìm thấy trong bảng orders');
        }

        if (in_array('partner_code', $columns)) {
            $this->line('<fg=green>✅</> Cột partner_code tồn tại trong bảng orders');
        } else {
            $this->error('❌ Cột partner_code không tìm thấy trong bảng orders');
        }
    }

    /**
     * Test failed orders
     */
    private function testFailedOrders()
    {
        $failedOrders = Order::where('partner_code', 'EMS')
            ->whereNotNull('push_error')
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        if ($failedOrders->isEmpty()) {
            $this->line('<fg=cyan>ℹ️</> Không tìm thấy các đơn hàng thất bại gần đây');
        } else {
            $this->line('Tìm thấy <comment>' . count($failedOrders) . '</comment> đơn hàng thất bại:');
            foreach ($failedOrders as $order) {
                $this->line('  • Đơn ID: <comment>' . $order->id . '</comment>, Mã: <comment>' . $order->order_code . '</comment>');
                $errorPreview = substr($order->push_error, 0, 60);
                if (strlen($order->push_error) > 60) {
                    $errorPreview .= '...';
                }
                $this->line('    Lỗi: <fg=red>' . $errorPreview . '</fg=red>');
            }
        }
    }

    /**
     * Test EmsService methods
     */
    private function testEmsServiceMethods()
    {
        $emsService = new EmsService();

        if (method_exists($emsService, 'executeRequest')) {
            $this->line('<fg=green>✅</> Phương thức executeRequest tồn tại');
        } else {
            $this->error('❌ Phương thức executeRequest không tìm thấy');
        }

        if (method_exists($emsService, 'createOrder')) {
            $this->line('<fg=green>✅</> Phương thức createOrder tồn tại');
        } else {
            $this->error('❌ Phương thức createOrder không tìm thấy');
        }

        if (method_exists($emsService, 'formatDataBody')) {
            $this->line('<fg=green>✅</> Phương thức formatDataBody tồn tại');
        } else {
            $this->error('❌ Phương thức formatDataBody không tìm thấy');
        }
    }

    /**
     * Test with sample order
     */
    private function testSampleOrder()
    {
        $testOrder = Order::where('partner_code', 'EMS')
            ->whereNotNull('invoice_code')
            ->whereNotNull('sender_id')
            ->whereNotNull('receiver_id')
            ->orderBy('id', 'desc')
            ->first();

        if (!$testOrder) {
            $this->line('<fg=cyan>ℹ️</> Không có đơn hàng mẫu để kiểm tra');
            return;
        }

        $this->line('Tìm thấy đơn hàng mẫu: ID <comment>' . $testOrder->id . '</comment>');

        try {
            $emsService = new EmsService();
            $this->line('  • Đang kiểm tra formatDataBody...');
            $formatData = $emsService->formatDataBody($testOrder);

            if (!empty($formatData['OrderCode'])) {
                $this->line('  <fg=green>✅</> Mã đơn: <comment>' . $formatData['OrderCode'] . '</comment>');
            } else {
                $this->line('  <fg=red>❌</> Mã đơn trống!');
            }

            if (!empty($formatData['BuyerInfo']['FullName'])) {
                $this->line('  <fg=green>✅</> Tên người nhận: <comment>' . $formatData['BuyerInfo']['FullName'] . '</comment>');
            } else {
                $this->line('  <fg=red>❌</> Tên người nhận trống!');
            }

            if (!empty($formatData['SenderInfo']['FullName'])) {
                $this->line('  <fg=green>✅</> Tên người gửi: <comment>' . $formatData['SenderInfo']['FullName'] . '</comment>');
            } else {
                $this->line('  <fg=red>❌</> Tên người gửi trống!');
            }

            if (!empty($formatData['BuyerInfo']['MobileNumber'])) {
                $this->line('  <fg=green>✅</> SĐT người nhận: <comment>' . $formatData['BuyerInfo']['MobileNumber'] . '</comment>');
            } else {
                $this->line('  <fg=red>❌</> SĐT người nhận trống!');
            }

            if (!empty($formatData['SenderInfo']['MobileNumber'])) {
                $this->line('  <fg=green>✅</> SĐT người gửi: <comment>' . $formatData['SenderInfo']['MobileNumber'] . '</comment>');
            } else {
                $this->line('  <fg=red>❌</> SĐT người gửi trống!');
            }

        } catch (\Exception $e) {
            $this->error('  ❌ Lỗi: ' . $e->getMessage());
        }
    }
}
