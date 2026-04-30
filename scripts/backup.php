<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

try {
    $path = create_backup('automatico', null);
    echo 'Backup creado: ' . $path . PHP_EOL;
    exit(0);
} catch (Throwable $e) {
    app_log('backup_cli_failed', ['error' => $e->getMessage()]);
    fwrite(STDERR, 'Error creando backup: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
