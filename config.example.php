<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'hrs_db');
define('DB_USER', 'hrs_user');
define('DB_PASS', 'your_password_here');
define('DB_CHARSET', 'utf8mb4');
define('ANTHROPIC_API_KEY', 'sk-ant-api03-xxxxxxxxxx-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', password_hash('your_admin_password', PASSWORD_DEFAULT));
define('APP_NAME', 'AI要件定義ヒアリング');
define('TOKEN_EXPIRE_DAYS', 30);
define('SESSION_LIFETIME', 3600);
define('BASE_URL',  'https://example.com/hrs');
define('BASE_PATH', '/hrs');
date_default_timezone_set('Asia/Tokyo');

function secureSessionStart(): void {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', 1);
    if (session_status() === PHP_SESSION_NONE) session_start();
}

function csrfToken(): string {
    secureSessionStart();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void {
    secureSessionStart();
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die(json_encode(['error' => 'CSRF検証失敗'], JSON_UNESCAPED_UNICODE));
    }
}

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

function initTables(): void {
    $db = getDB();
    $db->exec("
        CREATE TABLE IF NOT EXISTS clients (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            name        VARCHAR(100) NOT NULL,
            company     VARCHAR(100) NOT NULL,
            email       VARCHAR(200) NOT NULL DEFAULT '',
            token       CHAR(64) NOT NULL UNIQUE,
            status      ENUM('active','completed','expired') DEFAULT 'active',
            note        TEXT,
            created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
            expires_at  DATETIME,
            INDEX idx_token (token),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        CREATE TABLE IF NOT EXISTS messages (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            client_id   INT NOT NULL,
            role        ENUM('user','assistant','admin') NOT NULL,
            content     TEXT NOT NULL,
            phase       TINYINT DEFAULT 0,
            created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
            INDEX idx_client (client_id),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        CREATE TABLE IF NOT EXISTS requirements (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            client_id   INT NOT NULL UNIQUE,
            data        JSON,
            estimate    JSON,
            updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
}

function generateToken(): string { return bin2hex(random_bytes(32)); }

function jsonResponse(mixed $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function requireAdminSession(): void {
    secureSessionStart();
    if (empty($_SESSION['admin_logged_in'])) {
        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
            jsonResponse(['error' => '認証が必要です'], 401);
        }
        header('Location: ' . BASE_PATH . '/admin/login.php');
        exit;
    }
}

try { initTables(); } catch (Exception $e) {}
