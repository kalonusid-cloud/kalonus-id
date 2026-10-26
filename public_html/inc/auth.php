<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

function start_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $cfg = require __DIR__ . '/config.php';
    session_name($cfg['app']['session_name']);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
    // idle timeout
    if (isset($_SESSION['last_activity']) &&
        time() - $_SESSION['last_activity'] > $cfg['app']['session_idle']) {
        session_unset(); session_destroy();
        session_start();
    }
    $_SESSION['last_activity'] = time();
}

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function require_login(string ...$roles): void {
    start_session();
    if (!current_user()) {
        redirect('/admin/login.php');
    }
    if ($roles && !in_array(current_user()['role'], $roles, true)) {
        http_response_code(403);
        exit('Akses ditolak.');
    }
}

function login(string $email, string $password): bool {
    $st = db()->prepare('SELECT * FROM users WHERE email=? AND is_active=1 LIMIT 1');
    $st->execute([$email]);
    $u = $st->fetch();
    if (!$u || !password_verify($password, $u['password_hash'])) return false;

    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => (int)$u['id'], 'name' => $u['name'],
        'email' => $u['email'], 'role' => $u['role'],
    ];
    db()->prepare('UPDATE users SET last_login_at=NOW() WHERE id=?')->execute([$u['id']]);
    audit('LOGIN', 'user', (string)$u['id']);
    return true;
}

function logout(): void {
    start_session();
    if (current_user()) audit('LOGOUT', 'user', (string)current_user()['id']);
    $_SESSION = [];
    session_destroy();
}