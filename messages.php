<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="瀬田和久先生 追悼メッセージや写真を送る - 大阪公立大学 大学院情報学研究科 教授">
    <meta name="robots" content="noindex, nofollow">
    <title>追悼メッセージや写真を送る | 瀬田和久先生 追悼サイト</title>
    <link rel="icon" type="image/png" href="images/favicon.png">
    <link rel="stylesheet" href="css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+JP:wght@400;500&family=Noto+Sans+JP:wght@400;500&display=swap" rel="stylesheet">
</head>
<body>
    <!-- ヘッダー（テンプレートから生成） -->
    <div id="header-template" data-compact="true"></div>

    <!-- ナビゲーション（テンプレートから生成） -->
    <div id="nav-template" data-active="messages"></div>

    <!-- メインコンテンツ -->
    <main class="main-content">
        <section class="messages-section">
            <div class="container">
                <h1 class="page-title">追悼メッセージや写真を送る</h1>

                <p class="messages-intro">
                    瀬田先生への追悼メッセージ，思い出のお写真・動画をお寄せください．
                    投稿いただいた内容は，管理者の確認後に掲載されます．
                </p>

                <!-- メッセージ投稿フォーム -->
                <div class="message-form-section">

                    <form id="messageForm" class="message-form">
                        <div class="form-group">
                            <label for="authorName">お名前 <span class="required">*</span></label>
                            <input type="text" id="authorName" name="authorName" required
                                   placeholder="例：山田 太郎">
                        </div>

                        <div class="form-group">
                            <label for="authorAffiliation">ご所属・ご関係</label>
                            <input type="text" id="authorAffiliation" name="authorAffiliation"
                                   placeholder="例：○○大学 / 元研究室メンバー / 共同研究者">
                        </div>

                        <div class="form-group">
                            <label>瀬田先生とのご関係 <span class="required">*</span>（複数選択可）</label>
                            <div class="checkbox-group" id="relationshipGroup">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="relationship" value="alumni">
                                    <span>研究室OB/OG</span>
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="relationship" value="student">
                                    <span>教え子（研究室OB／OG以外）</span>
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="relationship" value="colleague">
                                    <span>ご同僚・共同研究者</span>
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="relationship" value="academic">
                                    <span>学会関係者</span>
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="relationship" value="friend">
                                    <span>友人</span>
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="relationship" value="family">
                                    <span>親族</span>
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="relationship" value="other">
                                    <span>その他</span>
                                </label>
                            </div>
                            <input type="text" id="relationshipDetail" name="relationshipDetail"
                                   placeholder="具体的なご関係（任意）" class="relationship-detail-input">
                        </div>

                        <div class="form-group">
                            <label for="messageContent">メッセージ</label>
                            <textarea id="messageContent" name="messageContent" rows="8"
                                      placeholder="瀬田先生との思い出，感謝の気持ちなどをお書きください．"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="mediaFiles">お写真・動画</label>
                            <div class="file-input-wrapper">
                                <input type="file" id="mediaFiles" name="mediaFiles" accept="image/jpeg,image/png,image/gif,image/webp,video/mp4,video/webm,video/quicktime,video/x-msvideo" multiple>
                                <span class="file-input-text" id="fileInputText">ファイルを選択してください（複数選択可）</span>
                            </div>
                            <p class="file-note">対応形式: JPEG, PNG, GIF, WebP, MP4, WebM, MOV, AVI<br><strong>最大1GB/ファイル</strong></p>
                            <!-- ファイルプレビュー -->
                            <div id="filePreviewSection" class="file-preview-section" style="display: none;">
                                <h4>選択中のファイル</h4>
                                <div id="filePreviewList" class="file-preview-list"></div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="contactEmail">連絡先メールアドレス（非公開）</label>
                            <input type="email" id="contactEmail" name="contactEmail"
                                   placeholder="確認のためのご連絡先（任意）">
                        </div>

                        <div class="form-group">
                            <label>サイトへの掲載</label>
                            <div class="radio-group">
                                <label class="radio-label">
                                    <input type="radio" name="publish" value="yes" checked>
                                    <span>掲載を希望する</span>
                                </label>
                                <label class="radio-label">
                                    <input type="radio" name="publish" value="no">
                                    <span>掲載を希望しない（管理者のみ閲覧）</span>
                                </label>
                            </div>
                        </div>

                        <div class="form-note">
                            <p>※ <span class="required">*</span> は必須項目です</p>
                            <p>※ 投稿いただいたメッセージは管理者の確認後に掲載されます</p>
                            <p>※ メールアドレスは確認のためにのみ使用し，公開されません</p>
                            <p>※ 故人への敬意を込めた内容でお願いいたします</p>
                        </div>

                        <button type="submit" class="submit-btn" id="submitBtn">メッセージを送信</button>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <!-- ローディングオーバーレイ -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <p class="loading-text">送信中です...</p>
            <p class="loading-subtext">しばらくお待ちください</p>
        </div>
    </div>

    <!-- 送信完了モーダル -->
    <div id="successModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-icon">✓</div>
            <h3 class="modal-title">送信が完了しました</h3>
            <p class="modal-message">追悼メッセージをお寄せいただき，ありがとうございます．</p>
            <p class="modal-submessage">引き続き，お写真や動画を追加で送信いただけます．</p>
            <button type="button" class="modal-btn" id="modalCloseBtn">閉じる</button>
        </div>
    </div>

    <!-- フッター（テンプレートから生成） -->
    <div id="footer-template"></div>

    <script src="js/templates.js"></script>
    <script src="js/main.js"></script>
    <script>
    (function() {
        'use strict';

        var form = document.getElementById('messageForm');
        var submitBtn = document.getElementById('submitBtn');
        var fileInput = document.getElementById('mediaFiles');
        var fileInputText = document.getElementById('fileInputText');
        var filePreviewSection = document.getElementById('filePreviewSection');
        var filePreviewList = document.getElementById('filePreviewList');
        var loadingOverlay = document.getElementById('loadingOverlay');
        var successModal = document.getElementById('successModal');
        var modalCloseBtn = document.getElementById('modalCloseBtn');
        var MAX_FILE_SIZE = 1 * 1024 * 1024 * 1024; // 1GB

        var isSubmitting = false;

        // ページ離脱防止
        function preventNavigation(e) {
            if (isSubmitting) {
                e.preventDefault();
                e.returnValue = '送信中です．このページを離れると送信が中断されます．';
                return e.returnValue;
            }
        }

        // ローディング表示
        function showLoading() {
            isSubmitting = true;
            loadingOverlay.classList.add('active');
            window.addEventListener('beforeunload', preventNavigation);
        }

        // ローディング非表示
        function hideLoading() {
            isSubmitting = false;
            loadingOverlay.classList.remove('active');
            window.removeEventListener('beforeunload', preventNavigation);
        }

        // モーダル表示
        function showSuccessModal() {
            successModal.classList.add('active');
        }

        // モーダル非表示
        function hideSuccessModal() {
            successModal.classList.remove('active');
        }

        // モーダル閉じるボタン
        modalCloseBtn.addEventListener('click', hideSuccessModal);

        // モーダル背景クリックで閉じる
        successModal.addEventListener('click', function(e) {
            if (e.target === successModal) {
                hideSuccessModal();
            }
        });

        // ファイルサイズを読みやすい形式に変換
        function formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            if (bytes < 1024 * 1024 * 1024) return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
            return (bytes / (1024 * 1024 * 1024)).toFixed(2) + ' GB';
        }

        // ファイルプレビューを表示
        function showFilePreviews(files) {
            filePreviewList.innerHTML = '';

            if (files.length === 0) {
                filePreviewSection.style.display = 'none';
                return;
            }

            filePreviewSection.style.display = 'block';

            for (var i = 0; i < files.length; i++) {
                var file = files[i];
                var item = document.createElement('div');
                item.className = 'file-preview-item';

                var isImage = file.type.startsWith('image/');
                var isVideo = file.type.startsWith('video/');

                if (isImage) {
                    var img = document.createElement('img');
                    img.className = 'file-preview-thumb';
                    img.alt = file.name;
                    var reader = new FileReader();
                    reader.onload = (function(imgElement) {
                        return function(e) {
                            imgElement.src = e.target.result;
                        };
                    })(img);
                    reader.readAsDataURL(file);
                    item.appendChild(img);
                } else if (isVideo) {
                    var videoIcon = document.createElement('div');
                    videoIcon.className = 'file-preview-video-icon';
                    videoIcon.innerHTML = '▶';
                    item.appendChild(videoIcon);
                }

                var info = document.createElement('div');
                info.className = 'file-preview-info';
                info.innerHTML = '<span class="file-preview-name">' + file.name + '</span>' +
                                 '<span class="file-preview-size">' + formatFileSize(file.size) + '</span>';
                item.appendChild(info);

                filePreviewList.appendChild(item);
            }
        }

        // メッセージとファイルのみクリア（名前・所属・関係は保持）
        function clearMessageAndFiles() {
            document.getElementById('messageContent').value = '';
            fileInput.value = '';
            fileInputText.textContent = 'ファイルを選択してください（複数選択可）';
            showFilePreviews([]);
        }

        // ファイル選択時にファイル名を表示
        fileInput.addEventListener('change', function() {
            var files = fileInput.files;
            if (files.length > 0) {
                if (files.length === 1) {
                    fileInputText.textContent = files[0].name;
                } else {
                    fileInputText.textContent = files.length + '件のファイルを選択中';
                }
                showFilePreviews(files);
            } else {
                fileInputText.textContent = 'ファイルを選択してください（複数選択可）';
                showFilePreviews([]);
            }
        });

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            // ご関係のチェック（少なくとも1つ選択必須）
            var relationshipChecks = document.querySelectorAll('input[name="relationship"]:checked');
            if (relationshipChecks.length === 0) {
                alert('瀬田先生とのご関係を1つ以上選択してください');
                return;
            }

            // ファイルサイズチェック
            var files = fileInput.files;
            if (files.length > 0) {
                var oversizedFiles = [];
                for (var i = 0; i < files.length; i++) {
                    if (files[i].size > MAX_FILE_SIZE) {
                        oversizedFiles.push(files[i].name);
                    }
                }
                if (oversizedFiles.length > 0) {
                    alert('ファイルサイズが1GBを超えています: ' + oversizedFiles.join(', '));
                    return;
                }
            }

            // ローディング表示
            showLoading();
            submitBtn.disabled = true;

            // 選択されたご関係を収集
            var relationships = [];
            var relationshipLabels = {
                'alumni': '研究室OB/OG',
                'student': '教え子（研究室OB／OG以外）',
                'colleague': 'ご同僚・共同研究者',
                'academic': '学会関係者',
                'friend': '友人',
                'family': '親族',
                'other': 'その他'
            };
            relationshipChecks.forEach(function(cb) {
                relationships.push(relationshipLabels[cb.value] || cb.value);
            });
            var relationshipText = relationships.join('，');

            // 具体的なご関係があれば追加
            var relationshipDetail = document.getElementById('relationshipDetail').value.trim();
            if (relationshipDetail) {
                relationshipText += '（' + relationshipDetail + '）';
            }

            var authorName = document.getElementById('authorName').value;

            // フォームデータの収集（メッセージとファイルを一緒に送信）
            var formData = new FormData();
            formData.append('type', 'message');
            formData.append('name', authorName);
            formData.append('affiliation', document.getElementById('authorAffiliation').value);
            formData.append('relationship', relationshipText);
            formData.append('message', document.getElementById('messageContent').value);
            formData.append('email', document.getElementById('contactEmail').value);
            formData.append('publish', document.querySelector('input[name="publish"]:checked').value);

            // ファイルも一緒に送信
            for (var i = 0; i < files.length; i++) {
                formData.append('files[]', files[i]);
            }

            fetch('api/slack-post.php', {
                method: 'POST',
                body: formData
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                hideLoading();
                if (data.success) {
                    clearMessageAndFiles();
                    showSuccessModal();
                } else {
                    // エラーは表示しない（サイレント）
                    console.error('送信エラー:', data.error);
                }
            })
            .catch(function(error) {
                hideLoading();
                // エラーは表示しない（サイレント）
                console.error('送信エラー:', error.message);
            })
            .finally(function() {
                submitBtn.disabled = false;
            });
        });

    })();
    </script>
</body>
</html>
