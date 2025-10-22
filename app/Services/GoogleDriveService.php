<?php

namespace App\Services;

use App\GoogleDrive;
use Google_Client;
use Google\Service\Drive;
use Exception;

class GoogleDriveService
{
    private $googleClient;
    private $googleDrive;
    private $folder;
    private $file;

    public function __construct()
    {
        $clientSecret = app_path('Services/google/client_secret.json');
        $tokenPath    = app_path('Services/google/token.json');

        if (!is_file($clientSecret)) {
            throw new Exception('Missing client_secret.json at app/Services/google/client_secret.json');
        }

        $this->googleClient = new Google_Client();
        $this->googleClient->setAuthConfig($clientSecret);
        $this->googleClient->setAccessType('offline');
        $this->googleClient->setPrompt('consent');
        $this->googleClient->setScopes([Drive::DRIVE_FILE]);

        // ⚠️ Cố định redirect URI để tránh redirect_uri_mismatch
        $this->googleClient->setRedirectUri('https://bill.ht-cargos.com/google/oauth2/callback');

        // Load access token nếu đã có
        if (is_file($tokenPath)) {
            $this->googleClient->setAccessToken(json_decode(file_get_contents($tokenPath), true));
        }

        // Tự refresh hoặc báo yêu cầu chạy /google/oauth2/start
        if ($this->googleClient->isAccessTokenExpired()) {
            $refreshToken = $this->googleClient->getRefreshToken();
            if ($refreshToken) {
                $this->googleClient->fetchAccessTokenWithRefreshToken($refreshToken);
                file_put_contents($tokenPath, json_encode($this->googleClient->getAccessToken()));
            } else {
                throw new Exception('Google OAuth not initialized. Open /google/oauth2/start to connect your account.');
            }
        }

        $this->googleDrive = new Drive($this->googleClient);
    }

    /**
     * Tạo/có sẵn folder theo tháng/năm (giống logic cũ)
     */
    public function getOrCreateFolderId($month = null, $year = null)
    {
        if (empty($month)) $month = date('m');
        if (empty($year))  $year  = date('Y');

        $googleDrive = GoogleDrive::where('month', $month)->where('year', $year)->first();
        $dataGGDrive = ['month' => $month, 'year' => $year];

        if (empty($googleDrive)) {
            $googleDriveYear = GoogleDrive::where('year', $year)->first();

            if (empty($googleDriveYear)) {
                $forderYear = $this->createFolder($year);
                $dataGGDrive['year_folder_id'] = $forderYear->id;
            } else {
                $dataGGDrive['year_folder_id'] = $googleDriveYear->year_folder_id;
            }

            $googleDrive = new GoogleDrive();
            $folder = $this->createFolder($month, $dataGGDrive['year_folder_id']);
            $dataGGDrive['folder_id'] = $folder->id;
            $googleDrive->fill($dataGGDrive);
            $googleDrive->save();
        }

        return $googleDrive;
    }

    /**
     * Tạo folder trong My Drive. $folderId = thư mục cha; nếu rỗng dùng GOOGLE_DRIVE_PARENT_FOLDER_ID
     */
    public function createFolder($name, $folderId = null)
    {
        if (empty($folderId)) {
            $folderId = config('google_drive.folderId'); // đặt trong config/google_drive.php hoặc .env
            if (empty($folderId)) {
                throw new Exception('Missing google_drive.folderId (root folder id).');
            }
        }

        $fileMetadata = new Drive\DriveFile([
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$folderId],
        ]);

        // Với My Drive KHÔNG cần supportsAllDrives
        $folder = $this->googleDrive->files->create($fileMetadata, ['fields' => 'id']);
        return $folder;
    }

    /**
     * Tạo file vào folder tháng/năm
     */
    public function createFile($nameFile, $content, $mimeType, $month = null, $year = null)
    {
        $folder = $this->getOrCreateFolderId($month, $year);
        $this->setFolder($folder);

        $fileMetadata = new Drive\DriveFile([
            'name' => $nameFile,
            'parents' => [$folder->folder_id],
        ]);

        $file = $this->googleDrive->files->create(
            $fileMetadata,
            [
                'data' => $content,
                'mimeType' => $mimeType ?: 'application/octet-stream',
                'uploadType' => 'multipart',
                'fields' => 'id',
            ]
        );

        $this->setFile($file);
        return $this;
    }

    public function deleteFile($fileId)
    {
        try {
            $this->googleDrive->files->delete($fileId);
        } catch (Exception $e) {
            // nuốt lỗi để giữ API tương thích
        }
        return $this;
    }

    public function setFolder($folder) { $this->folder = $folder; return $this; }
    public function getFolder() { return $this->folder; }
    public function setFile($file) { $this->file = $file; return $this; }
    public function getFile() { return $this->file; }
}
