<?php

namespace App\Services;

class LogFileService
{
    public function writeLog($type, $message)
    {
        $fileName = storage_path('logs'.DIRECTORY_SEPARATOR.date('Y-m-d').'-'.$type.'.log');
        $openFile = fopen($fileName, 'a');
        $message = '['.date('Y-m-d H:i:s').'] '.env('APP_ENV', 'production').'.INFO: '.$message."\n";
        fwrite($openFile, $message);
        fclose($openFile);
    }

}
