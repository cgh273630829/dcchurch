<?php
require_once 'Log.php';
// 創建 Log 類的實例
$logger = new Log('/var/log/php_errors.log');
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$encryptedId = $data['encryptedId'] ?? null;
$logger->write("claim_info encryptedId: $encryptedId");
if ($encryptedId) {
    $servername = "localhost"; // 伺服器名稱
    $username = "cgh273630829"; // 使用者名稱
    $password = "120531ChiaHu"; // 密碼
    $dbname = "christmas_market"; // 資料庫名稱
    // 假設你有一個資料庫連接
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        echo json_encode(['success' => false]);
        exit();
    }
    
    $key = generateKey();
    $decrypted = decryptData($key, $encryptedId);
    // $id = ltrim($decrypted, '0');
    // $logger->write("id: $id");
    // 查詢資料庫
    $stmt = $conn->prepare("SELECT 
                                m.id AS member_id, 
                                m.name AS member_name, 
                                tr.amount, 
                                tr.check_flag 
                            FROM trade_records tr 
                            JOIN members m 
                            ON tr.member_id = m.id
                            WHERE m.user_id = ?");
    // $stmt = $conn->prepare("SELECT member_id, member, amount, check_flag FROM trade_records WHERE id = ?");
    $stmt->bind_param("i", $decrypted);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode(['success' => true,
            'member_id' => $row['member_id'],  
            'member' => $row['member_name'], 
            'amount' => $row['amount'],
            'check_flag' => $row['check_flag']]);
    } else {
        echo json_encode(['success' => false]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false]);
}

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
