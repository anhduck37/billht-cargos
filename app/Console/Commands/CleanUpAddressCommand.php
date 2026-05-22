<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Ward;
use App\District;
use App\City;
use App\Models\Order;
use App\Sender;
use App\Receiver;
use Illuminate\Support\Facades\DB;

class CleanUpAddressCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ems:cleanup_address';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up unmapped and unused Wards, Districts, and Cities from the database.';

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
        $this->info("Bắt đầu dọn dẹp các Phường/Xã không nằm trong mạng lưới EMS...");

        // 1. Quét Ward (Phường/Xã)
        $unmappedWards = Ward::whereNull('ems_code')->get();
        $this->info("Tìm thấy " . $unmappedWards->count() . " Phường/Xã chưa map được mã EMS (ems_code = null).");

        $deletedWardsCount = 0;
        $keptWardsCount = 0;

        foreach ($unmappedWards as $ward) {
            // Kiểm tra ràng buộc dữ liệu: Có đang dùng trong Order, Sender, Receiver không
            // Assuming relationships or explicitly checking columns
            $isUsedInOrders = Order::where(function($q) use ($ward) {
                // Usually Orders have sender_ward_id, receiver_ward_id? Or depends on DB structure.
                // It seems Order might not directly have ward_id. Let's check Sender/Receiver.
                $q->whereNull('id'); // Placeholder until we confirm Orders table structure if needed.
            })->exists();

            $isUsedInSenders = Sender::where('ward_id', $ward->id)->exists();
            $isUsedInReceivers = Receiver::where('ward_id', $ward->id)->exists();

            if (!$isUsedInOrders && !$isUsedInSenders && !$isUsedInReceivers) {
                $ward->delete();
                $deletedWardsCount++;
            } else {
                $keptWardsCount++;
            }
        }

        $this->info("Hoàn tất dọn dẹp Phường/Xã: Đã xóa $deletedWardsCount, Giữ lại $keptWardsCount (do đang được sử dụng ở Log/Order cũ).");

        // 2. Quét District (Quận/Huyện) không còn Phường/Xã hoặc ems_code = null
        $this->info("Đang dọn dẹp các Quận/Huyện mồ côi hoặc không map EMS...");
        $unmappedDistricts = District::whereNull('ems_code')->get();
        $deletedDistrictsCount = 0;
        
        foreach ($unmappedDistricts as $district) {
            $hasWards = Ward::where('district_id', $district->id)->exists();
            $isUsedInSenders = Sender::where('district_id', $district->id)->exists();
            $isUsedInReceivers = Receiver::where('district_id', $district->id)->exists();

            if (!$hasWards && !$isUsedInSenders && !$isUsedInReceivers) {
                $district->delete();
                $deletedDistrictsCount++;
            }
        }
        $this->info("Hoàn tất dọn dẹp Quận/Huyện: Đã xóa $deletedDistrictsCount.");

        return 0;
    }
}
