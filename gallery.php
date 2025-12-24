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

                <!-- 追悼メッセージ一覧 -->
                <div class="messages-section">
                    <div class="messages-controls">
                        <button type="button" class="expand-all-btn" id="expandAllBtn">すべてのメッセージの全文を開く</button>
                    </div>
                    <div class="messages-list" id="messagesList">
                        <!-- メッセージはJavaScriptで動的に生成 -->
                    </div>
                </div>

                <div class="gallery-info-box">
                    <p>
                        追悼メッセージ・お写真の投稿は<a href="messages.php">メッセージを送るページ</a>からお願いいたします．
                    </p>
                </div>

                <!-- 写真ギャラリー -->
                <?php
                $photosDir = __DIR__ . '/images/photos/';
                $photoFiles = [];
                if (is_dir($photosDir)) {
                    $files = scandir($photosDir);
                    foreach ($files as $file) {
                        if ($file === '.' || $file === '..' || $file === '.gitkeep') continue;
                        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm', 'mov'])) {
                            $photoFiles[] = $file;
                        }
                    }
                }
                if (!empty($photoFiles)):
                ?>
                <div class="photo-gallery-section">
                    <h2 class="photo-gallery-title">写真・動画 from 研究室</h2>
                    <div class="photo-gallery-grid">
                        <?php foreach ($photoFiles as $file):
                            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                            $isVideo = in_array($ext, ['mp4', 'webm', 'mov']);
                        ?>
                        <?php if ($isVideo): ?>
                        <div class="photo-gallery-item photo-gallery-video">
                            <video src="images/photos/<?php echo htmlspecialchars($file); ?>" controls preload="metadata"></video>
                        </div>
                        <?php else: ?>
                        <div class="photo-gallery-item photo-gallery-photo" data-src="images/photos/<?php echo htmlspecialchars($file); ?>">
                            <img src="images/photos/<?php echo htmlspecialchars($file); ?>" alt="写真" loading="lazy">
                        </div>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <p class="gallery-note">
                    故人のプライバシーに配慮し，適切なメッセージ・お写真・動画のみ掲載いたします．
                </p>
            </div>
        </section>
    </main>

    <?php if ($isAdmin): ?>
    <!-- 管理者用メッセージ追加モーダル -->
    <div id="messageModal" class="modal-overlay">
        <div class="modal-content message-modal">
            <button type="button" class="modal-close-btn" id="closeMessageModal">&times;</button>
            <h3 class="modal-title" id="messageModalTitle">追悼メッセージを追加</h3>

            <form id="adminMessageForm" class="message-form">
                <input type="hidden" id="messageId" name="id" value="">

                <div class="form-group">
                    <label for="msgAuthor">お名前 <span class="required">*</span></label>
                    <input type="text" id="msgAuthor" name="author" placeholder="例：山田 太郎">
                </div>

                <div class="form-group">
                    <label for="msgAffiliation">ご所属</label>
                    <input type="text" id="msgAffiliation" name="affiliation" placeholder="例：○○大学">
                </div>

                <div class="form-group">
                    <label for="msgRelationship">瀬田先生との関係</label>
                    <input type="text" id="msgRelationship" name="relationship" placeholder="例：元学生，共同研究者 など">
                </div>

                <div class="form-group">
                    <label for="msgCategory">カテゴリ <span class="required">*</span></label>
                    <select id="msgCategory" name="category">
                        <option value="">選択してください</option>
                        <option value="disciple">弟子（研究者）</option>
                        <option value="obog">OBOG</option>
                        <option value="related">関係者</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="msgContent">メッセージ</label>
                    <textarea id="msgContent" name="content" rows="6" placeholder="追悼メッセージを入力してください"></textarea>
                    <p class="field-note">簡易なMarkdown記法（Hタグ・アンカー・バレットリスト）に対応しています</p>
                </div>

                <div class="form-group">
                    <label for="msgFiles">写真・動画（複数選択可）</label>
                    <div class="file-input-wrapper">
                        <input type="file" id="msgFiles" name="files" accept="image/*,video/*" multiple>
                        <span class="file-input-text" id="msgFilesText">ファイルを選択してください</span>
                    </div>
                </div>

                <div class="form-group" id="msgPreviewGroup" style="display: none;">
                    <label>新規アップロード</label>
                    <div id="msgPreview" class="upload-preview-grid"></div>
                </div>

                <div class="form-group" id="existingMediaGroup" style="display: none;">
                    <label>登録済みメディア</label>
                    <div id="existingMediaPreview" class="upload-preview-grid"></div>
                </div>

                <div class="form-group" id="deletedMediaGroup" style="display: none;">
                    <label>削除予定（クリックで復元）</label>
                    <div id="deletedMediaPreview" class="upload-preview-grid deleted-media"></div>
                </div>

                <p class="form-note">※ メッセージのみ，写真・動画のみ，または両方を追加できます</p>

                <div class="form-actions">
                    <button type="button" class="cancel-btn" id="cancelMessage">キャンセル</button>
                    <button type="submit" class="submit-btn" id="submitMessage">追加する</button>
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

    <!-- 画像・動画拡大モーダル -->
    <div id="imageLightbox" class="lightbox">
        <button type="button" class="lightbox-close" aria-label="閉じる">&times;</button>
        <button type="button" class="lightbox-prev" aria-label="前へ">&#10094;</button>
        <button type="button" class="lightbox-next" aria-label="次へ">&#10095;</button>
        <div class="lightbox-content">
            <img id="lightboxImage" src="" alt="拡大画像">
            <video id="lightboxVideo" src="" controls style="display: none;"></video>
        </div>
        <div class="lightbox-counter" id="lightboxCounter"></div>
    </div>

    <!-- フッター（テンプレートから生成） -->
    <div id="footer-template"></div>

    <script src="js/templates.js"></script>
    <script src="js/main.js"></script>
    <!-- Markdown parser -->
    <script src="js/marked.min.js"></script>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-E6Z2CM1DGQ"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-E6Z2CM1DGQ');
    </script>
    <script>
    (function() {
        'use strict';

        var isAdmin = document.body.dataset.userType === 'admin';

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
                    renderMessages(data.messages || []);
                }
            })
            .catch(function(error) {
                console.error('ギャラリー読み込みエラー:', error);
            });
        }

        // カテゴリ定義
        var categories = [
            { key: 'disciple', label: '弟子（研究者）' },
            { key: 'obog', label: 'OBOG' },
            { key: 'related', label: '関係者' }
        ];

        // メッセージを表示
        function renderMessages(messages) {
            var list = document.getElementById('messagesList');
            list.innerHTML = '';

            if (messages.length === 0) {
                list.innerHTML = '<p class="no-messages">まだメッセージがありません</p>';
                return;
            }

            // カテゴリ別にメッセージを分類
            var categorizedMessages = {
                disciple: [],
                obog: [],
                related: [],
                uncategorized: []
            };

            messages.forEach(function(msg) {
                var cat = msg.category || '';
                if (categorizedMessages[cat]) {
                    categorizedMessages[cat].push(msg);
                } else {
                    categorizedMessages.uncategorized.push(msg);
                }
            });

            // カテゴリ順に表示（管理者は空のカテゴリも表示）
            categories.forEach(function(category) {
                var categoryMessages = categorizedMessages[category.key];

                // 管理者でない場合、空のカテゴリはスキップ
                if (!isAdmin && categoryMessages.length === 0) return;

                // カテゴリヘッダー
                var categoryHeader = document.createElement('div');
                categoryHeader.className = 'category-header';

                var headerHtml = '<h2 class="category-title">' + category.label + '</h2>';
                if (isAdmin) {
                    headerHtml += '<button type="button" class="category-add-btn" data-category="' + category.key + '">' +
                                  '<span class="add-icon">+</span> 追加</button>';
                }
                categoryHeader.innerHTML = headerHtml;
                list.appendChild(categoryHeader);

                // 管理者用の追加ボタンにイベントリスナーを設定
                if (isAdmin) {
                    var addBtn = categoryHeader.querySelector('.category-add-btn');
                    addBtn.addEventListener('click', function() {
                        openMessageModal(null, category.key);
                    });
                }

                // カテゴリ内のメッセージを表示
                if (categoryMessages.length === 0) {
                    var noMsg = document.createElement('p');
                    noMsg.className = 'no-messages-in-category';
                    noMsg.textContent = 'このカテゴリにはまだメッセージがありません';
                    list.appendChild(noMsg);
                } else {
                    categoryMessages.forEach(function(msg) {
                        renderMessageCard(msg, list);
                    });
                }
            });

            // 未分類のメッセージがある場合（既存データの互換性）
            if (categorizedMessages.uncategorized.length > 0) {
                var categoryHeader = document.createElement('div');
                categoryHeader.className = 'category-header';
                categoryHeader.innerHTML = '<h2 class="category-title">その他</h2>';
                list.appendChild(categoryHeader);

                categorizedMessages.uncategorized.forEach(function(msg) {
                    renderMessageCard(msg, list);
                });
            }
        }

        // 個別のメッセージカードをレンダリング
        function renderMessageCard(msg, list) {
                var card = document.createElement('div');
                card.className = 'message-card';
                card.dataset.id = msg.id;

                var html = '';

                // 1. メディア（写真・動画）を一番上に
                if (msg.media && msg.media.length > 0) {
                    html += '<div class="message-media">';
                    msg.media.forEach(function(media) {
                        if (media.type === 'photo') {
                            html += '<div class="message-media-item message-photo">';
                            html += '<img src="' + escapeHtml(media.url) + '" alt="添付写真" loading="lazy">';
                            html += '</div>';
                        } else if (media.type === 'video') {
                            html += '<div class="message-media-item message-video">';
                            html += '<video src="' + escapeHtml(media.url) + '" controls preload="metadata"></video>';
                            html += '</div>';
                        }
                    });
                    html += '</div>';
                }

                // 2. メッセージ内容（3行以上は折りたたみ）- マークダウン形式で表示
                if (msg.content) {
                    html += '<div class="message-content-wrapper">';
                    html += '<div class="message-content collapsed">' + parseMarkdown(msg.content) + '</div>';
                    html += '<button type="button" class="message-toggle">全文を見る</button>';
                    html += '</div>';
                }

                // 3. メタ情報（名前・所属・関係）を一番下に
                var metaParts = [];
                if (msg.author) metaParts.push(escapeHtml(msg.author));
                if (msg.affiliation) metaParts.push(escapeHtml(msg.affiliation));
                if (msg.relationship) metaParts.push(escapeHtml(msg.relationship));
                if (metaParts.length > 0) {
                    html += '<div class="message-meta">' + metaParts.join(' / ') + '</div>';
                }

                card.innerHTML = html;

                // 管理者用の編集・削除ボタン
                if (isAdmin) {
                    var actions = document.createElement('div');
                    actions.className = 'message-actions';
                    actions.innerHTML = '<button type="button" class="edit-btn" data-id="' + msg.id + '">編集</button>' +
                                       '<button type="button" class="delete-btn" data-id="' + msg.id + '">削除</button>';
                    card.appendChild(actions);

                    // 編集ボタン
                    actions.querySelector('.edit-btn').addEventListener('click', function() {
                        openMessageModal(msg);
                    });

                    // 削除ボタン
                    actions.querySelector('.delete-btn').addEventListener('click', function() {
                        if (confirm('このメッセージを削除しますか？')) {
                            deleteMessage(msg.id);
                        }
                    });
                }

                list.appendChild(card);

                // 画像クリックでライトボックス表示（そのメッセージのメディアのみ）
                if (msg.media && msg.media.length > 0) {
                    card.querySelectorAll('.message-photo img').forEach(function(img) {
                        img.addEventListener('click', function() {
                            openMessageLightbox(msg.media, img.src);
                        });
                    });
                    card.querySelectorAll('.message-video video').forEach(function(video) {
                        video.addEventListener('click', function(e) {
                            e.preventDefault();
                            openMessageLightbox(msg.media, video.src);
                        });
                    });
                }

                // メッセージ折りたたみ処理
                var contentWrapper = card.querySelector('.message-content-wrapper');
                if (contentWrapper) {
                    var content = contentWrapper.querySelector('.message-content');
                    var toggle = contentWrapper.querySelector('.message-toggle');

                    // 3行以下の場合はトグルボタンを非表示
                    setTimeout(function() {
                        if (content.scrollHeight <= content.clientHeight + 5) {
                            toggle.style.display = 'none';
                            content.classList.remove('collapsed');
                        }
                    }, 10);

                    toggle.addEventListener('click', function() {
                        if (content.classList.contains('collapsed')) {
                            content.classList.remove('collapsed');
                            toggle.textContent = '閉じる';
                        } else {
                            content.classList.add('collapsed');
                            toggle.textContent = '全文を見る';
                        }
                    });
                }
        }

        // ライトボックス制御（スライドナビゲーション対応）
        var lightbox = document.getElementById('imageLightbox');
        var lightboxImage = document.getElementById('lightboxImage');
        var lightboxVideo = document.getElementById('lightboxVideo');
        var lightboxClose = document.querySelector('.lightbox-close');
        var lightboxPrev = document.querySelector('.lightbox-prev');
        var lightboxNext = document.querySelector('.lightbox-next');
        var lightboxCounter = document.getElementById('lightboxCounter');

        // ギャラリーアイテムの配列と現在のインデックス
        var galleryItems = [];
        var currentIndex = 0;

        // ギャラリーアイテムを収集
        function collectGalleryItems() {
            galleryItems = [];
            // 研究室の写真・動画を収集
            document.querySelectorAll('.photo-gallery-item').forEach(function(item) {
                if (item.classList.contains('photo-gallery-video')) {
                    var video = item.querySelector('video');
                    if (video) {
                        galleryItems.push({ type: 'video', src: video.src });
                    }
                } else if (item.classList.contains('photo-gallery-photo')) {
                    var img = item.querySelector('img');
                    if (img) {
                        galleryItems.push({ type: 'image', src: item.dataset.src || img.src });
                    }
                }
            });
        }

        function showItem(index) {
            if (galleryItems.length === 0) return;

            // インデックスをラップ
            if (index < 0) index = galleryItems.length - 1;
            if (index >= galleryItems.length) index = 0;
            currentIndex = index;

            var item = galleryItems[currentIndex];

            // 動画を停止
            lightboxVideo.pause();
            lightboxVideo.src = '';

            if (item.type === 'video') {
                lightboxImage.style.display = 'none';
                lightboxVideo.style.display = 'block';
                lightboxVideo.src = item.src;
            } else {
                lightboxVideo.style.display = 'none';
                lightboxImage.style.display = 'block';
                lightboxImage.src = item.src;
            }

            // カウンター更新
            lightboxCounter.textContent = (currentIndex + 1) + ' / ' + galleryItems.length;
        }

        function openLightbox(src, type) {
            collectGalleryItems();

            // クリックしたアイテムのインデックスを探す
            currentIndex = 0;
            for (var i = 0; i < galleryItems.length; i++) {
                if (galleryItems[i].src === src) {
                    currentIndex = i;
                    break;
                }
            }

            lightbox.classList.add('active');
            document.body.style.overflow = 'hidden';
            showItem(currentIndex);
        }

        // メッセージ専用のライトボックスを開く
        function openMessageLightbox(mediaArray, clickedSrc) {
            // メッセージのメディアをgalleryItemsにセット
            galleryItems = [];
            mediaArray.forEach(function(media) {
                galleryItems.push({
                    type: media.type === 'photo' ? 'image' : 'video',
                    src: media.url
                });
            });

            // クリックしたアイテムのインデックスを探す
            currentIndex = 0;
            for (var i = 0; i < galleryItems.length; i++) {
                if (galleryItems[i].src === clickedSrc) {
                    currentIndex = i;
                    break;
                }
            }

            lightbox.classList.add('active');
            document.body.style.overflow = 'hidden';
            showItem(currentIndex);
        }

        function closeLightbox() {
            lightbox.classList.remove('active');
            lightboxImage.src = '';
            lightboxVideo.pause();
            lightboxVideo.src = '';
            lightboxVideo.style.display = 'none';
            lightboxImage.style.display = 'block';
            document.body.style.overflow = '';
        }

        function showPrev() {
            showItem(currentIndex - 1);
        }

        function showNext() {
            showItem(currentIndex + 1);
        }

        if (lightboxClose) {
            lightboxClose.addEventListener('click', closeLightbox);
        }

        if (lightboxPrev) {
            lightboxPrev.addEventListener('click', function(e) {
                e.stopPropagation();
                showPrev();
            });
        }

        if (lightboxNext) {
            lightboxNext.addEventListener('click', function(e) {
                e.stopPropagation();
                showNext();
            });
        }

        if (lightbox) {
            lightbox.addEventListener('click', function(e) {
                if (e.target === lightbox || e.target.classList.contains('lightbox-content')) {
                    closeLightbox();
                }
            });
        }

        // キーボードナビゲーション
        document.addEventListener('keydown', function(e) {
            if (!lightbox.classList.contains('active')) return;

            if (e.key === 'Escape') {
                closeLightbox();
            } else if (e.key === 'ArrowLeft') {
                showPrev();
            } else if (e.key === 'ArrowRight') {
                showNext();
            }
        });

        // タッチスワイプ対応
        var touchStartX = 0;
        var touchEndX = 0;

        lightbox.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });

        lightbox.addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].screenX;
            var diff = touchStartX - touchEndX;
            if (Math.abs(diff) > 50) {
                if (diff > 0) {
                    showNext(); // 左スワイプで次へ
                } else {
                    showPrev(); // 右スワイプで前へ
                }
            }
        }, { passive: true });

        // メッセージを削除
        function deleteMessage(id) {
            fetch('api/gallery.php?action=delete_message', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id }),
                credentials: 'same-origin'
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    loadGalleryData();
                }
            })
            .catch(function() {});
        }

        function escapeHtml(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // マークダウンパーサー（marked.js使用）
        function parseMarkdown(text) {
            if (!text) return '';

            // marked.jsの設定
            marked.setOptions({
                breaks: true,  // 改行を<br>に変換
                gfm: true      // GitHub Flavored Markdown
            });

            return marked.parse(text);
        }

        // ギャラリーデータを読み込み
        loadGalleryData();

        // 写真・動画ギャラリーのクリックイベント
        document.querySelectorAll('.photo-gallery-photo').forEach(function(item) {
            item.addEventListener('click', function() {
                var src = item.dataset.src || item.querySelector('img').src;
                openLightbox(src);
            });
        });

        document.querySelectorAll('.photo-gallery-video').forEach(function(item) {
            var video = item.querySelector('video');
            if (video) {
                // 動画アイテムにオーバーレイを追加（クリックでライトボックスを開く）
                var overlay = document.createElement('div');
                overlay.className = 'video-overlay';
                overlay.innerHTML = '<span class="play-icon">▶</span>';
                item.appendChild(overlay);

                overlay.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    openLightbox(video.src);
                });

                // controls属性を削除してグリッド内では再生させない
                video.removeAttribute('controls');
            }
        });

        // 「すべてのメッセージの全文を開く」ボタン
        var expandAllBtn = document.getElementById('expandAllBtn');
        var allExpanded = false;

        expandAllBtn.addEventListener('click', function() {
            var contents = document.querySelectorAll('.message-content');
            var toggles = document.querySelectorAll('.message-toggle');

            if (allExpanded) {
                // すべて閉じる
                contents.forEach(function(content) {
                    content.classList.add('collapsed');
                });
                toggles.forEach(function(toggle) {
                    if (toggle.style.display !== 'none') {
                        toggle.textContent = '全文を見る';
                    }
                });
                expandAllBtn.textContent = 'すべてのメッセージの全文を開く';
                allExpanded = false;
            } else {
                // すべて開く
                contents.forEach(function(content) {
                    content.classList.remove('collapsed');
                });
                toggles.forEach(function(toggle) {
                    if (toggle.style.display !== 'none') {
                        toggle.textContent = '閉じる';
                    }
                });
                expandAllBtn.textContent = 'すべてのメッセージを閉じる';
                allExpanded = true;
            }
        });

        // 管理者用機能
        if (isAdmin) {
            var uploadLoading = document.getElementById('uploadLoading');

            // === メッセージ管理機能 ===
            var msgModal = document.getElementById('messageModal');
            var msgForm = document.getElementById('adminMessageForm');
            var msgModalTitle = document.getElementById('messageModalTitle');
            var msgSubmitBtn = document.getElementById('submitMessage');
            var msgFilesInput = document.getElementById('msgFiles');
            var msgFilesText = document.getElementById('msgFilesText');
            var msgPreviewGroup = document.getElementById('msgPreviewGroup');
            var msgPreviewDiv = document.getElementById('msgPreview');
            var existingMediaGroup = document.getElementById('existingMediaGroup');
            var existingMediaPreview = document.getElementById('existingMediaPreview');
            var deletedMediaGroup = document.getElementById('deletedMediaGroup');
            var deletedMediaPreview = document.getElementById('deletedMediaPreview');

            // メディア管理用変数
            var currentExistingMedia = [];  // 現在の既存メディア
            var deletedMedia = [];          // 削除予定のメディア

            // モーダルを閉じる
            document.getElementById('closeMessageModal').addEventListener('click', closeMsgModal);
            document.getElementById('cancelMessage').addEventListener('click', closeMsgModal);
            msgModal.addEventListener('click', function(e) {
                if (e.target === msgModal) closeMsgModal();
            });

            function closeMsgModal() {
                msgModal.classList.remove('active');
                msgForm.reset();
                document.getElementById('messageId').value = '';
                msgFilesText.textContent = 'ファイルを選択してください';
                msgPreviewGroup.style.display = 'none';
                msgPreviewDiv.innerHTML = '';
                existingMediaGroup.style.display = 'none';
                existingMediaPreview.innerHTML = '';
                deletedMediaGroup.style.display = 'none';
                deletedMediaPreview.innerHTML = '';
                currentExistingMedia = [];
                deletedMedia = [];
            }

            // 既存メディアの表示を更新
            function renderExistingMedia() {
                existingMediaPreview.innerHTML = '';
                if (currentExistingMedia.length === 0) {
                    existingMediaGroup.style.display = 'none';
                    return;
                }
                existingMediaGroup.style.display = 'block';

                currentExistingMedia.forEach(function(media, index) {
                    var item = document.createElement('div');
                    item.className = 'preview-item existing-media-item';

                    if (media.type === 'photo') {
                        var img = document.createElement('img');
                        img.className = 'preview-thumb';
                        img.src = media.url;
                        item.appendChild(img);
                    } else {
                        var video = document.createElement('video');
                        video.className = 'preview-thumb';
                        video.src = media.url;
                        item.appendChild(video);
                        var badge = document.createElement('span');
                        badge.className = 'preview-badge';
                        badge.textContent = '動画';
                        item.appendChild(badge);
                    }

                    // 削除ボタン
                    var deleteBtn = document.createElement('button');
                    deleteBtn.type = 'button';
                    deleteBtn.className = 'media-delete-btn';
                    deleteBtn.innerHTML = '&times;';
                    deleteBtn.addEventListener('click', function() {
                        moveToDeleted(index);
                    });
                    item.appendChild(deleteBtn);

                    existingMediaPreview.appendChild(item);
                });
            }

            // 削除予定メディアの表示を更新
            function renderDeletedMedia() {
                deletedMediaPreview.innerHTML = '';
                if (deletedMedia.length === 0) {
                    deletedMediaGroup.style.display = 'none';
                    return;
                }
                deletedMediaGroup.style.display = 'block';

                deletedMedia.forEach(function(media, index) {
                    var item = document.createElement('div');
                    item.className = 'preview-item deleted-media-item';
                    item.title = 'クリックで復元';

                    if (media.type === 'photo') {
                        var img = document.createElement('img');
                        img.className = 'preview-thumb';
                        img.src = media.url;
                        item.appendChild(img);
                    } else {
                        var video = document.createElement('video');
                        video.className = 'preview-thumb';
                        video.src = media.url;
                        item.appendChild(video);
                        var badge = document.createElement('span');
                        badge.className = 'preview-badge';
                        badge.textContent = '動画';
                        item.appendChild(badge);
                    }

                    item.addEventListener('click', function() {
                        restoreFromDeleted(index);
                    });

                    deletedMediaPreview.appendChild(item);
                });
            }

            // 既存メディアを削除予定に移動
            function moveToDeleted(index) {
                var media = currentExistingMedia.splice(index, 1)[0];
                deletedMedia.push(media);
                renderExistingMedia();
                renderDeletedMedia();
            }

            // 削除予定から復元
            function restoreFromDeleted(index) {
                var media = deletedMedia.splice(index, 1)[0];
                currentExistingMedia.push(media);
                renderExistingMedia();
                renderDeletedMedia();
            }

            // ファイル選択時のプレビュー
            msgFilesInput.addEventListener('change', function() {
                var files = msgFilesInput.files;
                if (!files || files.length === 0) {
                    msgFilesText.textContent = 'ファイルを選択してください';
                    msgPreviewGroup.style.display = 'none';
                    msgPreviewDiv.innerHTML = '';
                    return;
                }

                msgFilesText.textContent = files.length + '件のファイルを選択中';
                msgPreviewDiv.innerHTML = '';

                for (var i = 0; i < files.length; i++) {
                    var file = files[i];
                    var previewItem = document.createElement('div');
                    previewItem.className = 'preview-item';

                    if (file.type.startsWith('image/')) {
                        var img = document.createElement('img');
                        img.className = 'preview-thumb';
                        (function(imgEl, f) {
                            var reader = new FileReader();
                            reader.onload = function(e) { imgEl.src = e.target.result; };
                            reader.readAsDataURL(f);
                        })(img, file);
                        previewItem.appendChild(img);
                    } else if (file.type.startsWith('video/')) {
                        var video = document.createElement('video');
                        video.className = 'preview-thumb';
                        video.src = URL.createObjectURL(file);
                        previewItem.appendChild(video);
                        var badge = document.createElement('span');
                        badge.className = 'preview-badge';
                        badge.textContent = '動画';
                        previewItem.appendChild(badge);
                    }

                    msgPreviewDiv.appendChild(previewItem);
                }
                msgPreviewGroup.style.display = 'block';
            });

            // メッセージモーダルを開く（新規 or 編集）
            // msg: 編集時はメッセージオブジェクト、新規時はnull
            // defaultCategory: 新規追加時のカテゴリ初期値（オプション）
            window.openMessageModal = function(msg, defaultCategory) {
                closeMsgModal(); // まずリセット
                if (msg) {
                    // 編集モード
                    msgModalTitle.textContent = '追悼メッセージを編集';
                    msgSubmitBtn.textContent = '更新する';
                    document.getElementById('messageId').value = msg.id;
                    document.getElementById('msgAuthor').value = msg.author || '';
                    document.getElementById('msgAffiliation').value = msg.affiliation || '';
                    document.getElementById('msgRelationship').value = msg.relationship || '';
                    document.getElementById('msgCategory').value = msg.category || '';
                    document.getElementById('msgContent').value = msg.content || '';

                    // 既存メディアを設定
                    if (msg.media && msg.media.length > 0) {
                        currentExistingMedia = msg.media.slice(); // コピー
                        renderExistingMedia();
                    }
                } else {
                    // 新規モード
                    msgModalTitle.textContent = '追悼メッセージを追加';
                    msgSubmitBtn.textContent = '追加する';
                    // カテゴリの初期値を設定
                    if (defaultCategory) {
                        document.getElementById('msgCategory').value = defaultCategory;
                    }
                }
                msgModal.classList.add('active');
            };

            // メッセージ送信
            msgForm.addEventListener('submit', function(e) {
                e.preventDefault();

                // バリデーション（必須項目がなければ送信しない）
                var authorValue = document.getElementById('msgAuthor').value.trim();
                var categoryValue = document.getElementById('msgCategory').value;

                if (!authorValue || !categoryValue) {
                    return;
                }

                var msgId = document.getElementById('messageId').value;
                var action = msgId ? 'update_message' : 'add_message';

                uploadLoading.classList.add('active');

                var formData = new FormData();
                formData.append('action', action);
                if (msgId) formData.append('id', msgId);
                formData.append('author', authorValue);
                formData.append('affiliation', document.getElementById('msgAffiliation').value);
                formData.append('relationship', document.getElementById('msgRelationship').value);
                formData.append('category', categoryValue);
                formData.append('content', document.getElementById('msgContent').value);

                // 既存メディア情報を送信（更新時のみ）
                if (msgId) {
                    formData.append('existing_media', JSON.stringify(currentExistingMedia));
                }

                // ファイルを追加（複数対応）
                var files = msgFilesInput.files;
                for (var i = 0; i < files.length; i++) {
                    formData.append('files[]', files[i]);
                }

                fetch('api/gallery.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    uploadLoading.classList.remove('active');
                    if (data.success) {
                        closeMsgModal();
                        loadGalleryData();
                    }
                })
                .catch(function() {
                    uploadLoading.classList.remove('active');
                });
            });
        }
    })();
    </script>
</body>
</html>
