<?php
require_once 'Log.php';
// 創建 Log 類的實例
$logger = new Log('/var/log/php_errors.log');

// 資料庫連線設定
$servername = "localhost"; // 伺服器名稱
$username = "cgh273630829"; // 使用者名稱
$password = "120531ChiaHu"; // 密碼
$dbname = "christmas_market"; // 資料庫名稱

// 建立資料庫連線
$conn = new mysqli($servername, $username, $password, $dbname);

// 檢查連線
if ($conn->connect_error) {
    die("資料庫連線失敗: " . $conn->connect_error);
}

// 取得傳入的資料
$decrypted = null;
$data = json_decode(file_get_contents('php://input'), true);
$encryptedId = $data['encryptedId'] ?? null;
$logger->write("scan encryptedId: $encryptedId");
if ($encryptedId) {
    $key = generateKey();
    $decrypted = decryptData($key, $encryptedId);
    $logger->write("decrypted: $decrypted");
    if ($decrypted === '00001' && isset($data['memberId'])) {
            $memberId = $data['memberId'];
            $logger->write("memberId: $memberId");
            // 更新資料庫，將對應 id 的 check_flag 改為 true
            $sql = "UPDATE trade_records SET check_flag = 1 WHERE member_id = ? and store = 'Helper'";
        
            // 預備及綁定參數
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $memberId);
        
            if ($stmt->execute()) {
                $logger->write("active success");
                echo json_encode(['success' => true]);
            } else {
                $logger->write("failed");
                echo json_encode(['success' => false, 'message' => '資料更新失敗']);
            }
        
            // 關閉語句與連線
            $stmt->close();
        
    } else {
        echo json_encode(['success' => false, 'message' => '無效的請求']);
    }
} else {
    echo json_encode(['success' => false, 'message' => '無效的請求']);
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
