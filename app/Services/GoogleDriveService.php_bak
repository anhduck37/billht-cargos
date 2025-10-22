<?php

namespace App\Services;

use App\GoogleDrive;
use Google_Client;
use Google\Service\Drive;
use Exception;

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

    public function getOrCreateFolderId($month=null, $year=null) {
        if(empty($month)) $month = date('m');
        if(empty($year)) $year = date('Y');
        $googleDrive = GoogleDrive::where('month', $month)->where('year', $year)->first();
        $dataGGDrive = [
            'month' => $month,
            'year' => $year
        ];
        if(empty($googleDrive)) {
            $googleDriveYear = GoogleDrive::where('year', $year)->first();
            if(empty($googleDriveYear)) {
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

    public function createFolder($name, $folderId=null) {
        $fileMetadata = new Drive\DriveFile();
        if(empty($folderId)) {
            $folderId = config('google_drive.folderId');
        }
        $fileMetadata->setName($name);
        $fileMetadata->setMimeType('application/vnd.google-apps.folder');
        $fileMetadata->setParents([$folderId]);
        $folder = $this->googleDrive->files->create($fileMetadata, array(
            'fields' => 'id'
        ));
        return $folder;
    }

    public function createFile($nameFile, $content, $mimeType, $month=null, $year=null) {
        $folder = $this->getOrCreateFolderId($month, $year);
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
        try{
            $this->googleDrive->files->delete($fileId);
        }catch(Exception $e) {

        }
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
