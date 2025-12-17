<?php
/**
 * 追悼メッセージ一覧ページ（認証必須）
 */
require_once __DIR__ . '/api/auth-check.php';

$isAdmin = isAdmin();
$userType = getUserType();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="瀬田和久先生 追悼メッセージ一覧 - 大阪公立大学 大学院情報学研究科 教授">
    <meta name="robots" content="noindex, nofollow">
    <title>追悼メッセージ一覧 | 瀬田和久先生 追悼サイト</title>
    <link rel="icon" type="image/png" href="images/favicon.png">
    <link rel="stylesheet" href="css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+JP:wght@400;500&family=Noto+Sans+JP:wght@400;500&display=swap" rel="stylesheet">
</head>
<body data-user-type="<?php echo htmlspecialchars($userType); ?>">
    <!-- ヘッダー（テンプレートから生成） -->
    <div id="header-template" data-compact="true"></div>

    <!-- ナビゲーション（テンプレートから生成） -->
    <div id="nav-template" data-active="gallery"></div>

    <!-- メインコンテンツ -->
    <main class="main-content">
        <section class="gallery-section">
            <div class="container">
                <div class="page-header-with-logout">
                    <h1 class="page-title">追悼メッセージ一覧</h1>
                    <button type="button" class="logout-btn" id="logoutBtn">ログアウト</button>
                </div>

                <p class="gallery-intro">
                    皆様からお寄せいただいた，瀬田先生との思い出のお写真・動画をご紹介いたします．
                </p>

                <!-- 統合ギャラリー -->
                <div class="media-gallery" id="mediaGallery">
                    <?php if ($isAdmin): ?>
                    <div class="media-item admin-add-btn" id="addMediaBtn">
                        <span class="add-icon">+</span>
                        <span class="add-text">写真・動画を追加</span>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="gallery-info-box">
                    <p>
                        追悼メッセージ・お写真の投稿は<a href="messages.php">メッセージを送るページ</a>からお願いいたします．
                    </p>
                </div>

                <p class="gallery-note">
                    故人のプライバシーに配慮し，適切なお写真・動画のみ掲載いたします．
                </p>
            </div>
        </section>
    </main>

    <!-- ライトボックス -->
    <div id="lightbox" class="lightbox">
        <button class="lightbox-close" aria-label="閉じる">&times;</button>
        <div class="lightbox-content">
            <img id="lightboxImage" src="" alt="">
            <div id="lightboxCaption" class="lightbox-caption"></div>
        </div>
        <button class="lightbox-prev" aria-label="前へ">&#10094;</button>
        <button class="lightbox-next" aria-label="次へ">&#10095;</button>
    </div>

    <?php if ($isAdmin): ?>
    <!-- 管理者用アップロードモーダル -->
    <div id="adminUploadModal" class="modal-overlay">
        <div class="modal-content admin-upload-modal">
            <button type="button" class="modal-close-btn" id="closeUploadModal">&times;</button>
            <h3 class="modal-title" id="uploadModalTitle">写真・動画を追加</h3>

            <form id="adminUploadForm" class="admin-upload-form">
                <input type="hidden" id="uploadType" name="type" value="photo">

                <div class="form-group">
                    <label for="uploadFile">ファイルを選択</label>
                    <div class="file-input-wrapper">
                        <input type="file" id="uploadFile" name="file" accept="image/*,video/*">
                        <span class="file-input-text" id="uploadFileText">写真または動画を選択してください</span>
                    </div>
                </div>

                <div class="form-group" id="previewGroup" style="display: none;">
                    <label>プレビュー</label>
                    <div id="uploadPreview" class="upload-preview"></div>
                </div>

                <div class="form-group">
                    <label for="uploadAuthor">投稿者名</label>
                    <input type="text" id="uploadAuthor" name="author" placeholder="例：山田 太郎">
                </div>

                <div class="form-group">
                    <label for="uploadCaption">キャプション・メッセージ</label>
                    <textarea id="uploadCaption" name="caption" rows="4" placeholder="写真・動画の説明やメッセージを入力"></textarea>
                </div>

                <div class="form-actions">
                    <button type="button" class="cancel-btn" id="cancelUpload">キャンセル</button>
                    <button type="submit" class="submit-btn" id="submitUpload">追加する</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ローディングオーバーレイ -->
    <div id="uploadLoading" class="loading-overlay">
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <p class="loading-text">アップロード中...</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- フッター（テンプレートから生成） -->
    <div id="footer-template"></div>

    <script src="js/templates.js"></script>
    <script src="js/main.js"></script>
    <script>
    (function() {
        'use strict';

        var isAdmin = document.body.dataset.userType === 'admin';
        var allPhotos = [];

        // ログアウト処理
        document.getElementById('logoutBtn').addEventListener('click', function() {
            fetch('api/auth.php?action=logout', {
                method: 'POST',
                credentials: 'same-origin'
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data.success) {
                    window.location.href = 'index.html';
                }
            })
            .catch(function(error) {
                console.error('ログアウトエラー:', error);
                window.location.href = 'index.html';
            });
        });

        // ギャラリーデータを読み込んで表示
        function loadGalleryData() {
            fetch('api/gallery.php?action=list')
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data.success) {
                    renderMedia(data.photos || [], data.videos || []);
                }
            })
            .catch(function(error) {
                console.error('ギャラリー読み込みエラー:', error);
            });
        }

        // 写真と動画を統合して表示
        function renderMedia(photos, videos) {
            var gallery = document.getElementById('mediaGallery');
            var addBtn = document.getElementById('addMediaBtn');

            // 既存のアイテム（+ボタン以外）を削除
            var existingItems = gallery.querySelectorAll('.media-item:not(.admin-add-btn)');
            existingItems.forEach(function(item) {
                item.remove();
            });

            // 写真と動画を日時順にマージ
            var allMedia = [];

            photos.forEach(function(photo) {
                allMedia.push({
                    type: 'photo',
                    data: photo,
                    created_at: photo.created_at || '2000-01-01'
                });
            });

            videos.forEach(function(video) {
                allMedia.push({
                    type: 'video',
                    data: video,
                    created_at: video.created_at || '2000-01-01'
                });
            });

            // 日時順（新しい順）にソート
            allMedia.sort(function(a, b) {
                return new Date(b.created_at) - new Date(a.created_at);
            });

            // ライトボックス用に写真だけを保存
            allPhotos = photos;

            // メディアを描画
            allMedia.forEach(function(media, index) {
                var item = document.createElement('div');
                item.className = 'media-item';
                item.dataset.id = media.data.id;
                item.dataset.type = media.type;

                if (media.type === 'photo') {
                    item.classList.add('media-photo');

                    var img = document.createElement('img');
                    img.src = media.data.url;
                    img.alt = media.data.caption || '瀬田先生のお写真';
                    img.loading = 'lazy';
                    item.appendChild(img);

                    // 写真のインデックスを保存（ライトボックス用）
                    var photoIndex = allPhotos.findIndex(function(p) { return p.id === media.data.id; });
                    item.dataset.photoIndex = photoIndex;

                    item.addEventListener('click', function() {
                        openLightboxWithData(allPhotos, photoIndex);
                    });
                } else {
                    item.classList.add('media-video');

                    var videoEl = document.createElement('video');
                    videoEl.src = media.data.url;
                    videoEl.controls = true;
                    videoEl.preload = 'metadata';
                    item.appendChild(videoEl);
                }

                // オーバーレイ（投稿者名表示）
                if (media.data.author) {
                    var overlay = document.createElement('div');
                    overlay.className = 'media-item-overlay';
                    overlay.innerHTML = '<span class="media-author">' + escapeHtml(media.data.author) + '</span>';
                    if (media.type === 'video') {
                        overlay.innerHTML += '<span class="media-type-badge">動画</span>';
                    }
                    item.appendChild(overlay);
                } else if (media.type === 'video') {
                    var overlay = document.createElement('div');
                    overlay.className = 'media-item-overlay';
                    overlay.innerHTML = '<span class="media-type-badge">動画</span>';
                    item.appendChild(overlay);
                }

                if (addBtn) {
                    gallery.insertBefore(item, addBtn);
                } else {
                    gallery.appendChild(item);
                }
            });
        }

        // ライトボックスを開く
        function openLightboxWithData(photos, index) {
            var lightbox = document.getElementById('lightbox');
            var lightboxImage = document.getElementById('lightboxImage');
            var lightboxCaption = document.getElementById('lightboxCaption');

            if (!lightbox || photos.length === 0 || index < 0) return;

            var currentIndex = index;

            function showImage(idx) {
                var photo = photos[idx];
                lightboxImage.src = photo.url;
                var captionText = '';
                if (photo.author) captionText += photo.author;
                if (photo.caption) captionText += (captionText ? ' - ' : '') + photo.caption;
                lightboxCaption.textContent = captionText;
            }

            showImage(currentIndex);
            lightbox.classList.add('active');
            document.body.style.overflow = 'hidden';

            // ナビゲーション
            var prevBtn = lightbox.querySelector('.lightbox-prev');
            var nextBtn = lightbox.querySelector('.lightbox-next');
            var closeBtn = lightbox.querySelector('.lightbox-close');

            function navigate(dir) {
                currentIndex += dir;
                if (currentIndex < 0) currentIndex = photos.length - 1;
                if (currentIndex >= photos.length) currentIndex = 0;
                showImage(currentIndex);
            }

            function closeLightbox() {
                lightbox.classList.remove('active');
                document.body.style.overflow = '';
            }

            prevBtn.onclick = function() { navigate(-1); };
            nextBtn.onclick = function() { navigate(1); };
            closeBtn.onclick = closeLightbox;
            lightbox.onclick = function(e) {
                if (e.target === lightbox) closeLightbox();
            };
        }

        function escapeHtml(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // ギャラリーデータを読み込み
        loadGalleryData();

        // 管理者用機能
        if (isAdmin) {
            var modal = document.getElementById('adminUploadModal');
            var uploadForm = document.getElementById('adminUploadForm');
            var uploadLoading = document.getElementById('uploadLoading');
            var fileInput = document.getElementById('uploadFile');
            var fileText = document.getElementById('uploadFileText');
            var previewGroup = document.getElementById('previewGroup');
            var previewDiv = document.getElementById('uploadPreview');
            var uploadType = document.getElementById('uploadType');

            // メディア追加ボタン
            var addMediaBtn = document.getElementById('addMediaBtn');
            if (addMediaBtn) {
                addMediaBtn.addEventListener('click', function() {
                    fileInput.accept = 'image/*,video/*';
                    resetForm();
                    modal.classList.add('active');
                });
            }

            // モーダルを閉じる
            document.getElementById('closeUploadModal').addEventListener('click', closeModal);
            document.getElementById('cancelUpload').addEventListener('click', closeModal);
            modal.addEventListener('click', function(e) {
                if (e.target === modal) closeModal();
            });

            function closeModal() {
                modal.classList.remove('active');
                resetForm();
            }

            function resetForm() {
                uploadForm.reset();
                fileText.textContent = '写真または動画を選択してください';
                previewGroup.style.display = 'none';
                previewDiv.innerHTML = '';
                uploadType.value = 'photo';
            }

            // ファイル選択時のプレビュー
            fileInput.addEventListener('change', function() {
                var file = fileInput.files[0];
                if (!file) {
                    fileText.textContent = '写真または動画を選択してください';
                    previewGroup.style.display = 'none';
                    uploadType.value = 'photo';
                    return;
                }

                fileText.textContent = file.name;
                previewDiv.innerHTML = '';

                if (file.type.startsWith('image/')) {
                    uploadType.value = 'photo';
                    var img = document.createElement('img');
                    img.className = 'preview-image';
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        img.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                    previewDiv.appendChild(img);
                    previewGroup.style.display = 'block';
                } else if (file.type.startsWith('video/')) {
                    uploadType.value = 'video';
                    var video = document.createElement('video');
                    video.className = 'preview-video';
                    video.controls = true;
                    video.src = URL.createObjectURL(file);
                    previewDiv.appendChild(video);
                    previewGroup.style.display = 'block';
                }
            });

            // アップロード処理
            uploadForm.addEventListener('submit', function(e) {
                e.preventDefault();

                var file = fileInput.files[0];
                if (!file) {
                    alert('ファイルを選択してください');
                    return;
                }

                uploadLoading.classList.add('active');

                var formData = new FormData();
                formData.append('action', 'upload');
                formData.append('type', uploadType.value);
                formData.append('file', file);
                formData.append('author', document.getElementById('uploadAuthor').value);
                formData.append('caption', document.getElementById('uploadCaption').value);

                fetch('api/gallery.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                })
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    uploadLoading.classList.remove('active');
                    if (data.success) {
                        closeModal();
                        loadGalleryData();
                    } else {
                        alert('エラー: ' + (data.error || 'アップロードに失敗しました'));
                    }
                })
                .catch(function(error) {
                    uploadLoading.classList.remove('active');
                    alert('エラー: アップロードに失敗しました');
                    console.error(error);
                });
            });
        }
    })();
    </script>
</body>
</html>
