<?php
require_once 'Log.php';
// 創建 Log 類的實例
$logger = new Log('/var/log/php_errors.log');
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

// 獲取 POST 數據
$data = json_decode(file_get_contents("php://input"), true);
$memberName = $data['memberName'];
$numberOfPeople = $data['numberOfPeople'];
$userid = $data['userid'];
$logger->write("memberName: $memberName, numberOfPeople: $numberOfPeople, userid: $userid");
// 取得當前時間
$currentDateTime = date('Y-m-d H:i:s');

// 計算金額
$amount = 200 * $numberOfPeople;
$id = 0;
$stmt = $conn->prepare("SELECT 
                            id 
                        FROM members
                        WHERE user_id = ?");
$stmt->bind_param("s", $userid);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $id = $row['id'] ;
} else {
    $sql = "INSERT INTO members (name, user_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $memberName, $userid);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        $id = $stmt->insert_id;
    } else {
        echo json_encode(["success" => false, "message" => "插入失敗: " . $stmt->error]);
    }
}
if ($id != 0) {
    $id_padded = str_pad($id, 5, '0', STR_PAD_LEFT);
    // 寫入領券資料
    $sql = "INSERT INTO trade_records (member_id, amount, create_time) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $id, $amount, $currentDateTime);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $key = generateKey();
        $encrypted = encryptData($key, $userid);
        $logger->write("encrypted: $encrypted");
    
        echo json_encode(['success' => true, 'memberId' => $id_padded,'encrypted' => $encrypted]);
    } else {
        echo json_encode(["success" => false, "message" => "插入失敗: " . $stmt->error]);
    }
} else {
    echo json_encode(["success" => false, "message" => "作業失敗: " . $stmt->error]);
}

// 關閉連接
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

// function decryptData($key, $data) {
//     // 從 Base64 中解碼
//     $combined = base64_decode($data);

//     // 從解碼的資料中提取 IV、密文和標籤
//     $iv = substr($combined, 0, 12); // 前 12 字節是 IV
//     $tag = substr($combined, -16); // 最後 16 字節是標籤
//     $cipherText = substr($combined, 12, -16); // 剩下的是密文

//     // 使用 AES-GCM 解密
//     $decrypted = openssl_decrypt($cipherText, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    
//     if ($decrypted === false) {
//         throw new Exception('Decryption failed: ' . openssl_error_string());
//     }

//     return $decrypted; // 返回明文
// }

?>
