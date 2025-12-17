<?php
/**
 * 認証チェック（includeして使用）
 * 認証が必要なページの先頭でこのファイルをincludeする
 */

// 設定ファイルの読み込み
$configPath = __DIR__ . '/config.php';
if (!file_exists($configPath)) {
    header('Location: login.php');
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

/**
 * セッションの有効期限をチェック
 */
function checkSessionValid($config) {
    if (!isset($_SESSION['auth_time'])) {
        return false;
    }

    $sessionLifetime = $config['auth']['session_lifetime'];
    $elapsed = time() - $_SESSION['auth_time'];

    return $elapsed < $sessionLifetime;
}

/**
 * 認証状態を確認
 */
function isAuthenticated($config) {
    return isset($_SESSION['authenticated'])
        && $_SESSION['authenticated'] === true
        && checkSessionValid($config);
}

/**
 * ユーザータイプを取得
 */
function getUserType() {
    return $_SESSION['user_type'] ?? null;
}

/**
 * 管理者かどうかを確認
 */
function isAdmin() {
    return getUserType() === 'admin';
}

// 認証チェック
if (!isAuthenticated($config)) {
    // 現在のURLを取得してリダイレクト先として保存
    $currentPage = basename($_SERVER['PHP_SELF']);
    header('Location: login.php?redirect=' . urlencode($currentPage));
    exit;
}

// セッションを延長（アクセスごとに期限を更新）
$_SESSION['auth_time'] = time();
