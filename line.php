<?php
// 你的 LINE Login Channel 資訊
$channelId = '2006451872';
$channelSecret = '9a35cb02015fcc4fd448bc3f29f9e52e';
$redirectUri = 'https://www.dcchurch.idv.tw/christmas/line.php';  // 替換為你的重定向 URI

// 授權碼
$code = $_GET['code'];

// 使用授權碼換取 access token
$tokenUrl = 'https://api.line.me/oauth2/v2.1/token';
$data = [
    'grant_type' => 'authorization_code',
    'code' => $code,
    'redirect_uri' => $redirectUri,
    'client_id' => $channelId,
    'client_secret' => $channelSecret
];

$options = [
    'http' => [
        'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => http_build_query($data),
    ],
];

$context  = stream_context_create($options);
$response = file_get_contents($tokenUrl, false, $context);
if ($response === FALSE) {
    die('Error');
}

$responseData = json_decode($response, true);
$accessToken = $responseData['access_token'];

// 使用 access token 來獲取用戶資訊
$userInfoUrl = 'https://api.line.me/v2/profile';
$options = [
    'http' => [
        'header'  => "Authorization: Bearer {$accessToken}\r\n",
        'method'  => 'GET',
    ],
];

$context = stream_context_create($options);
$userInfo = file_get_contents($userInfoUrl, false, $context);

if ($userInfo === FALSE) {
    die('Error');
}

// 解析用戶資訊，取得 userId
$userData = json_decode($userInfo, true);
$userId = $userData['userId'];

// 在網頁上顯示 userId
echo "<h1>你的 LINE 用戶 ID 是：$userId</h1>";
?>
