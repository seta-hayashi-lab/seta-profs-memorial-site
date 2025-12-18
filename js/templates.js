/**
 * 瀬田和久先生 追悼サイト - 共通テンプレート
 * ヘッダー・ナビゲーション・フッターの共通コンポーネント
 */

(function() {
    'use strict';

    // =====================================================
    // 共通テンプレート（ヘッダー・ナビゲーション・フッター）
    // =====================================================
    var templates = {
        // ヘッダーを生成
        header: function(options) {
            return '<header class="site-header">' +
                '<div class="header-content">' +
                '<div class="header-portrait">' +
                '<img src="images/person/seta.png" alt="瀬田和久先生">' +
                '</div>' +
                '<div class="header-text">' +
                '<p class="site-subtitle">追悼</p>' +
                '<h1 class="site-title">瀬田 和久 先生</h1>' +
                '<p class="dates">1970 - 2025</p>' +
                '</div>' +
                '</div>' +
                '</header>';
        },

        // ナビゲーションを生成（activePage: 現在のページ識別子）
        navigation: function(activePage) {
            // PC版の順序
            var navItems = [
                { href: 'index.html', label: '追悼の辞', id: 'index' },
                { href: 'research.html', label: '学問へのご貢献', id: 'research' },
                { href: 'words.html', label: '先生のお言葉', id: 'words' },
                { href: 'gallery.php', label: '追悼メッセージ一覧', id: 'gallery', requiresLogin: true },
                { href: 'messages.php', label: 'メッセージを送る', id: 'messages' },
                { href: 'inquiry.html', label: 'お問い合わせ', id: 'inquiry' }
            ];

            var listItems = navItems.map(function(item) {
                var activeClass = item.id === activePage ? ' active' : '';
                var loginBadge = item.requiresLogin ? '<span class="nav-login-badge">ログイン必須</span>' : '';
                return '<li><a href="' + item.href + '" class="nav-link' + activeClass + '">' + item.label + loginBadge + '</a></li>';
            }).join('');

            return '<nav class="main-nav">' +
                '<button class="hamburger-btn" id="hamburgerBtn" aria-label="メニューを開く" aria-expanded="false">' +
                '<span class="hamburger-line"></span>' +
                '<span class="hamburger-line"></span>' +
                '<span class="hamburger-line"></span>' +
                '</button>' +
                '<ul class="nav-list" id="navList">' +
                listItems +
                '</ul>' +
                '</nav>';
        },

        // フッターを生成
        footer: function() {
            return '<footer class="site-footer">' +
                '<div class="container">' +
                '<div class="footer-bottom">' +
                '<p>&copy; 2025 瀬田・林・油谷研究室 | <a href="https://kshci-lab.net/" target="_blank" rel="noopener">研究室HP</a></p>' +
                '<p class="footer-admin">主たる管理者: 油谷知岐 (<a href="mailto:aburatani.tomoki@omu.ac.jp">aburatani.tomoki@omu.ac.jp</a>)</p>' +
                '</div>' +
                '</div>' +
                '</footer>';
        }
    };

    // テンプレートを挿入する関数
    function insertTemplates() {
        // ヘッダーを挿入
        var headerPlaceholder = document.getElementById('header-template');
        if (headerPlaceholder) {
            headerPlaceholder.outerHTML = templates.header();
        }

        // ナビゲーションを挿入
        var navPlaceholder = document.getElementById('nav-template');
        if (navPlaceholder) {
            var activePage = navPlaceholder.getAttribute('data-active') || 'index';
            navPlaceholder.outerHTML = templates.navigation(activePage);
        }

        // フッターを挿入
        var footerPlaceholder = document.getElementById('footer-template');
        if (footerPlaceholder) {
            footerPlaceholder.outerHTML = templates.footer();
        }
    }

    // グローバルに公開（必要に応じて他のスクリプトから利用可能）
    window.siteTemplates = {
        templates: templates,
        insert: insertTemplates
    };

    // DOMContentLoaded時にテンプレートを挿入
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', insertTemplates);
    } else {
        insertTemplates();
    }

})();
