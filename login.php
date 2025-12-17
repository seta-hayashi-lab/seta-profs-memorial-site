<?php
/**
 * ログインページ
 */

// 設定ファイルの読み込み
$configPath = __DIR__ . '/api/config.php';
if (file_exists($configPath)) {
    $config = require $configPath;
}

// セッション開始
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// すでにログイン済みの場合はリダイレクト
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    $redirect = $_GET['redirect'] ?? 'gallery.php';
    header('Location: ' . $redirect);
    exit;
}

$redirect = htmlspecialchars($_GET['redirect'] ?? 'gallery.php', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="瀬田和久先生 追悼サイト - ログイン">
    <meta name="robots" content="noindex, nofollow">
    <title>ログイン | 瀬田和久先生 追悼サイト</title>
    <link rel="icon" type="image/png" href="images/favicon.png">
    <link rel="stylesheet" href="css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+JP:wght@400;500&family=Noto+Sans+JP:wght@400;500&display=swap" rel="stylesheet">
    <style>
        .login-section {
            min-height: calc(100vh - 200px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--spacing-xl) var(--spacing-md);
        }

        .login-container {
            max-width: 400px;
            width: 100%;
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: var(--spacing-xl);
            box-shadow: var(--shadow-lg);
        }

        .login-header {
            text-align: center;
            margin-bottom: var(--spacing-lg);
        }

        .login-title {
            font-family: var(--font-serif);
            font-size: 1.5rem;
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: var(--spacing-sm);
        }

        .login-description {
            font-size: 0.9rem;
            color: var(--text-muted);
            line-height: 1.8;
        }

        .login-form {
            margin-top: var(--spacing-lg);
        }

        .login-form .form-group {
            margin-bottom: var(--spacing-md);
        }

        .login-form label {
            display: block;
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-bottom: var(--spacing-xs);
        }

        .password-input-wrapper {
            position: relative;
            width: 100%;
        }

        .login-form input[type="password"],
        .login-form input[type="text"].password-input {
            width: 100%;
            padding: 0.8rem 2.5rem 0.8rem 1rem;
            background-color: var(--bg-medium);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            color: var(--text-primary);
            font-family: var(--font-sans);
            font-size: 1rem;
            transition: border-color var(--transition-fast);
        }

        .login-form input[type="password"]:focus,
        .login-form input[type="text"].password-input:focus {
            outline: none;
            border-color: var(--color-gold);
        }

        .password-toggle-btn {
            position: absolute;
            right: 0.5rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            padding: 0.5rem;
            cursor: pointer;
            color: var(--text-muted);
            transition: color var(--transition-fast);
            line-height: 1;
        }

        .password-toggle-btn:hover {
            color: var(--text-primary);
        }

        .password-toggle-btn svg {
            width: 20px;
            height: 20px;
            display: block;
        }

        .login-type-group {
            display: flex;
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-md);
        }

        .login-type-label {
            flex: 1;
            position: relative;
        }

        .login-type-label input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .login-type-label .type-box {
            display: block;
            padding: 0.8rem 1rem;
            background-color: var(--bg-medium);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            text-align: center;
            cursor: pointer;
            transition: all var(--transition-fast);
        }

        .login-type-label .type-box .type-name {
            display: block;
            font-size: 0.95rem;
            color: var(--text-secondary);
            margin-bottom: 2px;
        }

        .login-type-label .type-box .type-desc {
            display: block;
            font-size: 0.7rem;
            color: var(--text-dim);
        }

        .login-type-label input[type="radio"]:checked + .type-box {
            border-color: var(--color-gold);
            background-color: rgba(184, 168, 120, 0.1);
        }

        .login-type-label input[type="radio"]:checked + .type-box .type-name {
            color: var(--color-gold-light);
        }

        .login-type-label:hover .type-box {
            border-color: var(--text-dim);
        }

        .login-btn {
            width: 100%;
            padding: 0.9rem 1rem;
            background-color: var(--bg-lighter);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            font-family: var(--font-sans);
            font-size: 1rem;
            cursor: pointer;
            transition: all var(--transition-fast);
            margin-top: var(--spacing-sm);
        }

        .login-btn:hover {
            background-color: var(--color-gold);
            border-color: var(--color-gold);
            color: var(--bg-darkest);
        }

        .login-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .login-error {
            background-color: rgba(180, 80, 80, 0.15);
            border: 1px solid rgba(180, 80, 80, 0.3);
            color: #e0a0a0;
            padding: var(--spacing-sm);
            border-radius: 4px;
            font-size: 0.85rem;
            margin-top: var(--spacing-md);
            display: none;
        }

        .login-error.show {
            display: block;
        }

        .login-footer {
            margin-top: var(--spacing-lg);
            text-align: center;
        }

        .login-footer p {
            margin: 0;
        }

        .login-footer a {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .login-footer a:hover {
            color: var(--color-gold-light);
        }

        .login-inquiry-link {
            padding: var(--spacing-sm);
            background-color: var(--bg-medium);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            margin-bottom: var(--spacing-sm);
        }

        .login-inquiry-link a {
            color: var(--color-gold-light);
        }

        .login-home-link {
            margin-top: var(--spacing-sm);
        }
    </style>
</head>
<body>
    <!-- ヘッダー（テンプレートから生成） -->
    <div id="header-template" data-compact="true"></div>

    <!-- ナビゲーション（テンプレートから生成） -->
    <div id="nav-template" data-active="login"></div>

    <!-- メインコンテンツ -->
    <main class="main-content">
        <section class="login-section">
            <div class="login-container">
                <div class="login-header">
                    <h1 class="login-title">ログイン</h1>
                    <p class="login-description">
                        追悼メッセージの閲覧・投稿には<br>パスワードが必要です
                    </p>
                </div>

                <form id="loginForm" class="login-form">
                    <input type="hidden" name="redirect" value="<?php echo $redirect; ?>">

                    <div class="form-group">
                        <label>ログイン種別</label>
                        <div class="login-type-group">
                            <label class="login-type-label">
                                <input type="radio" name="userType" value="user" checked>
                                <span class="type-box">
                                    <span class="type-name">一般</span>
                                    <span class="type-desc">閲覧・投稿</span>
                                </span>
                            </label>
                            <label class="login-type-label">
                                <input type="radio" name="userType" value="admin">
                                <span class="type-box">
                                    <span class="type-name">管理者</span>
                                    <span class="type-desc">サイト管理</span>
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">パスワード</label>
                        <div class="password-input-wrapper">
                            <input type="password" id="password" name="password" required
                                   placeholder="パスワードを入力" autocomplete="current-password">
                            <button type="button" class="password-toggle-btn" id="passwordToggle" aria-label="パスワードを表示">
                                <svg class="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <svg class="eye-off-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                    <line x1="1" y1="1" x2="23" y2="23"></line>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="login-btn" id="loginBtn">ログイン</button>

                    <div id="loginError" class="login-error"></div>
                </form>

                <div class="login-footer">
                    <p class="login-inquiry-link">
                        <a href="inquiry.html">パスワードをお持ちでない方はこちら</a>
                    </p>
                </div>
            </div>
        </section>
    </main>

    <!-- フッター（テンプレートから生成） -->
    <div id="footer-template"></div>

    <script src="js/templates.js"></script>
    <script>
    (function() {
        'use strict';

        var form = document.getElementById('loginForm');
        var loginBtn = document.getElementById('loginBtn');
        var errorDiv = document.getElementById('loginError');
        var passwordInput = document.getElementById('password');
        var passwordToggle = document.getElementById('passwordToggle');
        var eyeIcon = passwordToggle.querySelector('.eye-icon');
        var eyeOffIcon = passwordToggle.querySelector('.eye-off-icon');

        // パスワード表示/非表示切り替え
        passwordToggle.addEventListener('click', function() {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordInput.classList.add('password-input');
                eyeIcon.style.display = 'none';
                eyeOffIcon.style.display = 'block';
                passwordToggle.setAttribute('aria-label', 'パスワードを隠す');
            } else {
                passwordInput.type = 'password';
                passwordInput.classList.remove('password-input');
                eyeIcon.style.display = 'block';
                eyeOffIcon.style.display = 'none';
                passwordToggle.setAttribute('aria-label', 'パスワードを表示');
            }
        });

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            var password = passwordInput.value.trim();
            if (!password) {
                showError('パスワードを入力してください');
                return;
            }

            var userType = document.querySelector('input[name="userType"]:checked').value;

            // ボタンを無効化
            loginBtn.disabled = true;
            loginBtn.textContent = 'ログイン中...';
            hideError();

            // 認証リクエスト
            fetch('api/auth.php?action=login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ password: password, userType: userType }),
                credentials: 'same-origin'
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data.success) {
                    // リダイレクト先を取得
                    var redirect = form.querySelector('input[name="redirect"]').value || 'gallery.php';
                    window.location.href = redirect;
                } else {
                    throw new Error(data.error || 'ログインに失敗しました');
                }
            })
            .catch(function(error) {
                showError(error.message);
                loginBtn.disabled = false;
                loginBtn.textContent = 'ログイン';
            });
        });

        function showError(message) {
            errorDiv.textContent = message;
            errorDiv.classList.add('show');
        }

        function hideError() {
            errorDiv.classList.remove('show');
        }

        // Enterキーでフォーム送信
        passwordInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                form.dispatchEvent(new Event('submit'));
            }
        });
    })();
    </script>
</body>
</html>
