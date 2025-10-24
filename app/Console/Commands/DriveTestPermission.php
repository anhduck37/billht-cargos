<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GoogleDriveService; // Import service của bạn
use Google\Service\Drive;
use Exception;

class DriveTestPermission extends Command
{
    /**
     * Chữ ký lệnh, nhận 1 fileId
     */
    protected $signature = 'drive:test-file {fileId}'; // Thêm {fileId}

    /**
     * Mô tả lệnh
     */
    protected $description = 'Kiểm tra cấp quyền công khai cho một file ID duy nhất';

    private $googleDriveService;

    /**
     * Inject GoogleDriveService
     */
    public function __construct(GoogleDriveService $googleDriveService)
    {
        parent::__construct();
        $this->googleDriveService = $googleDriveService;
    }

    /**
     * Thực thi lệnh
     */
    public function handle()
    {
        $fileId = $this->argument('fileId');
        $this->info("Bắt đầu kiểm tra cấp quyền cho file: $fileId");

        try {
            // Lấy service Drive đã được xác thực
            $driveService = $this->googleDriveService->getDrive(); // Dùng hàm getter ta đã thêm

            if (!$driveService) {
                $this->error('Không thể khởi tạo Google Drive service.');
                return 1;
            }

            // 1. Kiểm tra quyền HIỆN TẠI (để xem trước khi thay đổi)
            $this->line("Đang kiểm tra quyền hiện tại...");
            $permissions = $driveService->permissions->listPermissions($fileId, ['fields' => 'permissions(id, type, role)']);
            $isPublic = false;
            foreach ($permissions->getPermissions() as $perm) {
                if ($perm->getType() == 'anyone') {
                    $isPublic = true;
                }
                $this->line("- Quyền: type={$perm->getType()}, role={$perm->getRole()}");
            }

            if ($isPublic) {
                $this->warn("File này ĐÃ được công khai. Không cần làm gì thêm.");
                return 0;
            }

            // 2. Tạo quyền mới
            $this->info("File đang riêng tư. Bắt đầu cấp quyền 'anyone'...");
            $permission = new Drive\Permission([
                'type' => 'anyone',
                'role' => 'reader',
            ]);

            // 3. Áp dụng quyền
            $driveService->permissions->create($fileId, $permission);

            $this->info("===================================");
            $this->info("THÀNH CÔNG! Đã cấp quyền công khai cho file.");
            $this->info("Hãy mở lại link file trong trình duyệt ẨN DANH để kiểm tra.");

        } catch (Exception $e) {
            $this->error("LỖI: " . $e->getMessage());
            return 1;
        }
        return 0;
    }
}