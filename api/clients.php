<?php
require_once dirname(__DIR__) . '/config.hrs.php';
requireAdminSession();
verifyCsrf(); // ★ CSRF検証（X-CSRF-TOKENヘッダー対応）

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $_GET['action'] ?? '';

match ($action) {
    'list'   => listClients(),
    'create' => createClient($body),
    'delete' => deleteClient($body),
    'update' => updateClient($body),
    default  => jsonResponse(['error' => '不明なアクション'], 400),
};

function listClients(): void {
    $db   = getDB();
    $stmt = $db->query("
        SELECT c.*, COUNT(m.id) AS message_count, MAX(m.created_at) AS last_activity,
               r.data AS req_data, r.estimate AS req_estimate
        FROM clients c
        LEFT JOIN messages m ON m.client_id = c.id AND m.role != 'admin'
        LEFT JOIN requirements r ON r.client_id = c.id
        GROUP BY c.id ORDER BY c.created_at DESC
    ");
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) {
        $r['req_data']     = $r['req_data']     ? json_decode($r['req_data'], true)     : null;
        $r['req_estimate'] = $r['req_estimate'] ? json_decode($r['req_estimate'], true) : null;
    }
    jsonResponse($rows);
}

function createClient(array $body): void {
    $name    = trim($body['name']    ?? '');
    $company = trim($body['company'] ?? '');
    $email   = trim($body['email']   ?? '');
    $note    = trim($body['note']    ?? '');
    if (!$name || !$company) jsonResponse(['error' => '氏名と会社名は必須です'], 400);
    $token   = generateToken();
    $expires = (new DateTime())->modify('+' . TOKEN_EXPIRE_DAYS . ' days')->format('Y-m-d H:i:s');
    $db = getDB();
    $db->prepare("INSERT INTO clients (name, company, email, token, note, expires_at) VALUES (?,?,?,?,?,?)")
       ->execute([$name, $company, $email, $token, $note, $expires]);
    jsonResponse(['ok' => true, 'id' => $db->lastInsertId(), 'token' => $token]);
}

function deleteClient(array $body): void {
    $id = intval($body['id'] ?? 0);
    if (!$id) jsonResponse(['error' => 'IDが必要です'], 400);
    getDB()->prepare("DELETE FROM clients WHERE id=?")->execute([$id]);
    jsonResponse(['ok' => true]);
}

function updateClient(array $body): void {
    $id = intval($body['id'] ?? 0);
    if (!$id) jsonResponse(['error' => 'IDが必要です'], 400);
    $db = getDB();
    if (!empty($body['status'])) $db->prepare("UPDATE clients SET status=? WHERE id=?")->execute([$body['status'], $id]);
    if (isset($body['note']))    $db->prepare("UPDATE clients SET note=? WHERE id=?")->execute([$body['note'], $id]);
    jsonResponse(['ok' => true]);
}
