# AI 要件定義ヒアリングシステム

Claude API を使い、クライアントへのヒアリングから要件定義書・見積書の自動生成までを行うシステムです。

## Features

- **AI ヒアリング** — Claude がフェーズに沿って自然な対話でクライアントから要件を引き出す
- **トークン URL** — クライアントごとに一意の URL を発行、メール等で共有するだけで利用開始
- **リアルタイム監視** — 管理者がクライアントのチャットをリアルタイムで確認可能
- **管理者介入** — 必要に応じて管理者が追加質問を送信（クライアントには AI からの質問として表示）
- **自動生成** — ヒアリング完了後、要件定義書と見積もりを自動生成しダウンロード可能
- **テーブル自動作成** — 初回アクセス時に MySQL テーブルを自動生成

## Hearing Flow

ヒアリングは5つのフェーズで進行します。Phase 0: 基本情報（会社名・事業内容）、Phase 1: 目的・背景（課題・ゴール）、Phase 2: 機能要件（具体的な機能の洗い出し）、Phase 3: 非機能要件（性能・セキュリティ・運用）、Phase 4: 確認・まとめ。クライアントがトークン URL でアクセスしてチャットを開始すると、`api/messages.php` がフェーズを判定し、Claude API に SSE ストリーミングでリクエストを送信します。管理者はリアルタイムでチャットを監視でき、追加質問を送信するとクライアントには AI からの質問として表示されます。全フェーズ完了後、要件定義書と見積もりを JSON で自動生成しダウンロード可能です。

## Database Schema

テーブルは初回アクセス時に `initTables()` で自動作成されます。

| テーブル | 概要 | 主なカラム |
|---------|------|-----------|
| `clients` | クライアント | id, name, company, email, token (CHAR 64 UNIQUE), status (active/completed/expired), expires_at |
| `messages` | チャット履歴 | id, client_id FK, role (user/assistant/admin), content, phase (0-4) |
| `requirements` | 要件定義データ | id, client_id FK UNIQUE, data (JSON), estimate (JSON) |

`clients` は `messages`（1対多）と `requirements`（1対1）にリレーションを持ちます。

## API Endpoints

| Method | Path | 認証 | 概要 |
|--------|------|------|------|
| `POST` | `/api/messages.php?action=send` | Token | クライアントメッセージ送信 → Claude 応答 (SSE) |
| `GET` | `/api/messages.php?action=history` | Token | チャット履歴取得 |
| `POST` | `/api/messages.php?action=generate` | Admin | 要件定義書・見積もり生成 |
| `GET` | `/api/clients.php?action=list` | Admin | クライアント一覧 |
| `POST` | `/api/clients.php?action=create` | Admin | 新規クライアント作成（トークン発行） |
| `POST` | `/api/clients.php?action=admin_msg` | Admin | 管理者からの追加質問 |

## Screen Flow

管理者がクライアントを新規作成しトークン URL を発行します。クライアントはその URL で AI チャットを開始し、Phase 0〜4 まで質問に回答します。管理者はリアルタイムでチャットを監視し、必要に応じて追加質問を送信できます（AI からの質問として表示）。全フェーズ完了後、管理者が要件定義書と見積もりを生成・ダウンロードします。

## Security

| 項目 | 実装 |
|------|------|
| クライアント認証 | 64文字ランダムトークン (`bin2hex(random_bytes(32))`) |
| 管理者認証 | セッション + bcrypt パスワード |
| CSRF | セッショントークン検証 |
| トークン期限 | `TOKEN_EXPIRE_DAYS` で有効期限管理 |
| セッション | httponly, secure, samesite=Strict |

## Tech Stack

| 項目 | 内容 |
|------|------|
| Backend | PHP 8.0+ |
| Database | MySQL 5.7+ |
| AI | Claude API (Anthropic) — SSE ストリーミング |
| Security | CSRF トークン, bcrypt, セッション管理 |

## Directory Structure

```
hrs/
├── config.hrs.php       # 設定・DB接続・テーブル定義（config.example.php を参照）
├── admin/
│   ├── login.php        # 管理者ログイン
│   └── index.php        # 管理画面（顧客一覧・リアルタイム監視）
├── api/
│   ├── messages.php     # メッセージ API（Claude 連携・見積もり生成）
│   └── clients.php      # 顧客 CRUD API
└── client/
    └── index.php        # 顧客向けヒアリング画面
```

## Setup

1. `config.example.php` を `config.hrs.php` にコピーし、DB 情報と Anthropic API キーを設定
2. 管理者パスワードを変更
3. 初回アクセスでテーブルが自動作成されます
