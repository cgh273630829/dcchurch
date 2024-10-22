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
$logger->write("memberName: $memberName, numberOfPeople: $numberOfPeople");
// 取得當前時間
$currentDateTime = date('Y-m-d H:i:s');

// 計算金額
$amount = 200 * $numberOfPeople;

// 插入資料
$sql = "INSERT INTO trade_records (member, store, amount, create_time, check_flag) VALUES (?, 'Helper', ?, ?, false)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sis", $memberName, $amount, $currentDateTime);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    $id = $stmt->insert_id;
    $logger->write("id: $id");
    // 將 ID 轉換為 5 位數的字串，前面補上 0
    $id_padded = str_pad($id, 5, '0', STR_PAD_LEFT);
    $logger->write("id_padded: '$id_padded'");
    $key = generateKey();
    $encrypted = encryptData($key, $id_padded);
    $logger->write("encrypted: $encrypted");

    // 更新 member_id 欄位
    $update_sql = "UPDATE trade_records SET member_id = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("si", $id_padded, $id);
    $update_stmt->execute();
    
    // 檢查更新是否成功
    if ($update_stmt->affected_rows > 0) {
        $logger->write("member_id updated successfully");
        echo json_encode(['success' => true, 'memberId' => $id_padded,'encrypted' => $encrypted]);
    } else {
        $logger->write("Failed to update member_id");
        echo json_encode(["success" => false, "message" => "插入失敗: " . $stmt->error]);
    }
    $update_stmt->close();
    // echo json_encode(['success' => true, 'id' => $id,'encrypted' => $encrypted]);
} else {
    echo json_encode(["success" => false, "message" => "插入失敗: " . $stmt->error]);
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
