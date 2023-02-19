<?php

namespace App\Services;

use App\GoogleDrive;
use Google_Client;
use Hypweb\Flysystem\GoogleDrive\GoogleDriveAdapter;
use League\Flysystem\Filesystem;
use Google\Service\Drive;

class GoogleDriveService {

    private $googleClient;
    private $googleDrive;
    private $folder;
    private $file;

    public function __construct()
    {
        $this->googleClient = new Google_Client();
        $this->googleClient->setAuthConfig(__DIR__ . '/google/config_google.json');
        $this->googleClient->useApplicationDefaultCredentials();
        $this->googleClient->addScope(Drive::DRIVE);
        $this->googleDrive = new Drive($this->googleClient);
    }

    public function getOrCreateFolderId() {
        $month = date('m');
        $googleDrive = GoogleDrive::where('month', (int)$month)->first();
        if(empty($googleDrive)) {
            $fileMetadata = new Drive\DriveFile();
            $fileMetadata->setName($month);
            $fileMetadata->setMimeType('application/vnd.google-apps.folder');
            $fileMetadata->setParents([config('google_drive.folderId')]);
            $folder = $this->googleDrive->files->create($fileMetadata, array(
                'fields' => 'id'
            ));
            $googleDrive = new GoogleDrive();
            $googleDrive->fill([
                'folder_id' => $folder->id,
                'month' => (int) $month
            ]);
            $googleDrive->save();
        }

        return $googleDrive;
    }

    public function createFile($nameFile, $content, $mimeType) {
        $folder = $this->getOrCreateFolderId();
        $this->setFolder($folder);
        $fileMetadata = new Drive\DriveFile(['name' => $nameFile]);
        $fileMetadata->setParents([$folder->folder_id]);
        $file = $this->googleDrive->files->create($fileMetadata, array(
            'data' => $content,
            'mimeType' => $mimeType,
            'uploadType' => 'multipart',
            'fields' => 'id'));
        $this->setFile($file);
        return $this;
    }

    public function deleteFile($fileId) {
        $this->googleDrive->files->delete($fileId);
        return $this;
    }

    public function setFolder($folder) {
        return $this->folder = $folder;
    }

    public function getFolder() {
        return $this->folder;
    }

    public function setFile($file) {
        return $this->file = $file;
    }

    public function getFile() {
        return $this->file;
    }
}
