<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/layout.php';
$user = require_login();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    redirect('polladas.php');
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        verify_csrf();
        $pdo = db();
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT * FROM polladas WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $pollada = $stmt->fetch();
        if (!$pollada || $pollada['estado'] !== 'PENDIENTE') {
            $pdo->rollBack();
            flash('warning', 'Esta pollada ya fue entregada. No entregar nuevamente.');
            redirect('entregar-pollada.php?id=' . $id);
        }
        if (empty($_FILES['foto']) || ($_FILES['foto']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('La foto es obligatoria.');
        }
        if ((int) $_FILES['foto']['size'] > 5 * 1024 * 1024) {
            throw new RuntimeException('La foto supera 5MB.');
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file((string) $_FILES['foto']['tmp_name']);
        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => throw new RuntimeException('Formato de foto no permitido.'),
        };
        $name = bin2hex(random_bytes(16)) . '.' . $ext;
        $relative = $name;
        $target = APP_POLLADA_UPLOADS . DIRECTORY_SEPARATOR . $name;
        if (!move_uploaded_file((string) $_FILES['foto']['tmp_name'], $target)) {
            throw new RuntimeException('No se pudo guardar la foto.');
        }
        $obs = clean_text((string) ($_POST['observacion'] ?? ''), 180);
        $stmt = $pdo->prepare("UPDATE polladas SET estado='ENTREGADO', foto_path=?, observacion=?, entregado_por_user_id=?, entregado_por_name=?, entregado_at=?, updated_at=? WHERE id=? AND estado='PENDIENTE'");
        $stmt->execute([$relative, $obs ?: null, (int) $user['id'], $user['name'], app_now(), app_now(), $id]);
        $pdo->commit();
        audit_log('pollada_entregada', 'polladas', $id);
        flash('success', 'Pollada entregada correctamente.');
        redirect('polladas.php');
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        app_log('pollada_delivery_error', ['error' => $e->getMessage()]);
        flash('danger', $e->getMessage());
        redirect('entregar-pollada.php?id=' . $id);
    }
}

$stmt = db()->prepare('SELECT * FROM polladas WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$pollada = $stmt->fetch();
if (!$pollada) {
    redirect('polladas.php');
}
page_start('Entregar pollada', 'polladas', $user);
?>
<div class="container">
    <a href="polladas.php" class="btn btn-outline mb-1" style="text-decoration:none;">← Volver a polladas</a>
    <div class="card" style="border-left: 4px solid var(--<?= $pollada['estado'] === 'ENTREGADO' ? 'success' : 'warning' ?>);">
        <div class="d-flex justify-between align-center"><div class="text-gray font-bold text-sm">#<?= (int) $pollada['item'] ?></div><?= badge_for((string) $pollada['estado']) ?></div>
        <div class="font-black text-xl my-1"><?= e($pollada['nombre']) ?></div>
    </div>
    <?php if ($pollada['estado'] === 'ENTREGADO'): ?>
        <div class="alert alert-warning">Esta pollada ya fue entregada. No entregar nuevamente.</div>
        <div class="card">
            <div class="muted-box mb-1"><strong>Entregó:</strong> <?= e($pollada['entregado_por_name']) ?> · <?= e($pollada['entregado_at']) ?><br><strong>Obs:</strong> <?= e($pollada['observacion'] ?: '-') ?></div>
            <img class="photo-proof" src="ver-foto.php?id=<?= (int) $pollada['id'] ?>" alt="Comprobante">
        </div>
    <?php else: ?>
        <form method="post" enctype="multipart/form-data" class="card">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $pollada['id'] ?>">
            <h3 class="card-title">Comprobante de entrega</h3>
            <p class="text-sm text-gray mb-1">Toma una foto a la persona o DNI para confirmar.</p>
            <div class="form-group">
                <input type="file" name="foto" id="photoInput" accept="image/jpeg,image/png,image/webp" capture="environment" style="display:none;" required>
                <label for="photoInput" class="dropzone" id="photoPlaceholder" style="display:block;"><div class="font-bold text-dark">📸 Tocar para abrir cámara</div><div class="text-sm text-gray mt-1">JPG, PNG o WEBP · máximo 5MB</div></label>
                <div id="photoPreviewContainer" style="width:100%;height:240px;background:#e2e8f0;border-radius:16px;display:none;align-items:center;justify-content:center;overflow:hidden;position:relative;"><img id="photoPreview" src="" alt="Preview" style="width:100%;height:100%;object-fit:cover;"><button type="button" class="btn btn-small" style="position:absolute;bottom:10px;right:10px;width:auto;padding:0 1rem;background:rgba(0,0,0,0.7);color:white;" onclick="document.getElementById('photoInput').click()">Cambiar</button></div>
            </div>
            <div class="form-group mt-1">
                <label class="form-label">Observación (Opcional)</label>
                <div class="chips" style="flex-wrap:wrap;margin-bottom:0;">
                    <?php foreach (['Recogió ella misma', 'Recogió familiar', 'Confirmó por WhatsApp', 'Envió taxi/moto'] as $obs): ?>
                        <button type="button" class="chip obs-chip" data-value="<?= e($obs) ?>"><?= e($obs) ?></button>
                    <?php endforeach; ?>
                </div>
                <input type="text" name="observacion" id="observationInput" class="form-control" maxlength="180" placeholder="Otra observación">
            </div>
            <button type="submit" class="btn btn-success mt-1 text-xl">CONFIRMAR ENTREGA</button>
        </form>
    <?php endif; ?>
</div>
<?php page_end('polladas'); ?>
