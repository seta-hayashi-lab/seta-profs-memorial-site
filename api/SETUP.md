# Slack API 設定手順

追悼サイトのフォームから投稿されたメッセージ・画像を Slack チャンネルに送信するための設定手順です．

## 概要

```
ファイル構成:
.
├── .env.example     # 環境設定のサンプル（Git管理対象）
├── .env             # 実際の環境設定（Git管理対象外・要作成）
└── api/
    ├── config.php   # .env を読み込む設定ファイル
    └── slack-post.php # Slack投稿API
```

## 1. Slack App の作成

1. [Slack API](https://api.slack.com/apps) にアクセス
2. **Create New App** をクリック
3. **From scratch** を選択
4. App Name: `追悼サイト投稿通知`（任意の名前）
5. Workspace: 投稿先のワークスペースを選択
6. **Create App** をクリック

## 2. Bot User の有効化

1. 左メニューの **App Home** をクリック
2. **App Display Name** セクションで **Edit** をクリック
3. 以下を設定:
   - **Display Name (Bot Name)**: `追悼サイト通知`（任意の名前）
   - **Default username**: `memorial-bot`（任意）
4. **Save** をクリック

> **注意**: この手順を行わないと「ボットを使用するための設定がされていません」エラーが発生します

## 3. Bot Token Scopes の設定

1. 左メニューの **OAuth & Permissions** をクリック
2. **Scopes** セクションまでスクロール
3. **Bot Token Scopes** で以下を追加:
   - `chat:write` - メッセージ投稿用
   - `files:write` - ファイルアップロード用

## 4. App をワークスペースにインストール

1. 同じページの上部にある **Install to Workspace** をクリック
2. 権限を確認して **許可する** をクリック
3. **Bot User OAuth Token** をコピー（`xoxb-` で始まる文字列）

## 5. チャンネル ID の取得

1. Slack で投稿先チャンネルを開く
2. チャンネル名をクリック → **チャンネル詳細を表示**
3. 一番下にある **チャンネル ID** をコピー（`C` で始まる文字列）

または，チャンネルのリンクをコピーして，URL の末尾から ID を取得:
```
https://xxxxx.slack.com/archives/C01XXXXXXXX
                                  ^^^^^^^^^^^^ これがチャンネル ID
```

## 6. Bot をチャンネルに追加

1. 投稿先チャンネルで `/invite @アプリ名` を実行

または:
1. チャンネルの設定 → **インテグレーション** → **アプリを追加する**
2. 作成した App を選択

## 7. 環境設定ファイルの作成

1. プロジェクトルートで `.env.example` を `.env` としてコピー:
   ```bash
   cp .env.example .env
   ```

2. `.env` を編集して実際の値を設定:
   ```bash
   # Slack Bot Token（手順3で取得）
   SLACK_BOT_TOKEN=xoxb-XXXX-XXXX-XXXX

   # チャンネル ID（手順4で取得）
   SLACK_CHANNEL_ID=C01XXXXXXXX

   # 本番環境では適切なドメインを指定
   ALLOWED_ORIGIN=https://your-domain.com
   ```

> **重要**: `.env` は `.gitignore` に含まれているため Git にはコミットされません．
> 機密情報が漏洩しないよう，`.env` ファイルの取り扱いには十分注意してください．

## 8. サーバー要件

- PHP 7.4 以上
- cURL 拡張が有効
- `file_uploads` が有効
- `upload_max_filesize` が 1GB 以上
- `post_max_size` が 1.1GB 以上
- `max_execution_time` を十分に長く設定（大容量ファイル用）

### php.ini の設定例
```ini
file_uploads = On
upload_max_filesize = 1G
post_max_size = 1200M
max_file_uploads = 20
max_execution_time = 600
max_input_time = 600
memory_limit = 256M
```

> **MAMP の場合**: MAMP > Preferences > PHP > php.ini を編集し，Apache を再起動

## 9. 動作確認

1. サイトにアクセス
2. 追悼メッセージページでテストメッセージを送信
3. Slack チャンネルにメッセージが投稿されることを確認
4. ギャラリーページでテスト画像・動画をアップロード（複数選択可）
5. Slack チャンネルにファイルが投稿されることを確認

## トラブルシューティング

### 「.env ファイルが見つかりません」エラー

1. `.env.example` を `.env` としてコピーしたか確認
2. `.env` がドキュメントルート（`index.html` と同じディレクトリ）にあるか確認

### メッセージが投稿されない

1. `.env` の `SLACK_BOT_TOKEN` が正しいか確認
2. `.env` の `SLACK_CHANNEL_ID` が正しいか確認
3. Bot がチャンネルに参加しているか確認
4. PHP のエラーログを確認

### ファイルがアップロードされない

1. ファイルサイズが 1GB 以下か確認
2. ファイル形式が対応形式か確認（画像: JPEG/PNG/GIF/WebP，動画: MP4/WebM/MOV/AVI）
3. PHP の `upload_max_filesize`（1G以上）と `post_max_size`（1200M以上）設定を確認
4. 大容量ファイルの場合は `max_execution_time` と `max_input_time` を確認
5. Slack の `files:write` 権限が付与されているか確認
6. 複数ファイルの場合は `max_file_uploads` 設定を確認

### CORS エラーが発生する

`.env` の `ALLOWED_ORIGIN` を適切に設定:
```bash
ALLOWED_ORIGIN=https://your-domain.com
```

## セキュリティ注意事項

- `.env` ファイルは **絶対に公開リポジトリにコミットしない**
- `.env` ファイルのパーミッションは `600` または `640` を推奨
- 本番環境では `ALLOWED_ORIGIN` を特定のドメインに制限する
- 必要に応じてレート制限を実装する

## 設定ファイル一覧

| ファイル | Git管理 | 説明 |
|---------|--------|------|
| `.env.example` | 対象 | 環境設定のサンプル |
| `.env` | **対象外** | 実際の環境設定（機密情報を含む） |
| `api/config.php` | 対象 | .env を読み込む設定ファイル |
| `api/slack-post.php` | 対象 | Slack投稿API |
