#!/bin/bash
#
# 追悼メッセージデータ同期スクリプト
# サーバーのuploadsフォルダを指定したホストにSCPで転送
#
# 使用方法:
#   ./sync-uploads.sh [送信先ホスト] [送信先パス] [SSHポート]
#
# 例:
#   ./sync-uploads.sh user@example.com /var/www/backup/uploads 22
#   ./sync-uploads.sh user@192.168.1.100 ~/backup
#

set -e

# デフォルト設定
DEFAULT_PORT=22

# 引数チェック
if [ $# -lt 2 ]; then
    echo "使用方法: $0 <送信先ホスト> <送信先パス> [SSHポート]"
    echo ""
    echo "例:"
    echo "  $0 user@example.com /var/www/backup/uploads"
    echo "  $0 user@192.168.1.100 ~/backup 2222"
    exit 1
fi

REMOTE_HOST="$1"
REMOTE_PATH="$2"
SSH_PORT="${3:-$DEFAULT_PORT}"

# スクリプトのディレクトリを基準にuploadsフォルダを特定
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
UPLOADS_DIR="$PROJECT_DIR/uploads"

# uploadsフォルダの存在確認
if [ ! -d "$UPLOADS_DIR" ]; then
    echo "エラー: uploadsフォルダが見つかりません: $UPLOADS_DIR"
    exit 1
fi

echo "=========================================="
echo "追悼メッセージデータ同期"
echo "=========================================="
echo "送信元: $UPLOADS_DIR"
echo "送信先: $REMOTE_HOST:$REMOTE_PATH"
echo "SSHポート: $SSH_PORT"
echo ""

# 同期実行
echo "同期を開始します..."
scp -r -P "$SSH_PORT" "$UPLOADS_DIR/gallery.json" "$UPLOADS_DIR/gallery" "$REMOTE_HOST:$REMOTE_PATH/"

if [ $? -eq 0 ]; then
    echo ""
    echo "✓ 同期が完了しました"
    echo ""
    echo "転送されたファイル:"
    echo "  - gallery.json (メッセージデータ)"
    echo "  - gallery/ (写真・動画フォルダ)"
else
    echo ""
    echo "✗ 同期に失敗しました"
    exit 1
fi
