<?php
require_once 'Log.php';
// 創建 Log 類的實例
$logger = new Log('custom_log.txt');
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
$encryptedId = $data['encryptedId']; 
$member = $data['member']; 
$store = $data['store'];
$amount = $data['amount'];
$check_flag = $data['check_flag'];
// 記錄接收到的資料
$logger->write("Received data: encryptedId: $encryptedId, Store: $store, Amount: $amount, Check Flag: $check_flag");

$key = generateKey();
$decrypted = decryptData($key, $encryptedId);

if ($decrypted) {
    // 記錄最終的 SQL 字串 (參數會被替換成實際的值)
    $finalSql = "INSERT INTO trade_records (member_id, member, store, amount, create_time, trade_time, check_flag) 
                VALUES ('$decrypted', '$member', '$store', $amount, NOW(), NOW(), $check_flag)";

    // 將最終的 SQL 字串記錄到日誌
    $logger->write("Final SQL: $finalSql");

    // 寫入資料庫的 SQL 語句
    $sql = "INSERT INTO trade_records (member_id, member, store, amount, create_time, trade_time, check_flag) 
            VALUES (?, ?, ?, ?, NOW(), NOW(), ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssdi", $decrypted, $member, $store, $amount, $check_flag);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }

    $stmt->close();
}
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

function decryptData($key, $data) {
    // 從 Base64 中解碼
    $combined = base64_decode($data);

    // 從解碼的資料中提取 IV、密文和標籤
    $iv = substr($combined, 0, 12); // 前 12 字節是 IV
    $tag = substr($combined, -16); // 最後 16 字節是標籤
    $cipherText = substr($combined, 12, -16); // 剩下的是密文

    // 使用 AES-GCM 解密
    $decrypted = openssl_decrypt($cipherText, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    
    if ($decrypted === false) {
        throw new Exception('Decryption failed: ' . openssl_error_string());
    }

    return $decrypted; // 返回明文
}

?>
