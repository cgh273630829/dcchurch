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

// 從 POST 請求中獲取資料
$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'];
// $check = $data['check'];

// 更新資料庫的 SQL 語句
$sql = "UPDATE trade_records SET check_flag = false WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    // 获取更新后的 member_id
    // $sqlSelect = "SELECT member_id FROM trade_records WHERE id = ?";
    // $stmtSelect = $conn->prepare($sqlSelect);
    // $stmtSelect->bind_param("i", $id);
    // $stmtSelect->execute();
    // $result = $stmtSelect->get_result();
    // if ($row = $result->fetch_assoc()) {
    // // if ($stmtSelect->execute()) {
    //     // $result = $stmtSelect->get_result();
        
    //     // if ($result->num_rows > 0) {
    //     // $row = $result->fetch_assoc();
    //     $memberId = $row['member_id'];
    //     $logger->write("memberId: '$memberId'");
    //     $key = generateKey();
    //     $encrypted = encryptData($key, $memberId);
    //     $logger->write("update encrypted: $encrypted");
        echo json_encode(['success' => true]);
    // } else {
    //     echo json_encode(['success' => false, 'message' => '找不到該ID的member_id']);
    // }
    // } else {
    //     echo json_encode(['success' => false, 'message' => '無法查詢member_id']);
    // }
    // echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}

$stmt->close();
$conn->close();

function generateKey() {
    global $logger;
    $logger->write("generateKey");
    $secretKeyStr = getenv('DC2024_SECRET_KEY');
    if ($secretKeyStr === false) {
        // 生成隨機 256 位密鑰
        $logger->write("openssl_random_pseudo_bytes: $secretKeyStr");
        $secretKey = openssl_random_pseudo_bytes(32); // 32 bytes = 256 bits
        $logger->write("create new secretKey: " . bin2hex($secretKey));
    } else {
        $secretKey = hex2bin($secretKeyStr);
        if ($secretKey === false) {
            throw new Exception('Invalid secret key format in environment variable.');
        }
        $logger->write("get secretKey: " . bin2hex($secretKey));
    }
    
    return $secretKey;
}

function encryptData($key, $data) {
    global $logger;
    $logger->write("encryptData");
    $logger->write("key: $key, data: $data");
    // 生成隨機 IV
    $iv = openssl_random_pseudo_bytes(12); // AES-GCM 通常使用 12 字節的 IV
    // 使用 AES-GCM 加密
    $cipherText = openssl_encrypt($data, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);

    // 將 IV 和密文結合並轉為 Base64 格式
    return base64_encode($iv . $cipherText . $tag); // 將 IV, 密文和標籤一起編碼
}

?>
