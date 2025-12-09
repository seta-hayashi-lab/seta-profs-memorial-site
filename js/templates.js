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
        // ヘッダーを生成（compact: trueでサブページ用コンパクトヘッダー）
        header: function(options) {
            options = options || {};
            var compact = options.compact || false;
            var compactClass = compact ? ' compact' : '';
            var datesHtml = compact ? '' : '<p class="dates">1963 - 2025</p>';

            return '<header class="site-header' + compactClass + '">' +
                '<div class="header-content">' +
                '<p class="site-subtitle">追悼</p>' +
                '<h1 class="site-title">瀬田 和久 先生</h1>' +
                datesHtml +
                '</div>' +
                '</header>';
        },

        // ナビゲーションを生成（activePage: 現在のページ識別子）
        navigation: function(activePage) {
            var navItems = [
                { href: 'index.html', label: '追悼の辞', id: 'index' },
                { href: 'profile.html', label: 'ご略歴', id: 'profile' },
                { href: 'research.html', label: 'ご研究業績', id: 'research' },
                { href: 'contributions.html', label: 'ご貢献', id: 'contributions' },
                { href: 'gallery.html', label: 'お写真', id: 'gallery' },
                { href: 'messages.html', label: '追悼メッセージ', id: 'messages' }
            ];

            var listItems = navItems.map(function(item) {
                var activeClass = item.id === activePage ? ' active' : '';
                return '<li><a href="' + item.href + '" class="nav-link' + activeClass + '">' + item.label + '</a></li>';
            }).join('');

            return '<nav class="main-nav">' +
                '<ul class="nav-list">' +
                listItems +
                '</ul>' +
                '</nav>';
        },

        // フッターを生成
        footer: function() {
            return '<footer class="site-footer">' +
                '<div class="container">' +
                '<div class="footer-content">' +
                '<div class="footer-info">' +
                '<h3>瀬田和久先生 追悼サイト</h3>' +
                '<p>大阪公立大学 大学院情報学研究科</p>' +
                '<p>瀬田・林・油谷研究室</p>' +
                '</div>' +
                '<div class="footer-links">' +
                '<h4>関連リンク</h4>' +
                '<ul>' +
                '<li><a href="https://www.omu.ac.jp/i/" target="_blank" rel="noopener">大阪公立大学 情報学研究科</a></li>' +
                '<li><a href="https://kshci-lab.net/" target="_blank" rel="noopener">瀬田・林・油谷研究室</a></li>' +
                '<li><a href="https://researchmap.jp/read0101180" target="_blank" rel="noopener">researchmap</a></li>' +
                '</ul>' +
                '</div>' +
                '</div>' +
                '<div class="footer-bottom">' +
                '<p>&copy; 2025 瀬田和久先生追悼サイト</p>' +
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
            var isCompact = headerPlaceholder.getAttribute('data-compact') === 'true';
            headerPlaceholder.outerHTML = templates.header({ compact: isCompact });
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
