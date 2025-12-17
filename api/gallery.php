<?php
/**
 * メッセージ API
 * 追悼メッセージの追加・編集・削除を処理
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

// データファイルのパス
$uploadDir = dirname(__DIR__) . '/uploads';
$dataFile = $uploadDir . '/gallery.json';
$mediaDir = $uploadDir . '/gallery';

// ディレクトリが存在しない場合は作成
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}
if (!file_exists($mediaDir)) {
    mkdir($mediaDir, 0755, true);
}

/**
 * メッセージデータを読み込む
 */
function loadMessages($dataFile) {
    if (!file_exists($dataFile)) {
        return [];
    }
    $content = file_get_contents($dataFile);
    $data = json_decode($content, true);
    return $data['messages'] ?? [];
}

/**
 * メッセージデータを保存する
 */
function saveMessages($dataFile, $messages) {
    return file_put_contents($dataFile, json_encode(['messages' => $messages], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

/**
 * 認証チェック
 */
function isAuthenticated() {
    return isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
}

/**
 * 管理者チェック
 */
function isAdmin() {
    return isAuthenticated() && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

/**
 * 一意のIDを生成
 */
function generateId() {
    return uniqid() . '_' . bin2hex(random_bytes(4));
}

/**
 * ファイル名をサニタイズ
 */
function sanitizeFilename($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return generateId() . '.' . $ext;
}

/**
 * GitHubにコミット＆プッシュ
 */
function gitCommitAndPush($config, $filePath, $message) {
    if (empty($config['git_auto_push']) || $config['git_auto_push'] !== true) {
        return ['success' => false, 'skipped' => true];
    }

    $repoDir = dirname(__DIR__);
    $output = [];
    $returnCode = 0;

    $commands = [
        "cd " . escapeshellarg($repoDir) . " && git add " . escapeshellarg($filePath),
        "cd " . escapeshellarg($repoDir) . " && git add uploads/gallery.json",
        "cd " . escapeshellarg($repoDir) . " && git commit -m " . escapeshellarg($message),
        "cd " . escapeshellarg($repoDir) . " && git push"
    ];

    foreach ($commands as $cmd) {
        exec($cmd . " 2>&1", $output, $returnCode);
        if ($returnCode !== 0 && strpos($cmd, 'git push') !== false) {
            return [
                'success' => false,
                'error' => implode("\n", $output),
                'code' => $returnCode
            ];
        }
    }

    return ['success' => true, 'output' => implode("\n", $output)];
}

// アクションを取得
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

try {
    switch ($action) {
        case 'list':
            if (!isAuthenticated()) {
                throw new Exception('認証が必要です');
            }
            echo json_encode([
                'success' => true,
                'messages' => loadMessages($dataFile)
            ]);
            break;

        case 'add_message':
            if (!isAdmin()) {
                throw new Exception('管理者権限が必要です');
            }
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Method not allowed');
            }

            $author = trim($_POST['author'] ?? '');
            $affiliation = trim($_POST['affiliation'] ?? '');
            $relationship = trim($_POST['relationship'] ?? '');
            $content = trim($_POST['content'] ?? '');

            $hasFiles = isset($_FILES['files']) && !empty($_FILES['files']['name'][0]);
            $messages = loadMessages($dataFile);
            $uploadedMedia = [];

            // ファイルアップロード処理
            if ($hasFiles) {
                $files = $_FILES['files'];
                $fileCount = count($files['name']);

                for ($i = 0; $i < $fileCount; $i++) {
                    if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                        continue;
                    }

                    $tmpName = $files['tmp_name'][$i];
                    $originalName = $files['name'][$i];

                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mimeType = $finfo->file($tmpName);

                    $isPhoto = in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
                    $isVideo = in_array($mimeType, ['video/mp4', 'video/webm', 'video/quicktime', 'video/x-msvideo']);

                    if (!$isPhoto && !$isVideo) {
                        continue;
                    }

                    if ($files['size'][$i] > $config['max_file_size']) {
                        continue;
                    }

                    $newFilename = sanitizeFilename($originalName);
                    $subDir = $isPhoto ? 'photos' : 'videos';
                    $targetDir = $mediaDir . '/' . $subDir;

                    if (!file_exists($targetDir)) {
                        mkdir($targetDir, 0755, true);
                    }

                    $targetPath = $targetDir . '/' . $newFilename;

                    if (move_uploaded_file($tmpName, $targetPath)) {
                        $uploadedMedia[] = [
                            'type' => $isPhoto ? 'photo' : 'video',
                            'url' => 'uploads/gallery/' . $subDir . '/' . $newFilename,
                            'filename' => $newFilename
                        ];
                    }
                }
            }

            $newMessage = [
                'id' => generateId(),
                'author' => $author,
                'affiliation' => $affiliation,
                'relationship' => $relationship,
                'content' => $content,
                'media' => $uploadedMedia,
                'created_at' => date('Y-m-d H:i:s')
            ];

            array_unshift($messages, $newMessage);
            saveMessages($dataFile, $messages);

            $commitMsg = 'add: 追悼メッセージを追加';
            if (!empty($uploadedMedia)) {
                $commitMsg .= '（メディア' . count($uploadedMedia) . '件）';
            }
            $gitResult = gitCommitAndPush($config, 'uploads/gallery.json', $commitMsg);

            echo json_encode([
                'success' => true,
                'message' => 'メッセージを追加しました',
                'item' => $newMessage,
                'git' => $gitResult
            ]);
            break;

        case 'update_message':
            if (!isAdmin()) {
                throw new Exception('管理者権限が必要です');
            }
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Method not allowed');
            }

            $id = $_POST['id'] ?? '';
            if (empty($id)) {
                throw new Exception('IDが指定されていません');
            }

            $author = trim($_POST['author'] ?? '');
            $affiliation = trim($_POST['affiliation'] ?? '');
            $relationship = trim($_POST['relationship'] ?? '');
            $content = trim($_POST['content'] ?? '');

            $messages = loadMessages($dataFile);
            $found = false;

            foreach ($messages as $index => $msg) {
                if ($msg['id'] === $id) {
                    $messages[$index]['author'] = $author;
                    $messages[$index]['affiliation'] = $affiliation;
                    $messages[$index]['relationship'] = $relationship;
                    $messages[$index]['content'] = $content;
                    $messages[$index]['updated_at'] = date('Y-m-d H:i:s');
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                throw new Exception('メッセージが見つかりません');
            }

            saveMessages($dataFile, $messages);
            $gitResult = gitCommitAndPush($config, 'uploads/gallery.json', 'update: 追悼メッセージを編集');

            echo json_encode([
                'success' => true,
                'message' => '更新しました',
                'git' => $gitResult
            ]);
            break;

        case 'delete_message':
            if (!isAdmin()) {
                throw new Exception('管理者権限が必要です');
            }
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Method not allowed');
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? $_POST['id'] ?? '';

            if (empty($id)) {
                throw new Exception('IDが指定されていません');
            }

            $messages = loadMessages($dataFile);
            $found = false;

            foreach ($messages as $index => $msg) {
                if ($msg['id'] === $id) {
                    // メディアファイルも削除
                    if (!empty($msg['media'])) {
                        foreach ($msg['media'] as $media) {
                            $filePath = dirname(__DIR__) . '/' . $media['url'];
                            if (file_exists($filePath)) {
                                unlink($filePath);
                            }
                        }
                    }
                    array_splice($messages, $index, 1);
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                throw new Exception('メッセージが見つかりません');
            }

            saveMessages($dataFile, $messages);
            $gitResult = gitCommitAndPush($config, 'uploads/gallery.json', 'remove: 追悼メッセージを削除');

            echo json_encode([
                'success' => true,
                'message' => '削除しました',
                'git' => $gitResult
            ]);
            break;

        default:
            throw new Exception('不明なアクションです');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
