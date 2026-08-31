<?php
require_once dirname(__DIR__) . '/config.hrs.php';
secureSessionStart();
$csrf = csrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';
    if ($user === ADMIN_USER && password_verify($pass, ADMIN_PASS)) {
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        header('Location: /hrs/admin/index.php');
        exit;
    }
    $error = 'ユーザー名またはパスワードが間違っています';
}
if (!empty($_SESSION['admin_logged_in'])) { header('Location: /hrs/admin/index.php'); exit; }
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<link rel="icon" href="/hrs/favicon.ico" type="image/x-icon">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>管理者ログイン — <?= APP_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@300;400;500;600&family=DM+Mono&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Noto Sans JP',sans-serif;background:#f0f4f8;color:#1a1a2e;min-height:100vh;display:flex;align-items:center;justify-content:center}
.wrap{width:100%;max-width:400px;padding:20px}
.logo-area{text-align:center;margin-bottom:28px}
.logo-icon{width:48px;height:48px;background:#185FA5;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:12px}
.logo-icon svg{width:26px;height:26px;fill:#fff}
.logo-title{font-size:18px;font-weight:600;color:#1a1a2e}
.logo-sub{font-size:12px;color:#888;margin-top:2px;font-family:'DM Mono',monospace}
.card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:32px;box-shadow:0 1px 4px rgba(0,0,0,.06)}
.field{margin-bottom:16px}
.field label{display:block;font-size:12px;font-weight:500;color:#555;margin-bottom:6px}
.field input{width:100%;border:1px solid #dde1e7;border-radius:8px;padding:10px 14px;font-size:14px;font-family:'Noto Sans JP',sans-serif;color:#1a1a2e;outline:none;transition:border-color .2s,box-shadow .2s;background:#fafbfc}
.field input:focus{border-color:#185FA5;box-shadow:0 0 0 3px rgba(24,95,165,.1);background:#fff}
.btn{width:100%;padding:11px;background:#185FA5;border:none;border-radius:8px;color:#fff;font-family:'Noto Sans JP',sans-serif;font-size:14px;font-weight:500;cursor:pointer;transition:background .2s;margin-top:4px}
.btn:hover{background:#1450a0}
.error{background:#fef2f2;border:1px solid #fecaca;border-radius:8px;color:#dc2626;font-size:13px;padding:10px 14px;margin-bottom:16px}
.footer-note{text-align:center;font-size:11px;color:#aaa;margin-top:20px;font-family:'DM Mono',monospace}
</style>
</head>
<body>
<div class="wrap">
  <div class="logo-area">
    <div class="logo-icon"><svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z"/></svg></div>
    <div class="logo-title"><?= APP_NAME ?></div>
    <div class="logo-sub">ADMIN PANEL</div>
  </div>
  <div class="card">
    <?php if (!empty($error)): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <div class="field"><label>ユーザー名</label><input type="text" name="username" autocomplete="username" required></div>
      <div class="field"><label>パスワード</label><input type="password" name="password" autocomplete="current-password" required></div>
      <button type="submit" class="btn">ログイン</button>
    </form>
  </div>
  <div class="footer-note"><?= APP_NAME ?> &copy; <?= date('Y') ?></div>
</div>
</body>
</html>
