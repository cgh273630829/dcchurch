<?php
require_once 'Log.php';
// 創建 Log 類的實例
$logger = new Log('/var/log/php_errors.log');

// 獲取環境變數
$idKey = getenv('DC2024_ID_KEY');
$logger->write("idKey: $idKey");

// 構建 JSON 响應
$response = [
    'idKey' => $idKey
];

// 設置響應頭為 JSON 格式
header('Content-Type: application/json');
echo json_encode($response);

?>