<?php
require_once 'Log.php';
// 創建 Log 類的實例
$logger = new Log('/var/log/php_errors.log');
// 連接資料庫
$servername = "localhost"; // 伺服器名稱
$username = "cgh273630829"; // 使用者名稱
$password = "120531ChiaHu"; // 密碼
$dbname = "christmas_market"; // 資料庫名稱

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 從 GET 請求中獲取參數
$store = isset($_GET['store']) ? $_GET['store'] : 'all';
$check_flag = isset($_GET['check_flag']) ? $_GET['check_flag'] : 'false';
$logger->write("store: $store, check_flag: $check_flag");
// 取得當前時間
// 建立查詢語句
$sql = "SELECT * FROM trade_records WHERE 1=1"; // 確保能夠正常運作

// 根據選擇的店家過濾
if ($store !== 'all') {
    $sql .= " AND store = ?";
}

// 根據 check_flag 過濾
if ($check_flag === 'true') {
    $sql .= " AND check_flag = 1";
}
$sql .= " ORDER BY trade_time DESC";
$logger->write("sql: $sql");
$stmt = $conn->prepare($sql);

// 如果需要，綁定參數
if ($store !== 'all') {
    $stmt->bind_param("s", $store);
}

// 執行查詢並返回結果
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);

$stmt->close();
$conn->close();
?>
