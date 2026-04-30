<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este script solo debe ejecutarse por CLI.\n");
    exit(1);
}

$username = $argv[1] ?? '';
$password = $argv[2] ?? '';
if ($username === '' || $password === '') {
    fwrite(STDERR, "Uso: php scripts/change_password.php usuario NuevaClaveSegura123!\n");
    exit(1);
}
if (strlen($password) < 12) {
    fwrite(STDERR, "La contraseña debe tener al menos 12 caracteres.\n");
    exit(1);
}

$stmt = db()->prepare('UPDATE users SET password_hash = ?, failed_attempts = 0, locked_until = NULL, updated_at = ? WHERE username = ?');
$stmt->execute([password_hash($password, PASSWORD_DEFAULT), app_now(), $username]);
if ($stmt->rowCount() < 1) {
    fwrite(STDERR, "Usuario no encontrado.\n");
    exit(1);
}
audit_log('password_changed_cli', 'users', null, ['username' => $username]);
echo "Contraseña actualizada para {$username}.\n";
