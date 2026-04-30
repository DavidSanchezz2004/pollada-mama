<?php
require_once __DIR__ . '/../app/bootstrap.php';

if (current_user()) {
    redirect('dashboard.php');
}

$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        verify_csrf();
        if (attempt_login((string) ($_POST['username'] ?? ''), (string) ($_POST['password'] ?? ''))) {
            redirect('dashboard.php');
        }
        $error = 'Usuario o contraseña incorrectos.';
    } catch (Throwable $e) {
        app_log('login_error', ['error' => $e->getMessage()]);
        $error = 'Usuario o contraseña incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Login - Pollada Familiar</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .login-page { background: linear-gradient(135deg, #4A3326, var(--primary), var(--primary-light)); }
        .login-card { padding: 2.5rem 2rem; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); border: none; border-radius: 28px; position: relative; margin-top: 60px; }
        .login-avatar-container { position: absolute; top: -60px; left: 50%; transform: translateX(-50%); }
        .login-avatar { width: 120px; height: 120px; object-fit: cover; border-radius: 50%; box-shadow: 0 15px 25px -5px rgba(0,0,0,0.3); border: 6px solid var(--white); background: var(--white); }
        .login-input { padding-left: 18px !important; background: #f8fafc !important; border: 2px solid transparent !important; border-radius: 16px !important; height: 60px !important; }
    </style>
</head>
<body class="login-page">
    <div class="container w-100" style="max-width: 420px; padding: 1rem;">
        <div class="card text-center login-card">
            <div class="login-avatar-container"><img src="img-kira.png" alt="Kira" class="login-avatar"></div>
            <h1 class="font-black" style="color: var(--primary); font-size: 1.75rem; margin-bottom: 0.25rem; margin-top: 40px;">La Pollada de Kira</h1>
            <p class="text-gray mb-1" style="font-size: 0.95rem;">Sistema de Control y Entregas</p>
            <?php if ($error !== ''): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
            <form method="post" class="mt-1 text-left" style="margin-top: 2.5rem;">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label class="form-label">Usuario</label>
                    <input type="text" name="username" class="form-control login-input" autocomplete="username" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Contraseña</label>
                    <input type="password" name="password" class="form-control login-input" autocomplete="current-password" required>
                </div>
                <button type="submit" class="btn btn-primary mt-1 w-100" style="margin-top: 1.5rem; height: 64px; font-size: 1.125rem; border-radius: 16px;">Ingresar al panel</button>
            </form>
        </div>
    </div>
</body>
</html>
