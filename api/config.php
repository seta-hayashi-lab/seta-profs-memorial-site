<?php
/**
 * 設定ファイル
 * ドキュメントルートの .env ファイルから環境変数を読み込む
 */

/**
 * .env ファイルを読み込んで環境変数として設定
 */
function loadEnv($path) {
    if (!file_exists($path)) {
        return false;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        // コメント行をスキップ
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }

        // KEY=VALUE 形式をパース
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // クォートを除去
            if (preg_match('/^["\'].*["\']$/', $value)) {
                $value = substr($value, 1, -1);
            }

            // 環境変数として設定
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }
    }

    return true;
}

// ドキュメントルートの .env を読み込み
$envPath = dirname(__DIR__) . '/.env';
if (!loadEnv($envPath)) {
    // .env が見つからない場合はエラー
    if (php_sapi_name() !== 'cli') {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => '.env ファイルが見つかりません。.env.example を参考に .env を作成してください。'
        ]);
        exit;
    }
}

/**
 * 環境変数を取得（デフォルト値付き）
 */
function env($key, $default = null) {
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }
    return $value;
}

// 設定値を返す
return [
    // Slack API 設定（.env から読み込み）
    'slack_bot_token' => env('SLACK_BOT_TOKEN', ''),
    'slack_channel_id' => env('SLACK_CHANNEL_ID', ''),

    // セキュリティ設定
    'allowed_origin' => env('ALLOWED_ORIGIN', '*'),

    // ファイルアップロード設定
    'allowed_file_types' => [
        // 画像
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        // 動画
        'video/mp4',
        'video/webm',
        'video/quicktime',  // .mov
        'video/x-msvideo',  // .avi
    ],
    'max_file_size' => (int) env('MAX_FILE_SIZE', 1 * 1024 * 1024 * 1024),  // 1GB

    // 認証設定
    'auth' => [
        'admin_password' => env('ADMIN_PASSWORD', ''),
        'user_password' => env('USER_PASSWORD', ''),
        'session_lifetime' => (int) env('SESSION_LIFETIME', 86400),  // 24時間
    ],
];
