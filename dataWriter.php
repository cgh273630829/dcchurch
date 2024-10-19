<?php
// 資料庫連線資訊
$servername = "localhost"; // 資料庫伺服器
$username = "cgh273630829";        // 資料庫用戶名
$password = "120531ChiaHu";            // 資料庫密碼
$dbname = "christmas_market"; // 資料庫名稱

// 建立資料庫連線
$conn = new mysqli($servername, $username, $password, $dbname);

// 檢查連線是否成功
if ($conn->connect_error) {
    die("連線失敗: " . $conn->connect_error);
}

// 要插入的資料
$user = "Test People";
$store = "Helper";
$amount = -123;
$create_time = "2024-10-12 09:50:00";
$check = false;

// 準備 SQL 語句
$sql = "INSERT INTO trade_records (member, store, amount, create_time, check_flag)
        VALUES (?, ?, ?, ?, ?)";

// 準備和綁定
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssisi", $user, $store, $amount, $create_time, $check);

// 執行 SQL 語句
if ($stmt->execute()) {
    echo "新記錄插入成功";
} else {
    echo "插入記錄時出錯: " . $stmt->error;
}

// 關閉連線
$stmt->close();
$conn->close();
?>
