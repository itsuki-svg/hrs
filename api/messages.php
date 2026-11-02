<?php
require_once dirname(__DIR__) . '/config.hrs.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';
$body   = json_decode(file_get_contents('php://input'), true) ?? [];

// admin_send と history はCSRF検証（admin認証済み）
// send はトークン認証のためCSRF不要
if (in_array($action, ['admin_send', 'history'])) {
    secureSessionStart();
    if (empty($_SESSION['admin_logged_in'])) jsonResponse(['error' => '認証が必要です'], 401);
    if ($action === 'admin_send') verifyCsrf();
}

match ($action) {
    'send'       => handleSend($body),
    'history'    => handleHistory(),
    'admin_send' => handleAdminSend($body),
    default      => jsonResponse(['error' => '不明なアクション'], 400),
};

function handleSend(array $body): void {
    $token   = $body['token'] ?? '';
    $content = trim($body['content'] ?? '');
    if (!$token || !$content) jsonResponse(['error' => 'パラメータ不足'], 400);
    $db   = getDB();
    $stmt = $db->prepare("SELECT * FROM clients WHERE token=? AND status='active' AND (expires_at IS NULL OR expires_at > NOW())");
    $stmt->execute([$token]);
    $client = $stmt->fetch();
    if (!$client) jsonResponse(['error' => 'トークンが無効または期限切れです'], 403);
    $clientId = $client['id'];
    $phase    = intval($body['phase'] ?? 0);
    $db->prepare("INSERT INTO messages (client_id, role, content, phase) VALUES (?,?,?,?)")->execute([$clientId, 'user', $content, $phase]);
    $hist = $db->prepare("SELECT role, content FROM messages WHERE client_id=? ORDER BY created_at DESC LIMIT 30");
    $hist->execute([$clientId]);
    $rows       = array_reverse($hist->fetchAll());
    $aiResponse = callClaudeAPI($rows, $client);
    $newPhase   = extractPhase($aiResponse);
    $db->prepare("INSERT INTO messages (client_id, role, content, phase) VALUES (?,?,?,?)")->execute([$clientId, 'assistant', $aiResponse, $newPhase]);
    saveRequirements($clientId, $aiResponse, $db);
    if ($newPhase >= 7) $db->prepare("UPDATE clients SET status='completed' WHERE id=?")->execute([$clientId]);
    jsonResponse(['response' => $aiResponse, 'phase' => $newPhase, 'complete' => $newPhase >= 7]);
}

function handleAdminSend(array $body): void {
    $clientId = intval($body['client_id'] ?? 0);
    $content  = trim($body['content'] ?? '');
    if (!$clientId || !$content) jsonResponse(['error' => 'パラメータ不足'], 400);
    getDB()->prepare("INSERT INTO messages (client_id, role, content, phase) VALUES (?,?,?,?)")->execute([$clientId, 'admin', $content, 0]);
    jsonResponse(['ok' => true]);
}

function handleHistory(): void {
    $clientId = intval($_GET['client_id'] ?? 0);
    $since    = $_GET['since'] ?? '2000-01-01 00:00:00';
    $db   = getDB();
    $stmt = $db->prepare("SELECT id, role, content, phase, created_at FROM messages WHERE client_id=? AND created_at > ? ORDER BY created_at ASC");
    $stmt->execute([$clientId, $since]);
    $req = $db->prepare("SELECT data, estimate FROM requirements WHERE client_id=?");
    $req->execute([$clientId]);
    $reqData = $req->fetch();
    jsonResponse([
        'messages'     => $stmt->fetchAll(),
        'requirements' => $reqData ? json_decode($reqData['data'] ?? '{}', true) : [],
        'estimate'     => $reqData ? json_decode($reqData['estimate'] ?? '{}', true) : [],
    ]);
}

function callClaudeAPI(array $history, array $client): string {
    $systemPrompt = <<<PROMPT
あなたはWebサイト制作会社のAIヒアリング担当です。クライアントの「{$client['name']}」様（{$client['company']}）から要件をヒアリングしています。
以下の7フェーズを順番に進めてください：
0: 基本情報（事業内容・サービス概要）
1: 目的・目標（サイトの目的、KPI）
2: ターゲット（ペルソナ、年齢層、地域）
3: 機能要件（必要な機能、ページ数）
4: デザイン（イメージ、参考サイト）
5: 技術・予算（CMS、予算感）
6: スケジュール（公開希望日）
ルール：一度に1〜2個の質問のみ。日本語で丁寧に。フェーズ番号を [phase:N] 形式で毎回末尾に付ける。
全フェーズ完了時：{"complete":true,"data":{"company":"","contact":"","business":"","purpose":"","kpi":"","target":"","persona":"","functions":[],"pages":[],"design":"","reference":"","budget":"","cms":"","schedule":"","notes":"","complexity":"low|medium|high"}} の後に [phase:7] を付ける。
PROMPT;
    $messages = array_map(fn($m) => ['role' => $m['role'] === 'admin' ? 'assistant' : $m['role'], 'content' => $m['content']], $history);
    $payload  = json_encode(['model' => 'claude-sonnet-4-6', 'max_tokens' => 1024, 'system' => $systemPrompt, 'messages' => $messages], JSON_UNESCAPED_UNICODE);
    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload, CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'x-api-key: ' . ANTHROPIC_API_KEY, 'anthropic-version: 2023-06-01'], CURLOPT_TIMEOUT => 30]);
    $res = curl_exec($ch); $err = curl_error($ch); curl_close($ch);
    if ($err) return 'APIエラーが発生しました。 [phase:0]';
    $data = json_decode($res, true);
    return $data['content'][0]['text'] ?? 'エラーが発生しました。 [phase:0]';
}

function extractPhase(string $text): int {
    if (preg_match('/\[phase:(\d+)\]/', $text, $m)) return (int)$m[1];
    return 0;
}

function saveRequirements(int $clientId, string $aiText, PDO $db): void {
    // JSONブロックを柔軟に抽出（改行・空白対応）
    if (!preg_match('/\{[\s\S]*?"complete"\s*:\s*true[\s\S]*?\}/s', $aiText, $m)) return;
    $json = json_decode($m[0], true);
    if (!$json || empty($json['data'])) return;
    // dataの各値をサニタイズ
    $json['data'] = array_map(fn($v) => is_string($v) ? trim($v) : $v, $json['data']);
    $data     = json_encode($json['data'], JSON_UNESCAPED_UNICODE);
    $estimate = json_encode(generateEstimate($json['data']), JSON_UNESCAPED_UNICODE);
    $db->prepare("INSERT INTO requirements (client_id, data, estimate) VALUES (?,?,?) ON DUPLICATE KEY UPDATE data=VALUES(data), estimate=VALUES(estimate)")->execute([$clientId, $data, $estimate]);
}

function generateEstimate(array $data): array {
    $complexity   = $data['complexity'] ?? 'medium';
    $pageCount    = max(count($data['pages'] ?? []), 5);
    $funcCount    = count($data['functions'] ?? []);
    $baseHours    = match($complexity) { 'low' => 40, 'high' => 160, default => 80 };
    $totalHours   = $baseHours + ($pageCount * 4) + ($funcCount * 8);
    $totalCost    = $totalHours * 10000;
    $businessDays = ceil($totalHours / 6);
    $d = new DateTime(); $added = 0;
    while ($added < $businessDays) { $d->modify('+1 day'); if (!in_array($d->format('N'), ['6','7'])) $added++; }
    return [
        'total_hours' => $totalHours, 'total_cost' => $totalCost,
        'cost_display' => number_format($totalCost) . '円〜',
        'delivery_days' => $businessDays, 'delivery_date' => $d->format('Y年m月d日'),
        'breakdown' => [
            ['item'=>'企画・設計',   'hours'=>(int)($totalHours*.15),'cost'=>(int)($totalCost*.15)],
            ['item'=>'デザイン',     'hours'=>(int)($totalHours*.30),'cost'=>(int)($totalCost*.30)],
            ['item'=>'コーディング', 'hours'=>(int)($totalHours*.35),'cost'=>(int)($totalCost*.35)],
            ['item'=>'機能実装',     'hours'=>(int)($totalHours*.15),'cost'=>(int)($totalCost*.15)],
            ['item'=>'テスト・調整', 'hours'=>(int)($totalHours*.05),'cost'=>(int)($totalCost*.05)],
        ],
        'options' => [
            ['name'=>'SEO基本対策','cost'=>50000],['name'=>'CMS導入','cost'=>80000],
            ['name'=>'多言語対応','cost'=>120000],['name'=>'月次保守プラン','cost'=>30000],
        ],
        'notes' => '※上記は概算です。詳細なヒアリング後に正式なお見積もりを提出いたします。',
        'generated_at' => date('Y-m-d H:i:s'),
    ];
}
