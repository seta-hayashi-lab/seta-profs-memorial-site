<?php
/**
 * お問い合わせ・パスワード申請 API
 * 問い合わせ内容を Slack チャンネルに通知する
 */

// 設定ファイルの読み込み
$configPath = __DIR__ . '/config.php';
if (!file_exists($configPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => '設定ファイルが見つかりません']);
    exit;
}
$config = require $configPath;

// CORS ヘッダー
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . $config['allowed_origin']);
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// プリフライトリクエストへの対応
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// POST リクエストのみ許可
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

/**
 * Slack API にメッセージを投稿
 */
function postToSlack($token, $channel, $text, $blocks = null) {
    $payload = [
        'channel' => $channel,
        'text' => $text,
    ];

    if ($blocks) {
        $payload['blocks'] = $blocks;
    }

    $ch = curl_init('https://slack.com/api/chat.postMessage');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json; charset=utf-8',
        ],
        CURLOPT_RETURNTRANSFER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'http_code' => $httpCode,
        'response' => json_decode($response, true),
    ];
}

/**
 * 入力値のサニタイズ
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * 問い合わせ種別のラベルを取得
 */
function getInquiryTypeLabel($type) {
    $labels = [
        'password_request' => 'パスワード申請',
        'other' => 'その他のお問い合わせ',
    ];
    return $labels[$type] ?? $type;
}

/**
 * 関係性のラベルを取得
 */
function getRelationshipLabel($relationship) {
    $labels = [
        'student' => '教え子・学生',
        'colleague' => '同僚・共同研究者',
        'academic' => '学会関係者',
        'friend' => '友人・知人',
        'family' => 'ご親族',
        'other' => 'その他',
    ];
    return $labels[$relationship] ?? $relationship;
}

try {
    // JSON入力を取得
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('リクエストデータが不正です');
    }

    // 入力値を取得・サニタイズ
    $type = sanitizeInput($input['type'] ?? '');
    $name = sanitizeInput($input['name'] ?? '');
    $email = sanitizeInput($input['email'] ?? '');
    $affiliation = sanitizeInput($input['affiliation'] ?? '');
    $relationship = sanitizeInput($input['relationship'] ?? '');
    $relationshipDetail = sanitizeInput($input['relationshipDetail'] ?? '');
    $message = sanitizeInput($input['message'] ?? '');

    // 必須項目のバリデーション
    if (empty($type)) {
        throw new Exception('お問い合わせ種別を選択してください');
    }
    if (empty($name)) {
        throw new Exception('お名前は必須です');
    }
    if (empty($email)) {
        throw new Exception('メールアドレスは必須です');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('メールアドレスの形式が正しくありません');
    }
    if (empty($relationship)) {
        throw new Exception('ご関係を選択してください');
    }

    // 関係性の表示テキストを作成
    $relationshipText = getRelationshipLabel($relationship);
    if (!empty($relationshipDetail)) {
        $relationshipText .= "（{$relationshipDetail}）";
    }

    // Slack メッセージのフォーマット
    $typeLabel = getInquiryTypeLabel($type);
    $isPasswordRequest = ($type === 'password_request');

    $slackText = $isPasswordRequest
        ? "パスワード申請がありました"
        : "お問い合わせがありました";

    $headerText = $isPasswordRequest
        ? 'パスワード申請'
        : 'お問い合わせ';

    $blocks = [
        [
            'type' => 'header',
            'text' => [
                'type' => 'plain_text',
                'text' => $headerText,
                'emoji' => true,
            ],
        ],
        [
            'type' => 'section',
            'fields' => [
                ['type' => 'mrkdwn', 'text' => "*種別:*\n{$typeLabel}"],
                ['type' => 'mrkdwn', 'text' => "*お名前:*\n{$name}"],
            ],
        ],
        [
            'type' => 'section',
            'fields' => [
                ['type' => 'mrkdwn', 'text' => "*メールアドレス:*\n{$email}"],
                ['type' => 'mrkdwn', 'text' => "*ご所属:*\n" . ($affiliation ?: '未記入')],
            ],
        ],
        [
            'type' => 'section',
            'text' => [
                'type' => 'mrkdwn',
                'text' => "*瀬田先生とのご関係:*\n{$relationshipText}",
            ],
        ],
    ];

    // メッセージがある場合のみ追加
    if (!empty($message)) {
        $blocks[] = [
            'type' => 'section',
            'text' => [
                'type' => 'mrkdwn',
                'text' => "*メッセージ・備考:*\n{$message}",
            ],
        ];
    }

    // パスワード申請の場合は対応案内を追加
    if ($isPasswordRequest) {
        $blocks[] = [
            'type' => 'context',
            'elements' => [
                [
                    'type' => 'mrkdwn',
                    'text' => ":key: パスワードを `{$email}` 宛に送信してください",
                ],
            ],
        ];
    }

    $blocks[] = [
        'type' => 'context',
        'elements' => [
            [
                'type' => 'mrkdwn',
                'text' => "受信日時: " . date('Y-m-d H:i:s'),
            ],
        ],
    ];

    // Slackに投稿
    $result = postToSlack(
        $config['slack_bot_token'],
        $config['slack_channel_id'],
        $slackText,
        $blocks
    );

    if (!($result['response']['ok'] ?? false)) {
        throw new Exception($result['response']['error'] ?? 'Slack への通知に失敗しました');
    }

    echo json_encode([
        'success' => true,
        'message' => 'お問い合わせを受け付けました'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
