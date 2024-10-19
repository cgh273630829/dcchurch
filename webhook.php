<?php
// 設定 Content-Type 為 JSON
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
        $responseMessage = handleIncomingMessage($message, $userId);
        
        // 回覆消息
        replyMessage($replyToken, $responseMessage, $accessToken);
    }
}

// 根據不同消息內容給予特定回應的函數
function handleIncomingMessage($message, $userId) {
    $lowerMessage = strtolower(trim($message)); // 將消息轉為小寫以便於比較
    
    // 根據消息內容返回不同的回應
    switch ($lowerMessage) {
        case 'cgh':
            return "你好！你的用戶 ID 是：{$userId}。有什麼我可以幫助你的？";
    }
}

// 回覆消息的函數
function replyMessage($replyToken, $message, $accessToken) {
    $url = 'https://api.line.me/v2/bot/message/reply';
    $data = [
        'replyToken' => $replyToken,
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
    $result = file_get_contents($url, false, $context);
    if ($result === FALSE) {
        error_log("Error replying message: " . print_r($result, true));
    }
}

// 返回 HTTP 200
http_response_code(200);
?>
