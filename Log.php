<?php
class Log {
    private $logFile;

    public function __construct($fileName) {
        // 設定日誌檔案的路徑
        $this->logFile = $fileName;
    }

    public function write($message) {
        // 將訊息寫入日誌檔案
        $timestamp = date("Y-m-d H:i:s");
        $logMessage = "[$timestamp] $message\n";
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }

    public function writeArray($array) {
        // 將陣列寫入日誌檔案
        $this->write(json_encode($array));
    }
}
