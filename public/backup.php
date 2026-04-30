<?php
require_once __DIR__ . '/../app/bootstrap.php';
$user = require_admin();
require_post();
try {
    verify_csrf();
    create_backup('manual', $user);
    flash('success', 'Backup creado correctamente.');
} catch (Throwable $e) {
    flash('danger', $e->getMessage());
}
redirect('resumen.php');
