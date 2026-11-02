<?php
require_once dirname(__DIR__) . '/config.hrs.php';
requireAdminSession();
$csrf = csrfToken();
$baseUrl = BASE_URL;
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<link rel="icon" href="/hrs/favicon.ico" type="image/x-icon">
<meta name="csrf-token" content="<?= htmlspecialchars($csrf) ?>">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>管理画面 — <?= APP_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#f0f4f8;--white:#fff;--border:#e2e8f0;--border2:#f0f0f0;
  --text:#1a1a2e;--muted:#6b7280;--dim:#adb5bd;
  --blue:#185FA5;--blue-light:#f0f7ff;--blue-mid:#dbeafe;
  --green:#065f46;--green-light:#f0fdf4;
  --purple:#5b21b6;--purple-light:#faf5ff;
  --amber:#92400e;--amber-light:#fffbeb;
  --red:#dc2626;--red-light:#fef2f2;
  --sidebar-w:210px;
}
body{font-family:'Noto Sans JP',sans-serif;background:var(--bg);color:var(--text);display:flex;height:100vh;overflow:hidden;font-size:13px}

/* Sidebar */
.sidebar{width:var(--sidebar-w);background:var(--white);border-right:1px solid var(--border);display:flex;flex-direction:column;flex-shrink:0}
.sidebar-logo{padding:16px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px}
.logo-icon{width:32px;height:32px;background:var(--blue);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.logo-icon svg{width:17px;height:17px;fill:#fff}
.logo-text{font-size:13px;font-weight:600;color:var(--text);line-height:1.2}
.logo-sub{font-size:10px;color:var(--dim);font-family:'DM Mono',monospace}
.nav{flex:1;padding:10px 0;overflow-y:auto}
.nav-section{padding:6px 18px 4px;font-size:10px;color:var(--dim);letter-spacing:.08em;text-transform:uppercase;font-family:'DM Mono',monospace}
.nav-item{display:flex;align-items:center;gap:9px;padding:8px 18px;font-size:13px;color:var(--muted);cursor:pointer;border-left:2px solid transparent;transition:all .15s}
.nav-item:hover{color:var(--text);background:#f8fafc}
.nav-item.active{color:var(--blue);border-left-color:var(--blue);background:var(--blue-light);font-weight:500}
.nav-icon{width:16px;text-align:center;font-size:15px}
.nav-badge{margin-left:auto;background:var(--blue);color:#fff;font-size:10px;font-family:'DM Mono',monospace;padding:1px 6px;border-radius:10px}
.sidebar-footer{padding:12px 18px;border-top:1px solid var(--border)}
.logout-btn{width:100%;padding:8px;background:none;border:1px solid var(--border);border-radius:7px;color:var(--muted);font-family:'Noto Sans JP',sans-serif;font-size:12px;cursor:pointer;transition:all .15s;display:flex;align-items:center;justify-content:center;gap:6px}
.logout-btn:hover{border-color:var(--red);color:var(--red);background:var(--red-light)}

/* Main */
.main{flex:1;display:flex;flex-direction:column;overflow:hidden}
.topbar{padding:0 24px;height:52px;border-bottom:1px solid var(--border);background:var(--white);display:flex;align-items:center;gap:12px;flex-shrink:0}
.topbar-title{font-size:15px;font-weight:600;color:var(--text)}
.topbar-right{margin-left:auto;display:flex;align-items:center;gap:8px}
.badge-chip{font-size:11px;font-family:'DM Mono',monospace;padding:3px 10px;border-radius:20px;border:1px solid var(--border);color:var(--muted);background:var(--bg)}
.badge-chip.live{border-color:#10b981;color:#10b981;background:#f0fdf4}
.content{flex:1;overflow-y:auto;padding:24px}
.content::-webkit-scrollbar{width:4px}
.content::-webkit-scrollbar-thumb{background:var(--border);border-radius:2px}

/* Stats grid */
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px}
.stat-card{background:var(--white);border:1px solid var(--border);border-radius:10px;padding:16px 18px;display:flex;align-items:center;gap:14px}
.stat-icon{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}
.stat-icon.blue{background:var(--blue-light)}
.stat-icon.green{background:var(--green-light)}
.stat-icon.purple{background:var(--purple-light)}
.stat-icon.amber{background:var(--amber-light)}
.stat-val{font-size:26px;font-weight:700;line-height:1;font-family:'DM Mono',monospace}
.stat-val.blue{color:var(--blue)}
.stat-val.green{color:var(--green)}
.stat-val.purple{color:var(--purple)}
.stat-val.amber{color:var(--amber)}
.stat-label{font-size:11px;color:var(--muted);margin-top:3px;letter-spacing:.04em}

/* Card */
.card{background:var(--white);border:1px solid var(--border);border-radius:10px;margin-bottom:16px;overflow:hidden}
.card-header{padding:14px 18px;border-bottom:1px solid var(--border2);display:flex;align-items:center;gap:8px}
.card-title{font-size:13px;font-weight:600;color:var(--text)}
.card-header-right{margin-left:auto;display:flex;gap:8px}

/* Table */
.tbl{width:100%;border-collapse:collapse}
.tbl th{font-size:11px;color:var(--muted);text-align:left;padding:9px 14px;border-bottom:1px solid var(--border);background:#fafbfc;letter-spacing:.04em;font-weight:500}
.tbl td{padding:10px 14px;border-bottom:1px solid var(--border2);vertical-align:middle}
.tbl tr:last-child td{border-bottom:none}
.tbl tbody tr:hover td{background:#fafbfc}

/* Badges */
.badge{display:inline-flex;align-items:center;font-size:11px;font-family:'DM Mono',monospace;padding:2px 8px;border-radius:20px;font-weight:500}
.badge-active{background:#dcfce7;color:#15803d;border:1px solid #bbf7d0}
.badge-completed{background:var(--blue-mid);color:var(--blue);border:1px solid #93c5fd}
.badge-expired{background:#fee2e2;color:var(--red);border:1px solid #fecaca}

/* Progress bar */
.prog-wrap{display:flex;align-items:center;gap:8px}
.prog-track{flex:1;height:5px;background:var(--border);border-radius:3px;overflow:hidden}
.prog-fill{height:100%;background:var(--blue);border-radius:3px;transition:width .4s}
.prog-pct{font-size:11px;font-family:'DM Mono',monospace;color:var(--muted);min-width:28px;text-align:right}

/* Buttons */
.btn{padding:7px 14px;border-radius:7px;border:none;cursor:pointer;font-family:'Noto Sans JP',sans-serif;font-size:12px;font-weight:500;transition:all .15s;display:inline-flex;align-items:center;gap:5px}
.btn-primary{background:var(--blue);color:#fff}.btn-primary:hover{background:#1450a0}
.btn-ghost{background:transparent;border:1px solid var(--border);color:var(--muted)}.btn-ghost:hover{border-color:var(--blue);color:var(--blue)}
.btn-danger{background:transparent;border:1px solid #fecaca;color:var(--red)}.btn-danger:hover{background:var(--red-light)}
.btn-sm{padding:4px 10px;font-size:11px}
.btn-amber{background:var(--amber-light);border:1px solid #fcd34d;color:var(--amber)}.btn-amber:hover{background:#fef3c7}

/* Token url */
.token-url{font-family:'DM Mono',monospace;font-size:11px;color:var(--muted);max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.copy-btn{font-size:10px;padding:2px 7px;background:var(--bg);border:1px solid var(--border);color:var(--muted);border-radius:4px;cursor:pointer;font-family:'DM Mono',monospace;transition:all .15s}
.copy-btn:hover{border-color:var(--blue);color:var(--blue)}

/* Monitor layout */
.monitor-grid{display:grid;grid-template-columns:240px 1fr 300px;gap:12px;height:calc(100vh - 100px)}
.mon-col{background:var(--white);border:1px solid var(--border);border-radius:10px;display:flex;flex-direction:column;overflow:hidden}
.mon-col-hdr{padding:12px 16px;border-bottom:1px solid var(--border);font-size:12px;font-weight:600;color:var(--text);display:flex;align-items:center;gap:6px;flex-shrink:0;background:#fafbfc}
.client-list-scroll{flex:1;overflow-y:auto}
.client-list-item{padding:10px 14px;border-bottom:1px solid var(--border2);cursor:pointer;transition:background .1s;display:flex;align-items:center;gap:10px}
.client-list-item:hover{background:#f8fafc}
.client-list-item.selected{background:var(--blue-light);border-left:2px solid var(--blue)}
.cli-name{font-size:13px;font-weight:500;color:var(--text)}
.cli-meta{font-size:11px;color:var(--muted);margin-top:1px;font-family:'DM Mono',monospace}
.status-dot{width:7px;height:7px;border-radius:50%;flex-shrink:0}
.dot-active{background:#10b981}
.dot-completed{background:var(--blue)}
.dot-expired{background:var(--red)}

/* Chat */
.chat-scroll{flex:1;overflow-y:auto;padding:14px;display:flex;flex-direction:column;gap:10px;background:#fafbfc}
.chat-scroll::-webkit-scrollbar{width:3px}
.chat-scroll::-webkit-scrollbar-thumb{background:var(--border)}
.msg{display:flex;gap:8px;animation:fadeUp .2s ease}
@keyframes fadeUp{from{opacity:0;transform:translateY(4px)}to{opacity:1;transform:none}}
.msg.user{flex-direction:row-reverse}
.avatar{width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;flex-shrink:0}
.av-ai{background:var(--blue);color:#fff}
.av-user{background:var(--bg);color:var(--muted);border:1px solid var(--border)}
.av-admin{background:#fcd34d;color:#92400e}
.bubble{max-width:78%;padding:9px 13px;border-radius:10px;font-size:12px;line-height:1.7}
.msg.ai .bubble,.msg.admin .bubble{background:var(--white);border:1px solid var(--border)}
.msg.user .bubble{background:var(--blue-light);border:1px solid var(--blue-mid)}
.msg.admin .bubble{background:var(--amber-light);border:1px solid #fcd34d;color:var(--amber)}
.msg-time{font-size:10px;color:var(--dim);font-family:'DM Mono',monospace;align-self:flex-end;margin-bottom:2px;white-space:nowrap}
.admin-input-area{padding:10px 14px;border-top:1px solid var(--border);display:flex;gap:8px;background:var(--white);flex-shrink:0}
.admin-input-area textarea{flex:1;background:var(--bg);border:1px solid var(--border);border-radius:7px;color:var(--text);font-family:'Noto Sans JP',sans-serif;font-size:12px;padding:7px 10px;resize:none;outline:none;transition:border-color .15s}
.admin-input-area textarea:focus{border-color:var(--amber)}
.admin-input-area textarea::placeholder{color:var(--dim)}

/* Req panel */
.req-scroll{flex:1;overflow-y:auto;padding:14px}
.req-scroll::-webkit-scrollbar{width:3px}
.req-scroll::-webkit-scrollbar-thumb{background:var(--border)}
.req-section{margin-bottom:14px}
.req-section-title{font-size:10px;font-family:'DM Mono',monospace;color:var(--muted);letter-spacing:.08em;text-transform:uppercase;margin-bottom:7px;border-bottom:1px solid var(--border2);padding-bottom:4px}
.req-item{background:var(--bg);border:1px solid var(--border);border-radius:6px;padding:7px 10px;margin-bottom:5px;font-size:12px;line-height:1.6}
.req-label{font-size:10px;color:var(--muted);margin-bottom:2px;font-family:'DM Mono',monospace}

/* Estimate */
.est-total{text-align:center;padding:14px;background:var(--blue-light);border:1px solid var(--blue-mid);border-radius:9px;margin-bottom:12px}
.est-amount{font-size:22px;font-weight:700;color:var(--blue);font-family:'DM Mono',monospace}
.est-date{font-size:11px;color:var(--muted);margin-top:4px}
.est-row{display:flex;justify-content:space-between;align-items:center;padding:5px 0;border-bottom:1px solid var(--border2);font-size:11px}
.est-row:last-child{border-bottom:none}
.est-row .item{color:var(--muted)}
.est-row .val{font-family:'DM Mono',monospace;color:var(--text);font-weight:500}

/* Modal */
.modal-overlay{position:fixed;inset:0;background:rgba(15,20,40,.4);display:flex;align-items:center;justify-content:center;z-index:1000;opacity:0;pointer-events:none;transition:opacity .2s}
.modal-overlay.show{opacity:1;pointer-events:all}
.modal{background:var(--white);border:1px solid var(--border);border-radius:14px;padding:28px;width:460px;max-width:95vw;box-shadow:0 8px 32px rgba(0,0,0,.12)}
.modal-title{font-size:16px;font-weight:600;margin-bottom:20px;color:var(--text)}

/* Form */
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px}
.form-full{margin-bottom:12px}
.field-label{display:block;font-size:11px;font-weight:500;color:var(--muted);margin-bottom:5px;letter-spacing:.04em}
.field-input{width:100%;background:var(--bg);border:1px solid var(--border);border-radius:7px;color:var(--text);font-family:'Noto Sans JP',sans-serif;font-size:13px;padding:8px 12px;outline:none;transition:border-color .15s}
.field-input:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(24,95,165,.08)}
.field-input::placeholder{color:var(--dim)}

/* Toast */
.toast{position:fixed;bottom:24px;right:24px;background:var(--white);border:1px solid var(--border);border-radius:8px;padding:10px 16px;font-size:12px;z-index:9999;transform:translateY(10px);opacity:0;transition:all .2s;pointer-events:none;box-shadow:0 4px 12px rgba(0,0,0,.1);display:flex;align-items:center;gap:8px}
.toast.show{transform:none;opacity:1}
.toast.success{border-color:#bbf7d0;color:#15803d}
.toast.error{border-color:#fecaca;color:var(--red)}

.empty-state{text-align:center;padding:40px 20px;color:var(--dim);font-size:12px}
.empty-icon{font-size:32px;margin-bottom:10px;opacity:.4}

/* Estimate tab */
.est-list-row{display:flex;align-items:center;gap:16px;padding:14px 0;border-bottom:1px solid var(--border2)}
.est-list-row:last-child{border-bottom:none}

/* Tabs */
.tabs{display:flex;gap:4px;background:var(--bg);border:1px solid var(--border);border-radius:8px;padding:3px;margin-bottom:18px}
.tab{flex:1;padding:7px;border-radius:6px;text-align:center;font-size:12px;color:var(--muted);cursor:pointer;transition:all .15s;font-weight:500}
.tab.active{background:var(--white);color:var(--blue);box-shadow:0 1px 3px rgba(0,0,0,.08)}
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon">
      <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z"/></svg>
    </div>
    <div>
      <div class="logo-text"><?= APP_NAME ?></div>
      <div class="logo-sub">ADMIN</div>
    </div>
  </div>
  <div class="nav">
    <div class="nav-section">メニュー</div>
    <div class="nav-item active" onclick="showTab('clients')" id="nav-clients">
      <span class="nav-icon">👥</span>顧客管理
    </div>
    <div class="nav-item" onclick="showTab('monitor')" id="nav-monitor">
      <span class="nav-icon">📡</span>リアルタイム監視
    </div>
    <div class="nav-item" onclick="showTab('estimate')" id="nav-estimate">
      <span class="nav-icon">💴</span>見積もり一覧
    </div>
  </div>
  <div class="sidebar-footer">
    <form method="post" action="/hrs/admin/logout.php">
      <button class="logout-btn" type="submit">
        <svg width="13" height="13" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd"/></svg>
        ログアウト
      </button>
    </form>
  </div>
</div>

<!-- Main -->
<div class="main">
  <div class="topbar">
    <div class="topbar-title" id="topbar-title">顧客管理</div>
    <div class="topbar-right">
      <span class="badge-chip live">● LIVE</span>
      <span class="badge-chip" id="client-count">読込中...</span>
    </div>
  </div>

  <!-- Clients Tab -->
  <div class="content" id="tab-clients">
    <div class="stats-grid" id="stats-grid">
      <div class="stat-card">
        <div class="stat-icon blue">👥</div>
        <div><div class="stat-val blue" id="s-total">—</div><div class="stat-label">総顧客数</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green">✅</div>
        <div><div class="stat-val green" id="s-active">—</div><div class="stat-label">ヒアリング中</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon blue">📋</div>
        <div><div class="stat-val blue" id="s-done">—</div><div class="stat-label">完了</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon amber">⏰</div>
        <div><div class="stat-val amber" id="s-exp">—</div><div class="stat-label">期限切れ</div></div>
      </div>
    </div>
    <div class="card">
      <div class="card-header">
        <div class="card-title">顧客一覧</div>
        <div class="card-header-right">
          <button class="btn btn-primary" onclick="openNewClientModal()">＋ 新規登録</button>
        </div>
      </div>
      <table class="tbl" id="client-table">
        <thead><tr>
          <th>顧客名 / 会社</th>
          <th>ステータス</th>
          <th>進捗</th>
          <th>最終活動</th>
          <th>アクセスURL</th>
          <th>操作</th>
        </tr></thead>
        <tbody id="client-tbody"></tbody>
      </table>
    </div>
  </div>

  <!-- Monitor Tab -->
  <div class="content" id="tab-monitor" style="display:none;padding:12px">
    <div class="monitor-grid">
      <div class="mon-col">
        <div class="mon-col-hdr">📋 顧客一覧</div>
        <div class="client-list-scroll" id="monitor-client-list"></div>
      </div>
      <div class="mon-col">
        <div class="mon-col-hdr" id="monitor-chat-title">💬 チャット — 顧客を選択</div>
        <div class="chat-scroll" id="monitor-chat"></div>
        <div class="admin-input-area">
          <textarea id="admin-msg-input" placeholder="追加質問を入力（Ctrl+Enterで送信）..." rows="2"></textarea>
          <button class="btn btn-amber btn-sm" onclick="sendAdminMessage()" style="align-self:flex-end">送信</button>
        </div>
      </div>
      <div class="mon-col">
        <div class="mon-col-hdr">📊 要件 / 見積もり</div>
        <div class="req-scroll" id="req-panel">
          <div class="empty-state"><div class="empty-icon">📋</div>顧客を選択すると<br>要件が表示されます</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Estimate Tab -->
  <div class="content" id="tab-estimate" style="display:none">
    <div class="card">
      <div class="card-header"><div class="card-title">見積もり一覧</div></div>
      <div style="padding:16px" id="estimate-list"></div>
    </div>
  </div>
</div>

<!-- New Client Modal -->
<div class="modal-overlay" id="new-client-modal">
  <div class="modal">
    <div class="modal-title">新規顧客登録</div>
    <div class="form-row">
      <div><label class="field-label">担当者名 *</label><input class="field-input" id="nc-name" placeholder="山田 太郎"></div>
      <div><label class="field-label">会社名 *</label><input class="field-input" id="nc-company" placeholder="株式会社〇〇"></div>
    </div>
    <div class="form-full">
      <label class="field-label">メールアドレス</label>
      <input class="field-input" id="nc-email" type="email" placeholder="taro@example.com">
    </div>
    <div class="form-full">
      <label class="field-label">メモ（内部用）</label>
      <textarea class="field-input" id="nc-note" rows="2" placeholder="案件の背景など..." style="resize:vertical"></textarea>
    </div>
    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:8px">
      <button class="btn btn-ghost" onclick="closeModal()">キャンセル</button>
      <button class="btn btn-primary" onclick="createClient()">登録してURL発行</button>
    </div>
  </div>
</div>

<!-- Token Result Modal -->
<div class="modal-overlay" id="token-modal">
  <div class="modal">
    <div class="modal-title">✅ 登録完了 — URLをクライアントに共有</div>
    <div style="background:var(--bg);border:1px solid var(--border);border-radius:8px;padding:14px;font-family:'DM Mono',monospace;font-size:12px;color:var(--blue);word-break:break-all;margin-bottom:16px" id="token-url-display"></div>
    <div style="display:flex;gap:10px;justify-content:flex-end">
      <button class="btn btn-ghost" onclick="document.getElementById('token-modal').classList.remove('show')">閉じる</button>
      <button class="btn btn-primary" onclick="copyTokenUrl()">URLをコピー</button>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>

<script>
const BASE = '<?= $baseUrl ?>';
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
const apiFetch = (url, opts={}) => {
  const headers = {'X-CSRF-TOKEN': CSRF, ...(opts.headers||{})};
  if (opts.body && !headers['Content-Type']) headers['Content-Type'] = 'application/json';
  return fetch(url, {...opts, headers});
};
let clients = [];
let selectedClientId = null;
let pollInterval = null;
let lastMessageTime = {};

function showTab(name) {
  ['clients','monitor','estimate'].forEach(t => {
    document.getElementById(`tab-${t}`).style.display = t === name ? '' : 'none';
    document.getElementById(`nav-${t}`).classList.toggle('active', t === name);
  });
  const titles = {clients:'顧客管理', monitor:'リアルタイム監視', estimate:'見積もり一覧'};
  document.getElementById('topbar-title').textContent = titles[name];
  if (name === 'monitor') renderMonitorList();
  if (name === 'estimate') renderEstimateList();
}

async function loadClients() {
  const res = await apiFetch('/hrs/api/clients.php?action=list');
  clients = await res.json();
  document.getElementById('s-total').textContent = clients.length;
  document.getElementById('s-active').textContent = clients.filter(c=>c.status==='active').length;
  document.getElementById('s-done').textContent = clients.filter(c=>c.status==='completed').length;
  document.getElementById('s-exp').textContent = clients.filter(c=>c.status==='expired').length;
  document.getElementById('client-count').textContent = clients.length + ' 件';

  const tbody = document.getElementById('client-tbody');
  if (!clients.length) {
    tbody.innerHTML = '<tr><td colspan="6"><div class="empty-state"><div class="empty-icon">👤</div>顧客がまだいません</div></td></tr>';
    return;
  }
  tbody.innerHTML = clients.map(c => {
    const url = `${BASE}/client/?token=${c.token}`;
    const badgeMap = {active:'badge-active',completed:'badge-completed',expired:'badge-expired'};
    const labelMap = {active:'ヒアリング中',completed:'完了',expired:'期限切れ'};
    const prog = c.status === 'completed' ? 100 : 0;
    const lastAct = c.last_activity ? new Date(c.last_activity).toLocaleString('ja-JP') : '—';
    return `<tr>
      <td><div style="font-weight:500;font-size:13px">${esc(c.name)}</div><div style="font-size:11px;color:var(--muted)">${esc(c.company)}</div></td>
      <td><span class="badge ${badgeMap[c.status]}">${labelMap[c.status]}</span></td>
      <td style="min-width:120px">
        <div class="prog-wrap">
          <div class="prog-track"><div class="prog-fill" style="width:${prog}%"></div></div>
          <span class="prog-pct">${prog}%</span>
        </div>
      </td>
      <td style="font-size:12px;color:var(--muted)">${lastAct}</td>
      <td>
        <div class="token-url" title="${url}">${url}</div>
        <button class="copy-btn" onclick="copyText('${url}',event)">コピー</button>
      </td>
      <td>
        <div style="display:flex;gap:6px">
          <button class="btn btn-ghost btn-sm" onclick="openMonitor(${c.id})">監視</button>
          <button class="btn btn-danger btn-sm" onclick="deleteClient(${c.id})">削除</button>
        </div>
      </td>
    </tr>`;
  }).join('');
}

function openNewClientModal() {
  ['nc-name','nc-company','nc-email','nc-note'].forEach(id => document.getElementById(id).value='');
  document.getElementById('new-client-modal').classList.add('show');
}
function closeModal() { document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('show')); }

async function createClient() {
  const name = document.getElementById('nc-name').value.trim();
  const company = document.getElementById('nc-company').value.trim();
  const email = document.getElementById('nc-email').value.trim();
  const note = document.getElementById('nc-note').value.trim();
  if (!name || !company) { showToast('氏名と会社名を入力してください','error'); return; }
  const res = await apiFetch('/hrs/api/clients.php?action=create', {method:'POST',body:JSON.stringify({name,company,email,note})});
  const data = await res.json();
  if (!data.ok) { showToast(data.error,'error'); return; }
  closeModal();
  document.getElementById('token-url-display').textContent = `${BASE}/client/?token=${data.token}`;
  document.getElementById('token-modal').classList.add('show');
  await loadClients();
  showToast('顧客を登録しました','success');
}

async function deleteClient(id) {
  if (!confirm('この顧客と全ての会話を削除しますか？')) return;
  await apiFetch('/hrs/api/clients.php?action=delete', {method:'POST',body:JSON.stringify({id})});
  await loadClients();
  showToast('削除しました');
}

function copyTokenUrl() {
  copyText(document.getElementById('token-url-display').textContent);
  closeModal();
}

function openMonitor(id) { showTab('monitor'); selectClient(id); }

function renderMonitorList() {
  const list = document.getElementById('monitor-client-list');
  if (!clients.length) { list.innerHTML = '<div class="empty-state"><div class="empty-icon">👤</div>顧客がいません</div>'; return; }
  list.innerHTML = clients.map(c => {
    const dotClass = {active:'dot-active',completed:'dot-completed',expired:'dot-expired'}[c.status];
    return `<div class="client-list-item ${selectedClientId===c.id?'selected':''}" onclick="selectClient(${c.id})">
      <div class="status-dot ${dotClass}"></div>
      <div style="flex:1;min-width:0">
        <div class="cli-name">${esc(c.name)}</div>
        <div class="cli-meta">${esc(c.company)}</div>
      </div>
    </div>`;
  }).join('');
}

async function selectClient(id) {
  selectedClientId = id;
  renderMonitorList();
  const client = clients.find(c => c.id === id);
  document.getElementById('monitor-chat-title').textContent = `💬 ${client?.name} — ${client?.company}`;
  document.getElementById('monitor-chat').innerHTML = '';
  lastMessageTime[id] = '2000-01-01 00:00:00';
  await pollMessages();
  if (pollInterval) clearInterval(pollInterval);
  pollInterval = setInterval(pollMessages, 3000);
}

async function pollMessages() {
  if (!selectedClientId) return;
  const since = lastMessageTime[selectedClientId] || '2000-01-01 00:00:00';
  const res = await apiFetch(`/hrs/api/messages.php?action=history&client_id=${selectedClientId}&since=${encodeURIComponent(since)}`);
  const data = await res.json();
  if (data.messages?.length) {
    data.messages.forEach(appendMessage);
    lastMessageTime[selectedClientId] = data.messages[data.messages.length-1].created_at;
  }
  if (data.requirements && Object.keys(data.requirements).length) renderRequirements(data.requirements, data.estimate);
}

function appendMessage(m) {
  const chat = document.getElementById('monitor-chat');
  const div = document.createElement('div');
  const role = m.role;
  div.className = `msg ${role === 'user' ? 'user' : role === 'admin' ? 'admin' : 'ai'}`;
  const avClass = {user:'av-user',assistant:'av-ai',admin:'av-admin'}[role] || 'av-ai';
  const avLabel = {user:'YOU',assistant:'AI',admin:'ADM'}[role] || 'AI';
  const time = new Date(m.created_at).toLocaleTimeString('ja-JP',{hour:'2-digit',minute:'2-digit'});
  const content = m.content.replace(/\[phase:\d+\]/g,'').replace(/\{"complete":true[\s\S]*?\}/g,'').trim();
  div.innerHTML = `
    <div class="avatar ${avClass}">${avLabel}</div>
    <div style="display:flex;flex-direction:column;gap:2px">
      <div class="bubble">${content.replace(/\n/g,'<br>')}</div>
      <div class="msg-time">${time}</div>
    </div>`;
  chat.appendChild(div);
  chat.scrollTop = chat.scrollHeight;
}

async function sendAdminMessage() {
  const input = document.getElementById('admin-msg-input');
  const content = input.value.trim();
  if (!content || !selectedClientId) return;
  input.value = '';
  const res = await apiFetch('/hrs/api/messages.php?action=admin_send', {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({client_id: selectedClientId, content})
  });
  const data = await res.json();
  if (data.ok) showToast('追加質問を送信しました','success');
}

function renderRequirements(req, est) {
  const panel = document.getElementById('req-panel');
  if (!req || !Object.keys(req).length) {
    panel.innerHTML = '<div class="empty-state"><div class="empty-icon">📋</div>ヒアリング完了後に表示されます</div>';
    return;
  }

  const now = new Date().toLocaleDateString('ja-JP');
  let html = `
    <div style="background:var(--blue);color:#fff;border-radius:8px;padding:14px 16px;margin-bottom:14px">
      <div style="font-size:11px;opacity:.8;font-family:'DM Mono',monospace;margin-bottom:4px">WEB制作 要件定義書</div>
      <div style="font-size:16px;font-weight:700">${esc(req.company||'—')}</div>
      <div style="font-size:11px;opacity:.8;margin-top:4px">作成日：${now} ／ 担当：${esc(req.contact||'—')}</div>
    </div>`;

  // セクション定義
  const sections = [
    { title:'1. 基本情報', icon:'🏢', rows:[
      ['会社名', req.company],
      ['担当者', req.contact],
      ['事業内容', req.business],
    ]},
    { title:'2. 目的・目標', icon:'🎯', rows:[
      ['制作目的', req.purpose],
      ['KPI・目標', req.kpi],
    ]},
    { title:'3. ターゲット', icon:'👥', rows:[
      ['ターゲット層', req.target],
      ['ペルソナ', req.persona],
    ]},
    { title:'4. 機能要件', icon:'⚙️', rows:[
      ['必要機能', Array.isArray(req.functions) ? req.functions : null, 'list'],
      ['ページ構成', Array.isArray(req.pages) ? req.pages : null, 'list'],
    ]},
    { title:'5. デザイン', icon:'🎨', rows:[
      ['デザインイメージ', req.design],
      ['参考サイト', req.reference],
    ]},
    { title:'6. 技術・予算', icon:'💴', rows:[
      ['予算', req.budget],
      ['CMS・技術', req.cms],
    ]},
    { title:'7. スケジュール', icon:'📅', rows:[
      ['公開希望日', req.schedule],
      ['備考・特記事項', req.notes],
    ]},
  ];

  for (const sec of sections) {
    let rows = sec.rows.filter(r => r[1] && (Array.isArray(r[1]) ? r[1].length : true));
    if (!rows.length) continue;
    html += `<div class="req-section">
      <div class="req-section-title">${sec.icon} ${sec.title}</div>
      <table style="width:100%;border-collapse:collapse;font-size:12px">`;
    for (const [label, val, type] of rows) {
      const display = type === 'list' && Array.isArray(val)
        ? val.map((v,i) => `<span style="display:inline-block;background:var(--blue-light);border:1px solid var(--blue-mid);border-radius:4px;padding:1px 7px;margin:2px 3px 2px 0;color:var(--blue);font-size:11px">${esc(v)}</span>`).join('')
        : esc(String(val||''));
      html += `<tr style="border-bottom:1px solid var(--border2)">
        <td style="padding:7px 8px;color:var(--muted);white-space:nowrap;vertical-align:top;width:80px;font-family:'DM Mono',monospace;font-size:10px">${label}</td>
        <td style="padding:7px 8px;line-height:1.6">${display}</td>
      </tr>`;
    }
    html += `</table></div>`;
  }

  // 見積もりセクション
  if (est && est.total_cost) {
    html += `<div class="req-section">
      <div class="req-section-title">💰 8. 自動見積もり</div>
      <div class="est-total">
        <div style="font-size:11px;color:var(--muted);margin-bottom:6px">概算金額（税別）</div>
        <div class="est-amount">${est.cost_display}</div>
        <div class="est-date">納期目安：${est.delivery_date}（${est.delivery_days}営業日）</div>
      </div>
      <table style="width:100%;border-collapse:collapse;font-size:12px;margin-bottom:8px">
        <tr style="background:#fafbfc"><th style="padding:6px 8px;text-align:left;font-size:10px;color:var(--muted);border-bottom:1px solid var(--border)">工程</th><th style="padding:6px 8px;text-align:right;font-size:10px;color:var(--muted);border-bottom:1px solid var(--border)">工数</th><th style="padding:6px 8px;text-align:right;font-size:10px;color:var(--muted);border-bottom:1px solid var(--border)">金額</th></tr>
        ${(est.breakdown||[]).map(b=>`<tr style="border-bottom:1px solid var(--border2)"><td style="padding:6px 8px">${b.item}</td><td style="padding:6px 8px;text-align:right;font-family:'DM Mono',monospace">${b.hours}h</td><td style="padding:6px 8px;text-align:right;font-family:'DM Mono',monospace">¥${Number(b.cost).toLocaleString()}</td></tr>`).join('')}
        <tr style="background:#f0f7ff;font-weight:600"><td style="padding:8px">合計</td><td style="padding:8px;text-align:right;font-family:'DM Mono',monospace">${est.total_hours}h</td><td style="padding:8px;text-align:right;font-family:'DM Mono',monospace;color:var(--blue)">¥${Number(est.total_cost).toLocaleString()}</td></tr>
      </table>`;
    if (est.options && est.options.length) {
      html += `<div style="font-size:11px;color:var(--muted);margin-bottom:6px;font-weight:500">オプション（参考）</div>
        <table style="width:100%;border-collapse:collapse;font-size:11px;margin-bottom:8px">
        ${est.options.map(o=>`<tr style="border-bottom:1px solid var(--border2)"><td style="padding:5px 8px;color:var(--muted)">${o.name}</td><td style="padding:5px 8px;text-align:right;font-family:'DM Mono',monospace">¥${Number(o.cost).toLocaleString()}</td></tr>`).join('')}
        </table>`;
    }
    html += `<div style="font-size:10px;color:var(--muted);margin-bottom:10px">${est.notes||''}</div>
      <button class="btn btn-primary" style="width:100%" onclick="exportEstimate(${selectedClientId})">📄 見積書を出力</button>
    </div>`;
  }

  panel.innerHTML = html;
}

function renderEstimateList() {
  const completed = clients.filter(c => c.status === 'completed' && c.req_estimate);
  const list = document.getElementById('estimate-list');
  if (!completed.length) { list.innerHTML = '<div class="empty-state"><div class="empty-icon">📄</div>完了した案件がまだありません</div>'; return; }
  list.innerHTML = completed.map(c => {
    const est = c.req_estimate;
    return `<div class="est-list-row">
      <div style="flex:1">
        <div style="font-weight:500">${esc(c.name)} — ${esc(c.company)}</div>
        <div style="font-size:11px;color:var(--muted);margin-top:2px;font-family:'DM Mono',monospace">${new Date(c.created_at).toLocaleDateString('ja-JP')}</div>
      </div>
      <div style="text-align:right">
        <div style="font-size:20px;font-weight:700;font-family:'DM Mono',monospace;color:var(--blue)">${est?.cost_display||'—'}</div>
        <div style="font-size:11px;color:var(--muted)">${est?.delivery_date||'—'}</div>
      </div>
      <button class="btn btn-ghost btn-sm" onclick="openMonitor(${c.id});showTab('monitor')">詳細</button>
    </div>`;
  }).join('');
}

function exportEstimate(clientId) {
  const client = clients.find(c => c.id === clientId);
  const est = client?.req_estimate; const req = client?.req_data;
  if (!est) return;
  const text = `=== Web制作 お見積書 ===\n発行日：${new Date().toLocaleDateString('ja-JP')}\n\n【お客様情報】\n会社名：${req?.company||client?.company||'—'}\nご担当者：${req?.contact||client?.name||'—'}\n\n【金額】\n合計：${est.cost_display}（税別）\n\n【内訳】\n${(est.breakdown||[]).map(b=>`${b.item.padEnd(12)}${b.hours}h  ¥${Number(b.cost).toLocaleString()}`).join('\n')}\n\n【納期】\n${est.delivery_days}営業日 / ${est.delivery_date}\n\n${est.notes}`;
  const a = document.createElement('a');
  a.href = URL.createObjectURL(new Blob([text],{type:'text/plain;charset=utf-8'}));
  a.download = `見積書_${client?.company}_${new Date().toLocaleDateString('ja-JP').replace(/\//g,'-')}.txt`;
  a.click();
}

function esc(str) { return String(str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function copyText(text, e) {
  navigator.clipboard.writeText(text);
  if (e) { const b=e.target; b.textContent='コピー済!'; setTimeout(()=>b.textContent='コピー',1500); }
  showToast('コピーしました','success');
}
function showToast(msg, type='') {
  const t = document.getElementById('toast');
  t.textContent = msg; t.className = `toast ${type} show`;
  setTimeout(()=>t.classList.remove('show'),2500);
}

document.querySelectorAll('.modal-overlay').forEach(m=>m.addEventListener('click',e=>{if(e.target===m)closeModal();}));
document.getElementById('admin-msg-input').addEventListener('keydown',e=>{if(e.key==='Enter'&&e.ctrlKey)sendAdminMessage();});

loadClients();
setInterval(loadClients, 15000);
</script>
</body>
</html>
