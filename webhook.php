<?php
// 設定 Content-Type 為 JSON
header("Access-Control-Allow-Origin: https://dcch-70th.web.app");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");

// 處理預檢請求（CORS preflight）
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204); // No Content
    exit();
}

header("Content-Type: application/json");

// 讀取來自 LINE 的請求
$content = file_get_contents("php://input");
$events = json_decode($content, true)['events'];

// LINE 的 Channel Access Token
$accessToken = 'ofmB5zZiU0WbjMxqGpNrnfpRidcxFWzorV5sPy7LzAWKUvbjRDzOwGXmh5fI+eskCchxdMfD3hpwH/KuJlb6l7zV65rmu0VlKNoRop8+VcpyNnxTlzBu2XhAjlHrZ7bUNrzx+6+S1+blE21AaawkwAdB04t89/1O/w1cDnyilFU='; // 替換為你的 Channel Access Token

// 逐一處理事件
foreach ($events as $event) {
    if ($event['type'] == 'message') {
        $replyToken = $event['replyToken'];
        $userId = $event['source']['userId'];
        $message = $event['message']['text'];

        // 根據收到的消息給予不同的回應
        $responseMessage = handleIncomingMessage($message, $userId, $accessToken);
        
        // 回覆消息
        replyMessage($replyToken, $responseMessage, $accessToken);
    }
}

$input = json_decode(file_get_contents('php://input'), true);

if (isset($input['to']) && isset($input['message'])) {
    $userId = $input['to'];
    $msg = $input['message'];

    // 呼叫 LINE 推播
    pushMessage($userId, $msg, $accessToken);

    echo json_encode(['status' => 'pushed', 'userid' => $userId, 'msg' => $msg]);
    exit;
}

function handleIncomingMessage($message, $userId, $accessToken) {
    // 取得使用者名稱
    $userName = getUserName($userId, $accessToken);
    $encodedUserName = urlencode($userName); // 使用 URL 編碼
    $lowerMessage = strtolower(trim($message)); // 將消息轉為小寫以便於比較
    
    // 根據消息內容返回不同的回應
    switch ($lowerMessage) {
        case '70周年':
        case '70週年':
        case '70':
            return createButtonMessage("上傳照片", "https://dcch-70th.web.app?member=$encodedUserName");
        // case '餐券':
        //     return createButtonMessage("領取餐券", "https://www.wjtolive.com/christmas/claim.html?member=$encodedUserName&userid=$userId");
        // case '市集':
        //     return createButtonMessage("逛市集", "https://www.wjtolive.com/christmas/dashboard.html?member=$encodedUserName&userid=$userId");
        // case '結帳':
        //     return createButtonMessage("設定結帳", "https://www.wjtolive.com/christmas/checkout.html?member=$encodedUserName&userid=$userId");
        case 'cgh':
            return "Hi! $userName, $userId";
    }
}

// 創建包含按鈕的消息
function createButtonMessage($text, $link) {
    return [
        'type' => 'template',
        'altText' => '這是一個按鈕樣板',
        'template' => [
            'type' => 'buttons',
            'title' => '70週年紀念冊',
            'text' => '請點擊按鈕前往',
            'actions' => [
                [
                    'type' => 'uri',
                    'label' => $text,
                    'uri' => $link // 替換為你的網址
                ],
            ],
        ],
    ];
}

// 取得使用者名稱的函數
function getUserName($userId, $accessToken) {
    $url = "https://api.line.me/v2/bot/profile/$userId";
    $options = [
        'http' => [
            'header' => "Authorization: Bearer $accessToken\r\n",
            'method' => 'GET'
        ]
    ];
    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    
    if ($response === FALSE) {
        error_log("Error fetching user profile.");
        return "使用者";
    }

    $profile = json_decode($response, true);
    return $profile['displayName'] ?? "使用者";
}

// 回覆消息的函數
function replyMessage($replyToken, $message, $accessToken) {
    $url = 'https://api.line.me/v2/bot/message/reply';
    
    // 如果消息是按鈕消息，則需要特別處理
    if (is_array($message)) {
        $data = [
            'replyToken' => $replyToken,
            'messages' => [$message],
        ];
    } else {
        $data = [
            'replyToken' => $replyToken,
            'messages' => [
                [
                    'type' => 'text',
                    'text' => $message,
                ],
            ],
        ];
    }
    
    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n" .
                         "Authorization: Bearer {$accessToken}\r\n",
            'method'  => 'POST',
            'content' => json_encode($data),
        ],
    ];
    
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    if ($result === FALSE) {
        error_log("Error replying message: " . print_r($result, true));
    }
}

// ✅ 推播功能：主動發訊息
function pushMessage($userId, $message, $accessToken) {
    $url = 'https://api.line.me/v2/bot/message/push';

    $data = [
        'to' => $userId,
        'messages' => [
            [
                'type' => 'text',
                'text' => $message,
            ],
        ],
    ];

    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n" .
                         "Authorization: Bearer {$accessToken}\r\n",
            'method'  => 'POST',
            'content' => json_encode($data),
        ],
    ];

    $context  = stream_context_create($options);
    file_get_contents($url, false, $context);
}

// 返回 HTTP 200
http_response_code(200);
?>
