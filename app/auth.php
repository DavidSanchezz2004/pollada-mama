<?php
declare(strict_types=1);

function current_user(bool $requireActive = true): ?array
{
    start_secure_session();
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $stmt = db()->prepare('SELECT id, name, username, role, is_active FROM users WHERE id = ?');
    $stmt->execute([(int) $_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user || ($requireActive && (int) $user['is_active'] !== 1)) {
        unset($_SESSION['user_id']);
        return null;
    }
    return $user;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        redirect('login.php');
    }
    return $user;
}

function require_admin(): array
{
    $user = require_login();
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        exit('Acceso denegado');
    }
    return $user;
}

function attempt_login(string $username, string $password): bool
{
    $username = clean_text(mb_strtolower($username, 'UTF-8'), 50);
    $stmt = db()->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    $now = app_now();
    $generic = false;

    if (!$user || (int) $user['is_active'] !== 1) {
        audit_log('login_failed', 'users', null, ['username' => $username]);
        password_verify($password, password_hash('constant-time-padding', PASSWORD_DEFAULT));
        return false;
    }

    if (!empty($user['locked_until']) && strtotime((string) $user['locked_until']) > time()) {
        audit_log('login_failed', 'users', (int) $user['id'], ['reason' => 'locked']);
        return false;
    }

    if (!password_verify($password, (string) $user['password_hash'])) {
        $attempts = (int) $user['failed_attempts'] + 1;
        $lockedUntil = $attempts >= 5 ? (new DateTimeImmutable('+10 minutes', new DateTimeZone('America/Lima')))->format('Y-m-d H:i:s') : null;
        $stmt = db()->prepare('UPDATE users SET failed_attempts = ?, locked_until = ?, updated_at = ? WHERE id = ?');
        $stmt->execute([$attempts, $lockedUntil, $now, (int) $user['id']]);
        audit_log('login_failed', 'users', (int) $user['id'], ['attempts' => $attempts]);
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $stmt = db()->prepare('UPDATE users SET failed_attempts = 0, locked_until = NULL, last_login_at = ?, updated_at = ? WHERE id = ?');
    $stmt->execute([$now, $now, (int) $user['id']]);
    audit_log('login_success', 'users', (int) $user['id']);
    return true;
}

function logout_user(): void
{
    audit_log('logout');
    start_secure_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
    }
    session_destroy();
}
