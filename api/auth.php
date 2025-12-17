<?php
/**
 * 認証 API
 * ログイン・ログアウト・認証状態確認を処理
 */

// 設定ファイルの読み込み
$configPath = __DIR__ . '/config.php';
if (!file_exists($configPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => '設定ファイルが見つかりません']);
    exit;
}
$config = require $configPath;

// セッション設定
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

// セッション開始
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CORS ヘッダー
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . $config['allowed_origin']);
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// プリフライトリクエストへの対応
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * 指定されたユーザータイプのパスワードを検証
 */
function verifyPassword($password, $userType, $config) {
    $authConfig = $config['auth'];

    if ($userType === 'admin') {
        // 管理者パスワードをチェック
        if (!empty($authConfig['admin_password']) && $password === $authConfig['admin_password']) {
            return true;
        }
    } else {
        // 一般ユーザーパスワードをチェック
        if (!empty($authConfig['user_password']) && $password === $authConfig['user_password']) {
            return true;
        }
    }

    return false;
}

/**
 * セッションの有効期限をチェック
 */
function isSessionValid($config) {
    if (!isset($_SESSION['auth_time'])) {
        return false;
    }

    $sessionLifetime = $config['auth']['session_lifetime'];
    $elapsed = time() - $_SESSION['auth_time'];

    return $elapsed < $sessionLifetime;
}

// リクエストの種類を判定
$action = $_GET['action'] ?? $_POST['action'] ?? 'check';

try {
    switch ($action) {
        case 'login':
            // POST リクエストのみ許可
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Method not allowed');
            }

            // JSON または POST データを取得
            $input = json_decode(file_get_contents('php://input'), true);
            $password = $input['password'] ?? $_POST['password'] ?? '';
            $userType = $input['userType'] ?? $_POST['userType'] ?? 'user';

            // ユーザータイプのバリデーション
            if (!in_array($userType, ['admin', 'user'])) {
                $userType = 'user';
            }

            if (empty($password)) {
                throw new Exception('パスワードを入力してください');
            }

            $isValid = verifyPassword($password, $userType, $config);

            if (!$isValid) {
                // 遅延を入れてブルートフォース攻撃を緩和
                sleep(1);
                throw new Exception('パスワードが正しくありません');
            }

            // セッションを再生成（セッション固定攻撃対策）
            session_regenerate_id(true);

            // セッションに認証情報を保存
            $_SESSION['authenticated'] = true;
            $_SESSION['user_type'] = $userType;
            $_SESSION['auth_time'] = time();

            echo json_encode([
                'success' => true,
                'message' => 'ログインしました',
                'user_type' => $userType
            ]);
            break;

        case 'logout':
            // セッションを破棄
            $_SESSION = [];

            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }

            session_destroy();

            echo json_encode([
                'success' => true,
                'message' => 'ログアウトしました'
            ]);
            break;

        case 'check':
        default:
            // 認証状態を確認
            $isAuthenticated = isset($_SESSION['authenticated'])
                && $_SESSION['authenticated'] === true
                && isSessionValid($config);

            if ($isAuthenticated) {
                echo json_encode([
                    'success' => true,
                    'authenticated' => true,
                    'user_type' => $_SESSION['user_type'] ?? 'user'
                ]);
            } else {
                // セッションが無効な場合はクリア
                if (isset($_SESSION['authenticated'])) {
                    $_SESSION = [];
                }

                echo json_encode([
                    'success' => true,
                    'authenticated' => false
                ]);
            }
            break;
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
