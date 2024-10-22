<?php
require_once 'Log.php';
// 創建 Log 類的實例
$logger = new Log('/var/log/php_errors.log');
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$encryptedId = $data['encryptedId'] ?? null;
$logger->write("balance encryptedId: $encryptedId");
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

    if ($decrypted) {
        // 查詢該會員的所有交易並計算剩餘金額
        $sql = "SELECT member, IFNULL(SUM(amount), 0) as balance FROM trade_records WHERE member_id = ? and check_flag = true";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $decrypted);
        $stmt->execute();
        $result = $stmt->get_result();
        $balance = 0;
        
        if ($row = $result->fetch_assoc()) {
            $balance = $row['balance'];
            $member = $row['member'];
        }
    
        echo json_encode(['balance' => $balance, 'member' => $member, 'member_id' => $decrypted]);
    } else {
        echo json_encode(['error' => 'No member specified']);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false]);
}



// // 資料庫連線
// $servername = "localhost"; // 伺服器名稱
// $username = "cgh273630829"; // 使用者名稱
// $password = "120531ChiaHu"; // 密碼
// $dbname = "christmas_market"; // 資料庫名稱

// $conn = new mysqli($servername, $username, $password, $dbname);

// if ($conn->connect_error) {
//     die("Connection failed: " . $conn->connect_error);
// }

// // 獲取 URL 中的 member 參數
// $member = isset($_GET['member']) ? $_GET['member'] : '';


// if ($member) {
//     // 查詢該會員的所有交易並計算剩餘金額
//     $sql = "SELECT IFNULL(SUM(amount), 0) as balance FROM trade_records WHERE member_id = ? and check_flag = true";
//     $stmt = $conn->prepare($sql);
//     $stmt->bind_param("s", $member);
//     $stmt->execute();
//     $result = $stmt->get_result();
//     $balance = 0;
    
//     if ($row = $result->fetch_assoc()) {
//         $balance = $row['balance'];
//     }

//     echo json_encode(['balance' => $balance]);
// } else {
//     echo json_encode(['error' => 'No member specified']);
// }

// $conn->close();


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
