<?php
// 設定 JSON 檔案的路徑
$jsonFile = './records.json';

// 讀取現有 JSON 資料
$jsonData = json_decode(file_get_contents($jsonFile), true);

// 獲取請求參數
$userId = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;
$store = $_GET['store'] ?? null;

if ($userId && $action && $store) {
    $record = [
        'user' => $userId,
        'time' => date('Y-m-d H:i:s'),
'store' => $store,
'check' => false
    ];

    if ($action == 'claim') {
        $record['amount'] = 200;  // 領取餐券
        $record['type'] = 'claim';
    } elseif ($action == 'spend') {
        $record['amount'] = -50;  // 假設每次消費 50 元
        $record['type'] = 'spend';
    } else {
        echo json_encode(['message' => 'Invalid action']);
        exit;
    }

    // 新增記錄
    $jsonData[] = $record;

    // 將資料寫回 JSON 檔案
    file_put_contents($jsonFile, json_encode($jsonData));

    echo json_encode(['message' => 'Record updated successfully']);
} else {
    echo json_encode(['message' => 'Invalid request']);
}
?>
