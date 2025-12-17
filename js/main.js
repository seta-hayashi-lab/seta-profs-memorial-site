/**
 * 瀬田和久先生 追悼サイト - メインJavaScript
 * バニラJS（ES6+）で実装，トランスパイル不要
 * ※ヘッダー・ナビゲーション・フッターのテンプレートは templates.js に分離
 */

(function() {
    'use strict';

    // =====================================================
    // DOM要素の取得
    // =====================================================
    var elements = {};

    function getElements() {
        elements = {
            mainNav: document.querySelector('.main-nav'),
            navLinks: document.querySelectorAll('.nav-link'),
            tabButtons: document.querySelectorAll('.tab-btn'),
            tabContents: document.querySelectorAll('.tab-content'),
            messageForm: document.getElementById('messageForm'),
            messagesContainer: document.getElementById('messagesContainer'),
            photoGallery: document.getElementById('photoGallery'),
            videoGallery: document.getElementById('videoGallery'),
            lightbox: document.getElementById('lightbox'),
            lightboxImage: document.getElementById('lightboxImage'),
            lightboxCaption: document.getElementById('lightboxCaption'),
            mainPortrait: document.getElementById('mainPortrait')
        };
    }

    // =====================================================
    // データストレージ（ローカルストレージ利用）
    // =====================================================
    var storage = {
        getMessages: function() {
            var data = localStorage.getItem('memorial_messages');
            return data ? JSON.parse(data) : [];
        },
        saveMessage: function(message) {
            var messages = this.getMessages();
            messages.push(message);
            localStorage.setItem('memorial_messages', JSON.stringify(messages));
        },
        getPhotos: function() {
            var data = localStorage.getItem('memorial_photos');
            return data ? JSON.parse(data) : [];
        },
        savePhoto: function(photo) {
            var photos = this.getPhotos();
            photos.push(photo);
            localStorage.setItem('memorial_photos', JSON.stringify(photos));
        },
        getVideos: function() {
            var data = localStorage.getItem('memorial_videos');
            return data ? JSON.parse(data) : [];
        },
        saveVideo: function(video) {
            var videos = this.getVideos();
            videos.push(video);
            localStorage.setItem('memorial_videos', JSON.stringify(videos));
        }
    };

    // =====================================================
    // タブ切り替え
    // =====================================================
    function initTabs() {
        if (!elements.tabButtons || elements.tabButtons.length === 0) return;

        elements.tabButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                var targetTab = this.getAttribute('data-tab');

                // ボタンのアクティブ状態を切り替え
                elements.tabButtons.forEach(function(btn) {
                    btn.classList.remove('active');
                });
                this.classList.add('active');

                // タブコンテンツの表示切り替え
                elements.tabContents.forEach(function(content) {
                    content.classList.remove('active');
                    if (content.id === targetTab) {
                        content.classList.add('active');
                    }
                });
            });
        });
    }

    // =====================================================
    // ギャラリー機能
    // =====================================================
    var currentGalleryIndex = 0;
    var galleryImages = [];

    function initGallery() {
        if (!elements.photoGallery && !elements.videoGallery) return;

        // 保存済みの写真を読み込み
        if (elements.photoGallery) {
            loadSavedPhotos();
        }

        if (elements.videoGallery) {
            loadSavedVideos();
        }

        // ライトボックスの制御
        if (elements.lightbox) {
            initLightbox();
        }
    }

    function loadSavedPhotos() {
        var photos = storage.getPhotos();
        photos.forEach(function(photo, index) {
            addPhotoToGallery(photo, index);
        });
        updateGalleryImages();
    }

    function loadSavedVideos() {
        var videos = storage.getVideos();
        videos.forEach(function(video) {
            addVideoToGallery(video);
        });
    }

    function addPhotoToGallery(photo, index) {
        if (!elements.photoGallery) return;

        var item = document.createElement('div');
        item.className = 'gallery-item';
        item.setAttribute('data-index', index);
        item.setAttribute('data-caption', photo.caption || '');

        var img = document.createElement('img');
        img.src = photo.src;
        img.alt = photo.caption || '瀬田先生のお写真';
        img.loading = 'lazy';

        item.appendChild(img);

        item.addEventListener('click', function() {
            openLightbox(parseInt(this.getAttribute('data-index')));
        });

        elements.photoGallery.appendChild(item);
    }

    function addVideoToGallery(video) {
        if (!elements.videoGallery) return;

        var item = document.createElement('div');
        item.className = 'video-item';

        if (video.type === 'youtube') {
            var iframe = document.createElement('iframe');
            iframe.src = 'https://www.youtube.com/embed/' + video.id;
            iframe.setAttribute('allowfullscreen', '');
            iframe.setAttribute('loading', 'lazy');
            iframe.setAttribute('frameborder', '0');
            item.appendChild(iframe);
        } else if (video.type === 'vimeo') {
            var iframe = document.createElement('iframe');
            iframe.src = 'https://player.vimeo.com/video/' + video.id;
            iframe.setAttribute('allowfullscreen', '');
            iframe.setAttribute('loading', 'lazy');
            iframe.setAttribute('frameborder', '0');
            item.appendChild(iframe);
        }

        elements.videoGallery.appendChild(item);
    }

    function updateGalleryImages() {
        galleryImages = [];
        if (!elements.photoGallery) return;

        var items = elements.photoGallery.querySelectorAll('.gallery-item:not(.placeholder)');
        items.forEach(function(item, index) {
            var img = item.querySelector('img');
            if (img) {
                galleryImages.push({
                    src: img.src,
                    caption: item.getAttribute('data-caption') || ''
                });
                item.setAttribute('data-index', index);
            }
        });
    }

    function showAddPhotoDialog() {
        var url = prompt('お写真のURLを入力してください：');
        if (url && url.trim()) {
            var caption = prompt('キャプション（任意）：') || '';
            var photo = {
                src: url.trim(),
                caption: caption
            };
            storage.savePhoto(photo);
            addPhotoToGallery(photo, storage.getPhotos().length - 1);
            updateGalleryImages();
        }
    }

    function showAddVideoDialog() {
        var url = prompt('動画のURL（YouTube/Vimeo）を入力してください：');
        if (url && url.trim()) {
            var video = parseVideoUrl(url.trim());
            if (video) {
                storage.saveVideo(video);
                addVideoToGallery(video);
            } else {
                alert('対応していないURLです．YouTubeまたはVimeoのURLを入力してください．');
            }
        }
    }

    function parseVideoUrl(url) {
        // YouTube
        var youtubeMatch = url.match(/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]+)/);
        if (youtubeMatch) {
            return { type: 'youtube', id: youtubeMatch[1] };
        }

        // Vimeo
        var vimeoMatch = url.match(/vimeo\.com\/(\d+)/);
        if (vimeoMatch) {
            return { type: 'vimeo', id: vimeoMatch[1] };
        }

        return null;
    }

    // =====================================================
    // ライトボックス
    // =====================================================
    function initLightbox() {
        var closeBtn = elements.lightbox.querySelector('.lightbox-close');
        var prevBtn = elements.lightbox.querySelector('.lightbox-prev');
        var nextBtn = elements.lightbox.querySelector('.lightbox-next');

        if (closeBtn) {
            closeBtn.addEventListener('click', closeLightbox);
        }
        if (prevBtn) {
            prevBtn.addEventListener('click', function() { navigateLightbox(-1); });
        }
        if (nextBtn) {
            nextBtn.addEventListener('click', function() { navigateLightbox(1); });
        }

        elements.lightbox.addEventListener('click', function(e) {
            if (e.target === elements.lightbox) {
                closeLightbox();
            }
        });

        // キーボード操作
        document.addEventListener('keydown', function(e) {
            if (!elements.lightbox.classList.contains('active')) return;

            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowLeft') navigateLightbox(-1);
            if (e.key === 'ArrowRight') navigateLightbox(1);
        });
    }

    function openLightbox(index) {
        if (galleryImages.length === 0) return;

        currentGalleryIndex = index;
        updateLightboxImage();
        elements.lightbox.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
        elements.lightbox.classList.remove('active');
        document.body.style.overflow = '';
    }

    function navigateLightbox(direction) {
        currentGalleryIndex += direction;

        if (currentGalleryIndex < 0) {
            currentGalleryIndex = galleryImages.length - 1;
        } else if (currentGalleryIndex >= galleryImages.length) {
            currentGalleryIndex = 0;
        }

        updateLightboxImage();
    }

    function updateLightboxImage() {
        var image = galleryImages[currentGalleryIndex];
        if (image && elements.lightboxImage) {
            elements.lightboxImage.src = image.src;
            if (elements.lightboxCaption) {
                elements.lightboxCaption.textContent = image.caption;
            }
        }
    }

    // =====================================================
    // メッセージフォーム
    // =====================================================
    function initMessageForm() {
        if (!elements.messageForm) return;

        // 保存済みメッセージを読み込み
        loadSavedMessages();

        elements.messageForm.addEventListener('submit', function(e) {
            e.preventDefault();

            var formData = new FormData(this);
            var message = {
                id: Date.now(),
                name: formData.get('authorName'),
                affiliation: formData.get('authorAffiliation'),
                relationship: formData.get('relationship'),
                content: formData.get('messageContent'),
                email: formData.get('contactEmail'),
                date: new Date().toISOString(),
                approved: false
            };

            // バリデーション
            if (!message.name || !message.content || !message.relationship) {
                alert('必須項目を入力してください．');
                return;
            }

            // 保存
            storage.saveMessage(message);

            // フォームをリセット
            this.reset();

            // 確認メッセージ
            alert('メッセージを送信いたしました．管理者の確認後に掲載されます．');
        });
    }

    function loadSavedMessages() {
        if (!elements.messagesContainer) return;

        var messages = storage.getMessages();
        var approvedMessages = messages.filter(function(m) { return m.approved; });

        approvedMessages.forEach(function(message) {
            addMessageToPage(message);
        });
    }

    function addMessageToPage(message) {
        var article = document.createElement('article');
        article.className = 'message-card';

        var contentHtml = '<div class="message-content"><p>' + escapeHtml(message.content) + '</p></div>';
        var authorHtml = '<div class="message-author"><span class="author-name">' + escapeHtml(message.name) + '</span>';

        if (message.affiliation) {
            authorHtml += '<span class="author-affiliation">' + escapeHtml(message.affiliation) + '</span>';
        }
        authorHtml += '</div>';

        article.innerHTML = contentHtml + authorHtml;

        // 最初のメッセージ（説明文）の後に追加
        var firstMessage = elements.messagesContainer.querySelector('.message-card');
        if (firstMessage && firstMessage.nextSibling) {
            elements.messagesContainer.insertBefore(article, firstMessage.nextSibling);
        } else if (firstMessage) {
            elements.messagesContainer.appendChild(article);
        } else {
            elements.messagesContainer.appendChild(article);
        }
    }

    // =====================================================
    // メインポートレート
    // =====================================================
    function initPortrait() {
        if (!elements.mainPortrait) return;

        // ローカルストレージから保存済みの画像を読み込み
        var savedPortrait = localStorage.getItem('memorial_portrait');
        if (savedPortrait) {
            displayPortrait(savedPortrait);
        }

        elements.mainPortrait.addEventListener('click', function() {
            var url = prompt('メイン写真のURLを入力してください：');
            if (url && url.trim()) {
                localStorage.setItem('memorial_portrait', url.trim());
                displayPortrait(url.trim());
            }
        });
    }

    function displayPortrait(src) {
        elements.mainPortrait.innerHTML = '';
        var img = document.createElement('img');
        img.src = src;
        img.alt = '瀬田和久先生';
        elements.mainPortrait.appendChild(img);
        elements.mainPortrait.classList.remove('portrait-placeholder');
    }

    // =====================================================
    // ユーティリティ関数
    // =====================================================
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // =====================================================
    // 管理者用機能（コンソールから利用）
    // =====================================================
    window.memorialAdmin = {
        // 全メッセージを表示
        listMessages: function() {
            var messages = storage.getMessages();
            console.table(messages);
            return messages;
        },

        // メッセージを承認
        approveMessage: function(id) {
            var messages = storage.getMessages();
            var index = messages.findIndex(function(m) { return m.id === id; });
            if (index !== -1) {
                messages[index].approved = true;
                localStorage.setItem('memorial_messages', JSON.stringify(messages));
                console.log('メッセージID ' + id + ' を承認しました．ページを再読み込みしてください．');
                return true;
            }
            console.log('メッセージが見つかりません．');
            return false;
        },

        // メッセージを削除
        deleteMessage: function(id) {
            var messages = storage.getMessages();
            messages = messages.filter(function(m) { return m.id !== id; });
            localStorage.setItem('memorial_messages', JSON.stringify(messages));
            console.log('メッセージID ' + id + ' を削除しました．');
        },

        // 写真を削除
        deletePhoto: function(index) {
            var photos = storage.getPhotos();
            photos.splice(index, 1);
            localStorage.setItem('memorial_photos', JSON.stringify(photos));
            console.log('お写真 ' + index + ' を削除しました．ページを再読み込みしてください．');
        },

        // 動画を削除
        deleteVideo: function(index) {
            var videos = storage.getVideos();
            videos.splice(index, 1);
            localStorage.setItem('memorial_videos', JSON.stringify(videos));
            console.log('動画 ' + index + ' を削除しました．ページを再読み込みしてください．');
        },

        // ポートレートを削除
        deletePortrait: function() {
            localStorage.removeItem('memorial_portrait');
            console.log('ポートレートを削除しました．ページを再読み込みしてください．');
        },

        // データをエクスポート
        exportData: function() {
            var data = {
                messages: storage.getMessages(),
                photos: storage.getPhotos(),
                videos: storage.getVideos(),
                portrait: localStorage.getItem('memorial_portrait')
            };
            console.log(JSON.stringify(data, null, 2));
            return data;
        },

        // データをインポート
        importData: function(jsonString) {
            try {
                var data = JSON.parse(jsonString);
                if (data.messages) localStorage.setItem('memorial_messages', JSON.stringify(data.messages));
                if (data.photos) localStorage.setItem('memorial_photos', JSON.stringify(data.photos));
                if (data.videos) localStorage.setItem('memorial_videos', JSON.stringify(data.videos));
                if (data.portrait) localStorage.setItem('memorial_portrait', data.portrait);
                console.log('データをインポートしました．ページを再読み込みしてください．');
                return true;
            } catch (e) {
                console.error('インポートに失敗しました:', e);
                return false;
            }
        },

        // 全データをクリア
        clearAllData: function() {
            if (confirm('すべてのデータを削除しますか？この操作は取り消せません．')) {
                localStorage.removeItem('memorial_messages');
                localStorage.removeItem('memorial_photos');
                localStorage.removeItem('memorial_videos');
                localStorage.removeItem('memorial_portrait');
                console.log('すべてのデータを削除しました．');
            }
        }
    };

    // =====================================================
    // ハンバーガーメニュー
    // =====================================================
    function initHamburgerMenu() {
        var hamburgerBtn = document.getElementById('hamburgerBtn');
        var navList = document.getElementById('navList');

        if (!hamburgerBtn || !navList) return;

        hamburgerBtn.addEventListener('click', function() {
            var isOpen = navList.classList.toggle('open');
            hamburgerBtn.classList.toggle('active');
            hamburgerBtn.setAttribute('aria-expanded', isOpen);
            hamburgerBtn.setAttribute('aria-label', isOpen ? 'メニューを閉じる' : 'メニューを開く');
        });

        // メニュー項目クリックでメニューを閉じる
        navList.querySelectorAll('.nav-link').forEach(function(link) {
            link.addEventListener('click', function() {
                navList.classList.remove('open');
                hamburgerBtn.classList.remove('active');
                hamburgerBtn.setAttribute('aria-expanded', 'false');
                hamburgerBtn.setAttribute('aria-label', 'メニューを開く');
            });
        });

        // メニュー外クリックで閉じる
        document.addEventListener('click', function(e) {
            if (!hamburgerBtn.contains(e.target) && !navList.contains(e.target)) {
                navList.classList.remove('open');
                hamburgerBtn.classList.remove('active');
                hamburgerBtn.setAttribute('aria-expanded', 'false');
                hamburgerBtn.setAttribute('aria-label', 'メニューを開く');
            }
        });
    }

    // =====================================================
    // 初期化
    // =====================================================
    function init() {
        // DOM要素を取得（テンプレートは templates.js で挿入済み）
        getElements();

        // 各機能を初期化
        initHamburgerMenu();
        initTabs();
        initGallery();
        initMessageForm();
        initPortrait();

        console.log('瀬田和久先生追悼サイトを読み込みました．');
        console.log('管理者機能: memorialAdmin オブジェクトをご利用ください．');
    }

    // DOMContentLoaded後に初期化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
