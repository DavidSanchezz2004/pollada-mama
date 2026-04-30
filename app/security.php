<?php
declare(strict_types=1);

function start_secure_session(): void
{
    if (PHP_SAPI === 'cli') {
        return;
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('pollada_session');
    session_start();
}

function send_security_headers(): void
{
    if (headers_sent()) {
        return;
    }
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(self), geolocation=(), microphone=()');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data: blob:; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; form-action 'self'; base-uri 'self'; frame-ancestors 'self'");
}

function app_log(string $message, array $context = []): void
{
    ensure_storage_directories();
    $line = '[' . app_now() . '] ' . $message;
    if ($context !== []) {
        $line .= ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    file_put_contents(APP_LOGS . DIRECTORY_SEPARATOR . 'app.log', $line . PHP_EOL, FILE_APPEND | LOCK_EX);
}
