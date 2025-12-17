<?php
/**
 * ギャラリー API
 * 写真・動画のアップロードと一覧取得を処理
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

// ギャラリーデータファイルのパス
$dataDir = dirname(__DIR__) . '/data';
$galleryFile = $dataDir . '/gallery.json';
$uploadDir = dirname(__DIR__) . '/uploads/gallery';

// ディレクトリが存在しない場合は作成
if (!file_exists($dataDir)) {
    mkdir($dataDir, 0755, true);
}
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

/**
 * ギャラリーデータを読み込む
 */
function loadGalleryData($galleryFile) {
    if (!file_exists($galleryFile)) {
        return ['photos' => [], 'videos' => [], 'messages' => []];
    }
    $content = file_get_contents($galleryFile);
    $data = json_decode($content, true);
    if (!$data) {
        return ['photos' => [], 'videos' => [], 'messages' => []];
    }
    // messagesキーがない場合は追加
    if (!isset($data['messages'])) {
        $data['messages'] = [];
    }
    return $data;
}

/**
 * ギャラリーデータを保存する
 */
function saveGalleryData($galleryFile, $data) {
    return file_put_contents($galleryFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
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
    // 拡張子を取得
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    // 新しいファイル名を生成
    return generateId() . '.' . $ext;
}

/**
 * GitHubにコミット＆プッシュ
 */
function gitCommitAndPush($config, $filePath, $message) {
    // Git自動プッシュが無効の場合はスキップ
    if (empty($config['git_auto_push']) || $config['git_auto_push'] !== true) {
        return ['success' => false, 'skipped' => true];
    }

    $repoDir = dirname(__DIR__);
    $output = [];
    $returnCode = 0;

    // 作業ディレクトリを変更してgitコマンドを実行
    $commands = [
        "cd " . escapeshellarg($repoDir) . " && git add " . escapeshellarg($filePath),
        "cd " . escapeshellarg($repoDir) . " && git add data/gallery.json",
        "cd " . escapeshellarg($repoDir) . " && git commit -m " . escapeshellarg($message),
        "cd " . escapeshellarg($repoDir) . " && git push"
    ];

    foreach ($commands as $cmd) {
        exec($cmd . " 2>&1", $output, $returnCode);
        // commitは変更がない場合に失敗するので、pushまで続行
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
            // 認証チェック
            if (!isAuthenticated()) {
                throw new Exception('認証が必要です');
            }

            $data = loadGalleryData($galleryFile);

            echo json_encode([
                'success' => true,
                'photos' => $data['photos'],
                'videos' => $data['videos'],
                'messages' => $data['messages']
            ]);
            break;

        case 'upload':
            // 管理者のみアップロード可能
            if (!isAdmin()) {
                throw new Exception('管理者権限が必要です');
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Method not allowed');
            }

            // ファイルチェック
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('ファイルのアップロードに失敗しました');
            }

            $file = $_FILES['file'];
            $type = $_POST['type'] ?? 'photo';
            $author = trim($_POST['author'] ?? '');
            $caption = trim($_POST['caption'] ?? '');

            // MIMEタイプをチェック
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);

            $allowedTypes = [];
            if ($type === 'photo') {
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            } else {
                $allowedTypes = ['video/mp4', 'video/webm', 'video/quicktime', 'video/x-msvideo'];
            }

            if (!in_array($mimeType, $allowedTypes)) {
                throw new Exception('許可されていないファイル形式です');
            }

            // ファイルサイズチェック
            $maxSize = $config['max_file_size'];
            if ($file['size'] > $maxSize) {
                throw new Exception('ファイルサイズが大きすぎます');
            }

            // ファイルを保存
            $newFilename = sanitizeFilename($file['name']);
            $subDir = $type === 'photo' ? 'photos' : 'videos';
            $targetDir = $uploadDir . '/' . $subDir;

            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            $targetPath = $targetDir . '/' . $newFilename;

            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                throw new Exception('ファイルの保存に失敗しました');
            }

            // ギャラリーデータに追加
            $data = loadGalleryData($galleryFile);

            $newItem = [
                'id' => generateId(),
                'filename' => $newFilename,
                'url' => 'uploads/gallery/' . $subDir . '/' . $newFilename,
                'author' => $author,
                'caption' => $caption,
                'created_at' => date('Y-m-d H:i:s')
            ];

            if ($type === 'photo') {
                array_unshift($data['photos'], $newItem);
            } else {
                array_unshift($data['videos'], $newItem);
            }

            saveGalleryData($galleryFile, $data);

            // GitHubに自動コミット＆プッシュ
            $gitResult = gitCommitAndPush(
                $config,
                'uploads/gallery/' . $subDir . '/' . $newFilename,
                'add: ギャラリーに' . ($type === 'photo' ? '写真' : '動画') . 'を追加'
            );

            echo json_encode([
                'success' => true,
                'message' => 'アップロードしました',
                'item' => $newItem,
                'git' => $gitResult
            ]);
            break;

        case 'add_message':
            // 管理者のみメッセージ追加可能
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

            // ファイルがアップロードされたかチェック
            $hasFiles = isset($_FILES['files']) && !empty($_FILES['files']['name'][0]);

            if (empty($content) && !$hasFiles) {
                throw new Exception('メッセージまたは写真・動画を入力してください');
            }

            $data = loadGalleryData($galleryFile);
            $uploadedMedia = [];
            $gitFiles = [];

            // ファイルがある場合はアップロード処理
            if ($hasFiles) {
                $files = $_FILES['files'];
                $fileCount = count($files['name']);

                for ($i = 0; $i < $fileCount; $i++) {
                    if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                        continue;
                    }

                    $tmpName = $files['tmp_name'][$i];
                    $originalName = $files['name'][$i];

                    // MIMEタイプをチェック
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mimeType = $finfo->file($tmpName);

                    $isPhoto = in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
                    $isVideo = in_array($mimeType, ['video/mp4', 'video/webm', 'video/quicktime', 'video/x-msvideo']);

                    if (!$isPhoto && !$isVideo) {
                        continue; // 許可されていない形式はスキップ
                    }

                    // ファイルサイズチェック
                    if ($files['size'][$i] > $config['max_file_size']) {
                        continue;
                    }

                    // ファイルを保存
                    $newFilename = sanitizeFilename($originalName);
                    $subDir = $isPhoto ? 'photos' : 'videos';
                    $targetDir = $uploadDir . '/' . $subDir;

                    if (!file_exists($targetDir)) {
                        mkdir($targetDir, 0755, true);
                    }

                    $targetPath = $targetDir . '/' . $newFilename;

                    if (move_uploaded_file($tmpName, $targetPath)) {
                        $mediaUrl = 'uploads/gallery/' . $subDir . '/' . $newFilename;
                        $uploadedMedia[] = [
                            'type' => $isPhoto ? 'photo' : 'video',
                            'url' => $mediaUrl,
                            'filename' => $newFilename
                        ];
                        $gitFiles[] = $mediaUrl;
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

            array_unshift($data['messages'], $newMessage);
            saveGalleryData($galleryFile, $data);

            // GitHubに自動コミット＆プッシュ
            $commitMsg = 'add: 追悼メッセージを追加';
            if (!empty($uploadedMedia)) {
                $commitMsg .= '（写真・動画' . count($uploadedMedia) . '件）';
            }
            $gitResult = gitCommitAndPush(
                $config,
                'data/gallery.json',
                $commitMsg
            );

            echo json_encode([
                'success' => true,
                'message' => 'メッセージを追加しました',
                'item' => $newMessage,
                'git' => $gitResult
            ]);
            break;

        case 'update_message':
            // 管理者のみメッセージ編集可能
            if (!isAdmin()) {
                throw new Exception('管理者権限が必要です');
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Method not allowed');
            }

            $id = $_POST['id'] ?? '';
            $author = trim($_POST['author'] ?? '');
            $affiliation = trim($_POST['affiliation'] ?? '');
            $relationship = trim($_POST['relationship'] ?? '');
            $content = trim($_POST['content'] ?? '');

            if (empty($id)) {
                throw new Exception('IDが指定されていません');
            }

            if (empty($content)) {
                throw new Exception('メッセージ内容を入力してください');
            }

            $data = loadGalleryData($galleryFile);

            // メッセージを検索して更新
            $found = false;
            foreach ($data['messages'] as $index => $msg) {
                if ($msg['id'] === $id) {
                    $data['messages'][$index]['author'] = $author;
                    $data['messages'][$index]['affiliation'] = $affiliation;
                    $data['messages'][$index]['relationship'] = $relationship;
                    $data['messages'][$index]['content'] = $content;
                    $data['messages'][$index]['updated_at'] = date('Y-m-d H:i:s');
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                throw new Exception('メッセージが見つかりません');
            }

            saveGalleryData($galleryFile, $data);

            // GitHubに自動コミット＆プッシュ
            $gitResult = gitCommitAndPush(
                $config,
                'data/gallery.json',
                'update: 追悼メッセージを編集'
            );

            echo json_encode([
                'success' => true,
                'message' => '更新しました',
                'git' => $gitResult
            ]);
            break;

        case 'delete_message':
            // 管理者のみメッセージ削除可能
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

            $data = loadGalleryData($galleryFile);

            // メッセージを検索して削除
            $found = false;
            foreach ($data['messages'] as $index => $msg) {
                if ($msg['id'] === $id) {
                    array_splice($data['messages'], $index, 1);
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                throw new Exception('メッセージが見つかりません');
            }

            saveGalleryData($galleryFile, $data);

            // GitHubに自動コミット＆プッシュ
            $gitResult = gitCommitAndPush(
                $config,
                'data/gallery.json',
                'remove: 追悼メッセージを削除'
            );

            echo json_encode([
                'success' => true,
                'message' => '削除しました',
                'git' => $gitResult
            ]);
            break;

        case 'delete':
            // 管理者のみ削除可能
            if (!isAdmin()) {
                throw new Exception('管理者権限が必要です');
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Method not allowed');
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? $_POST['id'] ?? '';
            $type = $input['type'] ?? $_POST['type'] ?? 'photo';

            if (empty($id)) {
                throw new Exception('IDが指定されていません');
            }

            $data = loadGalleryData($galleryFile);
            $key = $type === 'photo' ? 'photos' : 'videos';

            // アイテムを検索して削除
            $found = false;
            foreach ($data[$key] as $index => $item) {
                if ($item['id'] === $id) {
                    // ファイルを削除
                    $filePath = dirname(__DIR__) . '/' . $item['url'];
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                    // データから削除
                    array_splice($data[$key], $index, 1);
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                throw new Exception('アイテムが見つかりません');
            }

            saveGalleryData($galleryFile, $data);

            // GitHubに自動コミット＆プッシュ（削除）
            $gitResult = gitCommitAndPush(
                $config,
                'data/gallery.json',
                'remove: ギャラリーから' . ($type === 'photo' ? '写真' : '動画') . 'を削除'
            );

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
