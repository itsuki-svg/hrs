<?php
require_once dirname(__DIR__) . '/config.hrs.php';
secureSessionStart();

$token = trim($_GET['token'] ?? '');
$client = null;
$error  = null;

if (!$token) {
    $error = 'URLが正しくありません。担当者にお問い合わせください。';
} else {
    $db   = getDB();
    $stmt = $db->prepare("SELECT * FROM clients WHERE token=? AND (expires_at IS NULL OR expires_at > NOW())");
    $stmt->execute([$token]);
    $client = $stmt->fetch();
    if (!$client) $error = 'リンクが無効または有効期限が切れています。担当者にお問い合わせください。';
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<link rel="icon" href="/hrs/favicon.ico" type="image/x-icon">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $client ? htmlspecialchars($client['company']).' — ' : '' ?><?= APP_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@300;400;500;600&family=DM+Mono&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#f0f4f8;--white:#fff;--border:#e2e8f0;--border2:#f0f0f0;
  --text:#1a1a2e;--muted:#6b7280;--dim:#adb5bd;
  --blue:#185FA5;--blue-light:#f0f7ff;--blue-mid:#dbeafe;
}
body{font-family:'Noto Sans JP',sans-serif;background:var(--bg);color:var(--text);height:100vh;display:flex;flex-direction:column;overflow:hidden;font-size:14px}

/* Header */
header{padding:0 24px;height:52px;border-bottom:1px solid var(--border);background:var(--white);display:flex;align-items:center;gap:12px;flex-shrink:0;box-shadow:0 1px 3px rgba(0,0,0,.04)}
.header-logo{display:flex;align-items:center;gap:10px}
.logo-icon{width:30px;height:30px;background:var(--blue);border-radius:7px;display:flex;align-items:center;justify-content:center}
.logo-icon svg{width:16px;height:16px;fill:#fff}
.header-title{font-size:14px;font-weight:600;color:var(--text)}
.header-client{margin-left:auto;font-size:12px;color:var(--muted);font-family:'DM Mono',monospace}

/* Progress */
.progress-wrap{padding:10px 24px;background:var(--white);border-bottom:1px solid var(--border);flex-shrink:0}
.progress-track{height:4px;background:var(--border);border-radius:2px;overflow:hidden;margin-bottom:8px}
.progress-fill{height:100%;background:var(--blue);border-radius:2px;transition:width .5s ease;width:0%}
.phase-labels{display:flex;gap:4px;flex-wrap:wrap}
.phase-chip{font-size:10px;font-family:'DM Mono',monospace;padding:2px 8px;border-radius:20px;border:1px solid var(--border);color:var(--dim);transition:all .3s;white-space:nowrap;background:var(--bg)}
.phase-chip.done{border-color:#10b981;color:#15803d;background:#f0fdf4}
.phase-chip.active{border-color:var(--blue);color:var(--blue);background:var(--blue-light);font-weight:500}

/* Chat */
.chat-area{flex:1;display:flex;flex-direction:column;overflow:hidden}
.chat-scroll{flex:1;overflow-y:auto;padding:20px 24px;display:flex;flex-direction:column;gap:14px;scroll-behavior:smooth;background:var(--bg)}
.chat-scroll::-webkit-scrollbar{width:4px}
.chat-scroll::-webkit-scrollbar-thumb{background:var(--border);border-radius:2px}
.msg{display:flex;gap:10px;animation:fadeUp .25s ease}
@keyframes fadeUp{from{opacity:0;transform:translateY(5px)}to{opacity:1;transform:none}}
.msg.user{flex-direction:row-reverse}
.avatar{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0}
.av-ai{background:var(--blue);color:#fff}
.av-user{background:var(--white);color:var(--muted);border:1px solid var(--border);box-shadow:0 1px 3px rgba(0,0,0,.06)}
.bubble{max-width:72%;padding:12px 16px;border-radius:12px;font-size:14px;line-height:1.75;box-shadow:0 1px 3px rgba(0,0,0,.05)}
.msg.ai .bubble{background:var(--white);border:1px solid var(--border);border-top-left-radius:4px;color:var(--text)}
.msg.user .bubble{background:var(--blue);border:1px solid var(--blue);border-top-right-radius:4px;color:#fff}
.typing{display:flex;gap:4px;padding:2px 0}
.typing span{width:7px;height:7px;border-radius:50%;background:var(--dim);animation:bounce .9s infinite}
.typing span:nth-child(2){animation-delay:.15s}
.typing span:nth-child(3){animation-delay:.3s}
@keyframes bounce{0%,60%,100%{transform:none}30%{transform:translateY(-5px)}}

/* Input */
.input-area{padding:14px 24px;border-top:1px solid var(--border);background:var(--white);flex-shrink:0;display:flex;gap:10px;align-items:flex-end}
.input-area textarea{flex:1;background:var(--bg);border:1px solid var(--border);border-radius:10px;color:var(--text);font-family:'Noto Sans JP',sans-serif;font-size:14px;padding:10px 14px;resize:none;outline:none;max-height:120px;line-height:1.6;transition:border-color .15s,box-shadow .15s}
.input-area textarea:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(24,95,165,.08);background:var(--white)}
.input-area textarea::placeholder{color:var(--dim)}
.send-btn{width:42px;height:42px;border-radius:10px;background:var(--blue);border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .15s}
.send-btn:hover{background:#1450a0;transform:scale(1.03)}
.send-btn:active{transform:scale(.97)}
.send-btn:disabled{opacity:.4;cursor:not-allowed;transform:none}
.send-btn svg{width:17px;height:17px;fill:#fff}

/* Error & Complete */
.center-screen{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:40px;gap:16px;background:var(--bg)}
.big-icon{font-size:48px}
.error-text{font-size:14px;color:var(--muted);max-width:360px;line-height:1.7}
.complete-card{background:var(--white);border:1px solid var(--border);border-top:3px solid #10b981;border-radius:12px;padding:32px;max-width:440px;text-align:center;box-shadow:0 4px 16px rgba(0,0,0,.06)}
.complete-icon{font-size:40px;margin-bottom:12px}
.complete-title{font-size:18px;font-weight:600;color:var(--text);margin-bottom:8px}
.complete-sub{font-size:13px;color:var(--muted);line-height:1.7}
</style>
</head>
<body>

<header>
  <div class="header-logo">
    <div class="logo-icon">
      <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z"/></svg>
    </div>
    <div class="header-title"><?= APP_NAME ?></div>
  </div>
  <?php if ($client): ?>
  <div class="header-client"><?= htmlspecialchars($client['name']) ?> / <?= htmlspecialchars($client['company']) ?></div>
  <?php endif; ?>
</header>

<?php if ($error): ?>
<div class="center-screen">
  <div class="big-icon">🔒</div>
  <div class="error-text"><?= htmlspecialchars($error) ?></div>
</div>

<?php elseif ($client && $client['status'] === 'completed'): ?>
<div class="center-screen">
  <div class="complete-card">
    <div class="complete-icon">✅</div>
    <div class="complete-title">ヒアリング完了</div>
    <div class="complete-sub">ヒアリングはすでに完了しています。<br>担当者よりご連絡いたしますので、しばらくお待ちください。</div>
  </div>
</div>

<?php else: ?>
<div class="progress-wrap">
  <div class="progress-track"><div class="progress-fill" id="progress-fill"></div></div>
  <div class="phase-labels">
    <?php foreach (['基本情報','目的・目標','ターゲット','機能要件','デザイン','技術・予算','スケジュール','完了'] as $i => $p): ?>
    <div class="phase-chip" id="phase-<?=$i?>"><?= $p ?></div>
    <?php endforeach; ?>
  </div>
</div>

<div class="chat-area">
  <div class="chat-scroll" id="chat"></div>
  <div class="input-area">
    <textarea id="user-input" placeholder="こちらに入力してください..." rows="1"></textarea>
    <button class="send-btn" id="send-btn" onclick="send()">
      <svg viewBox="0 0 24 24"><path d="M2 21l21-9L2 3v7l15 2-15 2v7z"/></svg>
    </button>
  </div>
</div>

<script>
const TOKEN  = <?= json_encode($token) ?>;
const CLIENT = <?= json_encode(['name'=>$client['name'],'company'=>$client['company']]) ?>;
let currentPhase = 0;
let isLoading = false;

function addMsg(role, text) {
  const chat = document.getElementById('chat');
  const div  = document.createElement('div');
  div.className = `msg ${role}`;
  let clean = text.replace(/\[phase:\d+\]/g,'').trim();
  clean = clean.replace(/```json[\s\S]*?```/g,'').trim();
  clean = clean.replace(/\{[\s\S]*?"complete"[\s\S]*?\}/g,'').trim();
  clean = clean.replace(/\n{3,}/g,'\n\n').trim();
  div.innerHTML = `
    <div class="avatar ${role==='ai'?'av-ai':'av-user'}">${role==='ai'?'AI':'You'}</div>
    <div class="bubble">${clean.replace(/\n/g,'<br>')}</div>`;
  chat.appendChild(div);
  chat.scrollTop = chat.scrollHeight;
}

function addTyping() {
  const chat = document.getElementById('chat');
  const div  = document.createElement('div');
  div.id='typing'; div.className='msg ai';
  div.innerHTML=`<div class="avatar av-ai">AI</div><div class="bubble"><div class="typing"><span></span><span></span><span></span></div></div>`;
  chat.appendChild(div);
  chat.scrollTop = chat.scrollHeight;
}
function removeTyping() { document.getElementById('typing')?.remove(); }

function setPhase(n) {
  currentPhase = n;
  document.getElementById('progress-fill').style.width = Math.round((n/7)*100)+'%';
  for (let i=0;i<=7;i++) {
    const el = document.getElementById(`phase-${i}`);
    el.className = 'phase-chip' + (i<n?' done':i===n?' active':'');
  }
}

async function send() {
  const input = document.getElementById('user-input');
  const text  = input.value.trim();
  if (!text || isLoading) return;
  isLoading = true;
  document.getElementById('send-btn').disabled = true;
  addMsg('user', text);
  input.value=''; input.style.height='auto';
  addTyping();
  try {
    const res  = await fetch('/hrs/api/messages.php?action=send', {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({token:TOKEN, content:text, phase:currentPhase})
    });
    const data = await res.json();
    removeTyping();
    if (data.error) addMsg('ai','エラーが発生しました。もう一度お試しください。');
    else { addMsg('ai', data.response); setPhase(data.phase||0); if(data.complete) showComplete(); }
  } catch(e) { removeTyping(); addMsg('ai','エラーが発生しました。もう一度お試しください。'); }
  isLoading = false;
  document.getElementById('send-btn').disabled = false;
}

function showComplete() {
  document.querySelector('.input-area').style.display='none';
  const div = document.createElement('div');
  div.style.cssText='flex-shrink:0;padding:20px 24px;display:flex;justify-content:center;background:var(--bg)';
  div.innerHTML=`<div class="complete-card"><div class="complete-icon">✅</div><div class="complete-title">ヒアリング完了</div><div class="complete-sub">ご回答ありがとうございました。<br>担当者が内容を確認し、お見積もりをご連絡いたします。</div></div>`;
  document.querySelector('.chat-area').appendChild(div);
  setPhase(7);
}

document.getElementById('user-input').addEventListener('input', function() {
  this.style.height='auto'; this.style.height=Math.min(this.scrollHeight,120)+'px';
});
document.getElementById('user-input').addEventListener('keydown', e=>{
  if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();send();}
});

async function init() {
  isLoading=true; document.getElementById('send-btn').disabled=true; addTyping();
  try {
    const res  = await fetch('/hrs/api/messages.php?action=send', {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({token:TOKEN, content:'こんにちは、ヒアリングを開始してください。', phase:0})
    });
    const data = await res.json();
    removeTyping();
    if(data.response){addMsg('ai',data.response);setPhase(data.phase||0);}
  } catch(e) {
    removeTyping();
    addMsg('ai',`こんにちは！${CLIENT.name}様、Web制作のご検討ありがとうございます。まず御社の事業内容を教えていただけますか？ [phase:0]`);
  }
  isLoading=false; document.getElementById('send-btn').disabled=false;
}
init();
</script>
<?php endif; ?>
</body>
</html>
