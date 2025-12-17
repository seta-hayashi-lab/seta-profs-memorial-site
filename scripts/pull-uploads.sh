#!/bin/bash
#
# 追悼メッセージデータ取得スクリプト
# 指定したホストからuploadsデータをSCPで取得
#
# 使用方法:
#   ./pull-uploads.sh [取得元ホスト] [取得元パス] [SSHポート]
#
# 例:
#   ./pull-uploads.sh user@example.com /var/www/html/uploads 22
#   ./pull-uploads.sh user@192.168.1.100 ~/site/uploads
#

set -e

# デフォルト設定
DEFAULT_PORT=22

# 引数チェック
if [ $# -lt 2 ]; then
    echo "使用方法: $0 <取得元ホスト> <取得元パス> [SSHポート]"
    echo ""
    echo "例:"
    echo "  $0 user@example.com /var/www/html/uploads"
    echo "  $0 user@192.168.1.100 ~/site/uploads 2222"
    exit 1
fi

REMOTE_HOST="$1"
REMOTE_PATH="$2"
SSH_PORT="${3:-$DEFAULT_PORT}"

# スクリプトのディレクトリを基準にuploadsフォルダを特定
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
UPLOADS_DIR="$PROJECT_DIR/uploads"

# uploadsフォルダの存在確認（なければ作成）
if [ ! -d "$UPLOADS_DIR" ]; then
    mkdir -p "$UPLOADS_DIR"
fi

echo "=========================================="
echo "追悼メッセージデータ取得"
echo "=========================================="
echo "取得元: $REMOTE_HOST:$REMOTE_PATH"
echo "保存先: $UPLOADS_DIR"
echo "SSHポート: $SSH_PORT"
echo ""

# 取得実行
echo "取得を開始します..."
scp -r -P "$SSH_PORT" "$REMOTE_HOST:$REMOTE_PATH/gallery.json" "$UPLOADS_DIR/"
scp -r -P "$SSH_PORT" "$REMOTE_HOST:$REMOTE_PATH/gallery" "$UPLOADS_DIR/"

if [ $? -eq 0 ]; then
    echo ""
    echo "✓ 取得が完了しました"
    echo ""
    echo "取得されたファイル:"
    echo "  - gallery.json (メッセージデータ)"
    echo "  - gallery/ (写真・動画フォルダ)"
else
    echo ""
    echo "✗ 取得に失敗しました"
    exit 1
fi
