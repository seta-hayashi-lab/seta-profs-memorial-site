<?php
/**
 * Slack 投稿 API
 * 追悼メッセージや画像を Slack チャンネルに投稿する
 */

// エラー表示を抑制（本番環境用）
// error_reporting(0);

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
 * Slack API にファイルをアップロード（準備のみ - Step 1 & 2）
 */
function prepareFileUpload($token, $filePath, $fileName) {
    // Step 1: アップロード URL を取得
    $fileSize = filesize($filePath);

    $ch = curl_init('https://slack.com/api/files.getUploadURLExternal');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'filename' => $fileName,
            'length' => $fileSize,
        ]),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
        ],
        CURLOPT_RETURNTRANSFER => true,
    ]);

    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (!$response['ok']) {
        return ['ok' => false, 'error' => $response['error'] ?? 'Failed to get upload URL'];
    }

    $uploadUrl = $response['upload_url'];
    $fileId = $response['file_id'];

    // Step 2: ファイルをアップロード
    $ch = curl_init($uploadUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => file_get_contents($filePath),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/octet-stream',
        ],
        CURLOPT_RETURNTRANSFER => true,
    ]);

    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return ['ok' => false, 'error' => 'Failed to upload file'];
    }

    return ['ok' => true, 'file_id' => $fileId];
}

/**
 * アップロード済みファイルをチャンネル/スレッドに公開（Step 3）
 */
function completeFileUpload($token, $channel, $files, $threadTs = null) {
    $payload = [
        'files' => $files,
        'channel_id' => $channel,
    ];

    if ($threadTs) {
        $payload['thread_ts'] = $threadTs;
    }

    $ch = curl_init('https://slack.com/api/files.completeUploadExternal');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json; charset=utf-8',
        ],
        CURLOPT_RETURNTRANSFER => true,
    ]);

    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);

    return $response;
}

/**
 * 入力値のサニタイズ
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// リクエストの種類を判定
$type = $_POST['type'] ?? 'message';

try {
    if ($type === 'message') {
        // 追悼メッセージの投稿（ファイル添付対応）
        $name = sanitizeInput($_POST['name'] ?? '');
        $affiliation = sanitizeInput($_POST['affiliation'] ?? '');
        $relationship = sanitizeInput($_POST['relationship'] ?? '');
        $message = sanitizeInput($_POST['message'] ?? '');
        $publish = $_POST['publish'] ?? 'yes';

        if (empty($name)) {
            throw new Exception('お名前は必須です');
        }

        // ファイルの有無を確認
        $hasFiles = isset($_FILES['files']) && !empty($_FILES['files']['name'][0]);
        $fileCount = 0;
        $uploadedFiles = [];
        $fileErrors = [];

        // ファイルがある場合は先にアップロード（ファイルIDを取得）
        if ($hasFiles) {
            $files = $_FILES['files'];
            $maxSizeMB = round($config['max_file_size'] / (1024 * 1024));
            $fileCount = is_array($files['name']) ? count($files['name']) : 1;

            for ($i = 0; $i < $fileCount; $i++) {
                if (is_array($files['name'])) {
                    $fileName = $files['name'][$i];
                    $tmpName = $files['tmp_name'][$i];
                    $fileError = $files['error'][$i];
                    $fileSize = $files['size'][$i];
                } else {
                    $fileName = $files['name'];
                    $tmpName = $files['tmp_name'];
                    $fileError = $files['error'];
                    $fileSize = $files['size'];
                }

                if ($fileError !== UPLOAD_ERR_OK) {
                    $fileErrors[] = "{$fileName}: アップロードエラー";
                    continue;
                }

                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($tmpName);

                if (!in_array($mimeType, $config['allowed_file_types'])) {
                    $fileErrors[] = "{$fileName}: 許可されていないファイル形式です";
                    continue;
                }

                if ($fileSize > $config['max_file_size']) {
                    $fileErrors[] = "{$fileName}: ファイルサイズが大きすぎます（最大 {$maxSizeMB}MB）";
                    continue;
                }

                // ファイルをアップロード（Step 1 & 2のみ、completeは後で一括）
                $uploadResult = prepareFileUpload($config['slack_bot_token'], $tmpName, $fileName);
                if ($uploadResult['ok']) {
                    $uploadedFiles[] = ['id' => $uploadResult['file_id'], 'title' => $fileName];
                } else {
                    $fileErrors[] = "{$fileName}: " . ($uploadResult['error'] ?? 'アップロード準備に失敗');
                }
            }
        }

        // Slack メッセージのフォーマット
        $slackText = "新しい追悼メッセージが投稿されました";
        $blocks = [
            [
                'type' => 'header',
                'text' => [
                    'type' => 'plain_text',
                    'text' => '新しい追悼メッセージ',
                ],
            ],
            [
                'type' => 'section',
                'fields' => [
                    ['type' => 'mrkdwn', 'text' => "*投稿者:*\n{$name}"],
                    ['type' => 'mrkdwn', 'text' => "*所属:*\n" . ($affiliation ?: '未記入')],
                ],
            ],
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => "*ご関係:*\n" . ($relationship ?: '未記入'),
                ],
            ],
        ];

        // メッセージがある場合のみ追加
        if (!empty($message)) {
            $blocks[] = [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => "*メッセージ:*\n{$message}",
                ],
            ];
        }

        // 添付ファイル情報を追加
        if (count($uploadedFiles) > 0) {
            $blocks[] = [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => "*添付ファイル:* " . count($uploadedFiles) . "件",
                ],
            ];
        }

        $blocks[] = [
            'type' => 'context',
            'elements' => [
                [
                    'type' => 'mrkdwn',
                    'text' => "サイト掲載: " . ($publish === 'yes' ? '希望する' : '希望しない'),
                ],
            ],
        ];

        // メッセージを投稿
        $result = postToSlack(
            $config['slack_bot_token'],
            $config['slack_channel_id'],
            $slackText,
            $blocks
        );

        if (!($result['response']['ok'] ?? false)) {
            throw new Exception($result['response']['error'] ?? 'Slack への投稿に失敗しました');
        }

        $threadTs = $result['response']['ts'] ?? null;

        // アップロード済みファイルをスレッドに添付
        if (count($uploadedFiles) > 0 && $threadTs) {
            $completeResult = completeFileUpload(
                $config['slack_bot_token'],
                $config['slack_channel_id'],
                $uploadedFiles,
                $threadTs
            );

            if (!($completeResult['ok'] ?? false)) {
                $fileErrors[] = 'ファイルの添付に失敗: ' . ($completeResult['error'] ?? '');
            }
        }

        // 結果を返す
        $responseMessage = 'メッセージを送信しました';
        if (count($uploadedFiles) > 0) {
            $responseMessage = 'メッセージと' . count($uploadedFiles) . '件のファイルを送信しました';
        }
        if (count($fileErrors) > 0) {
            $responseMessage .= '（' . count($fileErrors) . '件のエラーあり）';
        }

        echo json_encode([
            'success' => true,
            'message' => $responseMessage,
            'errors' => $fileErrors
        ]);

    } else {
        throw new Exception('不明なリクエストタイプです');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
