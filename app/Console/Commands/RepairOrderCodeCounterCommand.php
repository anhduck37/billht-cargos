<?php

namespace App\Console\Commands;

use App\Services\OrderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairOrderCodeCounterCommand extends Command
{
    protected $signature = 'orders:repair-code-counter
        {--prefix= : Prefix cần sửa, mặc định lấy PREFIX_CODE}
        {--apply : Chạy thật. Không có option này thì chỉ xem trước}';

    protected $description = 'Tính lại counter sinh mã vận đơn, bỏ qua các mã có phần số quá dài/bất thường';

    public function handle()
    {
        $prefix = trim((string) ($this->option('prefix') ?: config('order_manager.prefix_code')));
        $apply = (bool) $this->option('apply');

        if ($prefix === '') {
            $this->error('Prefix rỗng, không thể sửa counter.');
            return 1;
        }

        $safeNextNumber = app(OrderService::class)->getInitialNextNumber($prefix);
        $counter = DB::table('order_code_counters')->where('prefix', $prefix)->first();

        $this->line('Prefix: ' . $prefix);
        $this->line('Counter hiện tại: ' . ($counter ? $counter->next_number : 'chưa có'));
        $this->line('Counter nên dùng: ' . $safeNextNumber);

        if (!$apply) {
            $this->warn('Đây là bản xem trước. Thêm --apply để cập nhật counter.');
            return 0;
        }

        DB::table('order_code_counters')->updateOrInsert(
            ['prefix' => $prefix],
            [
                'next_number' => $safeNextNumber,
                'updated_at' => now(),
                'created_at' => $counter ? $counter->created_at : now(),
            ]
        );

        $this->info('Đã cập nhật counter sinh mã vận đơn.');

        return 0;
    }
}
