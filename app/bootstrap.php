<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/backup_manager.php';

date_default_timezone_set('America/Lima');
start_secure_session();
send_security_headers();

try {
    db();
} catch (Throwable $e) {
    app_log('bootstrap_error', ['error' => $e->getMessage()]);
    if (PHP_SAPI === 'cli') {
        throw $e;
    }
    http_response_code(500);
    echo '<h1>Error de configuración</h1><p>No se pudo iniciar la base de datos. Revisa que pdo_sqlite esté habilitado.</p>';
    exit;
}
