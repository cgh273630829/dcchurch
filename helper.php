<?php
header('Content-Type: application/json');

// 資料庫連接設定
$servername = "localhost"; // 伺服器名稱
$username = "cgh273630829"; // 使用者名稱
$password = "120531ChiaHu"; // 密碼
$dbname = "christmas_market"; // 資料庫名稱

// 建立連接
$conn = new mysqli($servername, $username, $password, $dbname);

// 檢查連接
if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "連接失敗: " . $conn->connect_error]));
}

// 查詢 store 為 Helper 的所有餐券記錄
$sql = "SELECT tr.member_id, m.name, tr.amount, tr.trade_time, tr.check_flag, s.name AS store_name
        FROM trade_records tr 
        JOIN members m 
        ON tr.member_id = m.id  
        LEFT JOIN stores s
        ON m.store = s.id
        WHERE tr.store_id = 1 ORDER BY trade_time DESC";
$result = $conn->query($sql);

$coupons = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $coupons[] = $row;
    }
}

// 輸出 JSON 結果
echo json_encode($coupons);

// 關閉連接
$conn->close();
?>
