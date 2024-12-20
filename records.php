<?php
require_once 'Log.php';
// 創建 Log 類的實例
$logger = new Log('/var/log/php_errors.log');

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$encryptedId = $data['encryptedId'] ?? null;
$logger->write("encryptedId: $encryptedId");
$member = $data['member'] ?? '%';
$store = $data['store'] ?? '%';
$check_flag = $data['check_flag'] ?? '%';

if ($encryptedId && $member === '%') {
    $key = generateKey();
    $member = decryptData($key, $encryptedId);
}

$logger->write("member: $member, store: $store, check_flag: $check_flag");
if ($store == 'all') {
    $store = '%';
}
if ($check_flag === true) {
    $check_flag = '1';
} else if ($check_flag === false) {
    $check_flag = '0';
}
// 連接資料庫
$servername = "localhost"; // 伺服器名稱
$username = "cgh273630829"; // 使用者名稱
$password = "120531ChiaHu"; // 密碼
$dbname = "christmas_market"; // 資料庫名稱

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 從 GET 請求中獲取參數
// $store = isset($_GET['store']) ? $_GET['store'] : 'all';
// $check_flag = isset($_GET['check_flag']) ? $_GET['check_flag'] : 'false';
$logger->write("member: $member, store: $store, check_flag: $check_flag");
// 取得當前時間
// 建立查詢語句
$storeSql = "AND s.store_id LIKE ?";
if ($store === '541CB233B2451B82086BB437AC316D6C') {
    $storeSql = "AND s.name LIKE '小老闆的店%'";
}
$sql = "SELECT tr.id, m.user_id, m.name AS member_name, s.name AS store_name, tr.amount, tr.trade_time, tr.check_flag
        FROM trade_records tr
        JOIN members m
        ON tr.member_id = m.id
        JOIN stores s
        ON tr.store_id = s.id
        WHERE m.user_id LIKE ? $storeSql AND tr.check_flag LIKE ?
        ORDER BY tr.trade_time DESC"; // 確保能夠正常運作

// 根據選擇的店家過濾
// if ($store !== 'all') {
//     $sql .= " AND store = ?";
// }

// // 根據 check_flag 過濾
// if ($check_flag === 'true') {
//     $sql .= " AND check_flag = 1";
// }
// $sql .= " ORDER BY trade_time DESC";
$logger->write("sql: $sql");
$stmt = $conn->prepare($sql);

// 如果需要，綁定參數
// if ($store !== 'all') {
if ($store === '541CB233B2451B82086BB437AC316D6C') {
    $stmt->bind_param("ss", $member, $check_flag);
} else {
    $stmt->bind_param("sss", $member, $store, $check_flag);
}
// }

// 執行查詢並返回結果
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);

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
