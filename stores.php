<?php
// 資料庫連線設定
$servername = "localhost"; // 伺服器名稱
$username = "cgh273630829"; // 使用者名稱
$password = "120531ChiaHu"; // 密碼
$dbname = "christmas_market"; // 資料庫名稱

$conn = new mysqli($servername, $username, $password, $dbname);

// 檢查連線
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 查詢不重複的 store
$sql = "SELECT DISTINCT store FROM trade_records";
$result = $conn->query($sql);

$stores = array();
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $stores[] = $row['store'];
    }
}

// 返回 JSON 格式的結果
header('Content-Type: application/json');
echo json_encode($stores);

// 關閉連線
$conn->close();
?>
