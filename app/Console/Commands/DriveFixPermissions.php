<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GoogleDriveService;
use App\GoogleDrive; // Model của bạn
use Google\Service\Drive;
use Exception;
use Log;

class DriveFixPermissions extends Command
{
    /**
     * Tên và chữ ký của lệnh.
     */
    protected $signature = 'drive:fix-permissions';

    /**
     * Mô tả lệnh.
     */
    protected $description = 'Sử dụng BATCH REQUEST để cập nhật quyền cho các file (có lọc ngày tháng)';

    private $googleDriveService;

    /**
     * Tạo instance mới.
     *
     * @param GoogleDriveService $googleDriveService
     */
    public function __construct(GoogleDriveService $googleDriveService)
    {
        parent::__construct();
        $this->googleDriveService = $googleDriveService;
    }

/**
     * Thực thi lệnh.
     */
    public function handle()
    {
        $this->info('Bắt đầu quá trình BATCH update quyền (QUÉT TOÀN BỘ)...');

        $client = $this->googleDriveService->getClient();
        $driveService = $this->googleDriveService->getDrive();

        if (!$client || !$driveService) {
            $this->error('Không thể khởi tạo Google Drive service/client.');
            return 1;
        }

        // === ĐÃ XÓA LỌC DATABASE ===
        // Lấy TẤT CẢ thư mục
        $folders = GoogleDrive::all();
        
        $this->info("Tìm thấy " . $folders->count() . " thư mục (quét toàn bộ) để quét.");

        $totalFilesUpdated = 0;

        foreach ($folders as $folder) {
            $folderId = $folder->folder_id;
            $this->line("===================================");
            $this->line("Đang quét thư mục: $folderId (Tháng {$folder->month}/{$folder->year})");

            $pageToken = null;

            // Vòng lặp do-while để xử lý pagination
            do {
                try {
                    // === ĐÃ XÓA LỌC API (createdTime) ===
                    $query = "'$folderId' in parents and trashed = false";
                    
                    $optParams = [
                        'q' => $query,
                        'fields' => 'nextPageToken, files(id, name, permissions)',
                        'pageSize' => 100, // Lấy 100 file mỗi lần
                        'pageToken' => $pageToken
                    ];

                    // Tắt batch mode để chạy listFiles BÌNH THƯỜNG
                    $client->setUseBatch(false);

                    // 1. CHẠY listFiles BÌNH THƯỜNG
                    $results = $driveService->files->listFiles($optParams);
                    $files = $results->getFiles();

                    if (empty($files) && $pageToken === null) {
                        $this->line("-> Không có file nào.");
                        break; 
                    }

                    // 2. BẬT BATCH VÀ TẠO BATCH
                    $client->setUseBatch(true);
                    $batch = $driveService->createBatch();
                    $batchCounter = 0;

                    foreach ($files as $file) {
                        // Kiểm tra xem file đã public chưa
                        $isPublic = false;
                        foreach ($file->getPermissions() as $perm) {
                            if ($perm->getType() == 'anyone') {
                                $isPublic = true;
                                break;
                            }
                        }

                        if ($isPublic) {
                            $this->line("-> Bỏ qua: '{$file->getName()}' (đã public).");
                        } else {
                            // Thêm vào batch
                            $this->info("--> Thêm vào batch: '{$file->getName()}' ($file->id)");
                            $permission = new Drive\Permission(['type' => 'anyone', 'role' => 'reader']);
                            
                            $batch->add($driveService->permissions->create($file->id, $permission), $file->id);
                            $batchCounter++;
                        }
                    } 

                    // 3. GỬI BATCH (nếu có)
                    if ($batchCounter > 0) {
                         $this->info("... Đang gửi batch $batchCounter file ...");
                         $batchResults = $batch->execute();
                         $totalFilesUpdated += $batchCounter;
                    }
                    
                    // 4. TẮT BATCH ĐỂ CHUẨN BỊ CHO VÒNG lISTFILES TIẾP THEO
                    $client->setUseBatch(false);

                    $pageToken = $results->getNextPageToken(); // Lấy token cho trang tiếp theo

                } catch (Exception $e) {
                    $this->error("LỖI khi quét: " . $e->getMessage());
                    Log::error("DriveFixPermissions Batch Error: " . $e->getMessage());
                    $client->setUseBatch(false); // Đảm bảo batch tắt nếu lỗi
                    $pageToken = null; // Dừng lại nếu có lỗi
                }
            } while ($pageToken);

        } // kết thúc foreach $folders

        $this->info("===================================");
        $this->info("HOÀN TẤT! Đã cập nhật quyền cho $totalFilesUpdated file.");
        return 0;
    }
}