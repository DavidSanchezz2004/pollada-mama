<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/layout.php';
$user = require_login();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        verify_csrf();
        if ($user['role'] !== 'admin') {
            throw new RuntimeException('Solo admin puede cambiar configuración.');
        }
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'config') {
            $price = filter_input(INPUT_POST, 'cerveza_precio', FILTER_VALIDATE_FLOAT);
            $stock = filter_input(INPUT_POST, 'cerveza_stock_inicial', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 99999]]);
            if ($price === false || $price <= 0 || $stock === false) {
                throw new RuntimeException('Configuración inválida.');
            }
            set_config('cerveza_precio', number_format((float) $price, 2, '.', ''));
            set_config('cerveza_stock_inicial', (string) $stock);
            audit_log('config_updated', 'config', null, ['precio' => $price, 'stock' => $stock]);
            flash('success', 'Configuración actualizada.');
        } elseif ($action === 'backup') {
            create_backup('manual', $user);
            flash('success', 'Backup creado correctamente.');
        }
    } catch (Throwable $e) {
        app_log('summary_action_error', ['error' => $e->getMessage()]);
        flash('danger', $e->getMessage());
    }
    redirect('resumen.php');
}

$pollada = pollada_stats();
$beer = cerveza_stats();
$price = (float) get_config('cerveza_precio', '8');
$pendingPolladas = db()->query("SELECT item, nombre FROM polladas WHERE estado='PENDIENTE' ORDER BY item ASC")->fetchAll();
$donePolladas = db()->query("SELECT item, nombre, entregado_at, entregado_por_name FROM polladas WHERE estado='ENTREGADO' ORDER BY entregado_at DESC")->fetchAll();
page_start('Reporte Global', 'resumen', $user);
?>
<div class="container">
    <div class="d-flex align-center gap-2 mb-1"><div class="icon-box" style="background:rgba(105,74,56,0.1);color:var(--primary);">📦</div><h2 class="text-primary font-black" style="margin:0;">Resumen Entregas</h2></div>
    <div class="card">
        <div class="grid-2 mb-1"><div class="text-center"><div class="text-sm text-gray font-bold">Total</div><div class="font-black text-xl"><?= $pollada['total'] ?></div></div><div class="text-center"><div class="text-sm text-gray font-bold">Avance</div><div class="font-black text-xl text-primary"><?= $pollada['avance'] ?>%</div></div></div>
        <div class="progress-bar mb-1"><div class="progress-fill" style="width:<?= (int) $pollada['avance'] ?>%;"></div></div>
        <div class="grid-2 mt-1"><div class="text-center"><div class="text-sm text-gray font-bold">Entregadas</div><div class="font-black text-success text-xl"><?= $pollada['entregadas'] ?></div></div><div class="text-center"><div class="text-sm text-gray font-bold">Pendientes</div><div class="font-black text-warning text-xl"><?= $pollada['pendientes'] ?></div></div></div>
    </div>
    <div class="card">
        <div class="text-sm font-bold text-gray mb-1">Pendientes:</div>
        <?php if ($pendingPolladas): ?><ul class="text-sm text-gray" style="padding-left:1.25rem;line-height:1.6;"><?php foreach ($pendingPolladas as $p): ?><li>#<?= (int) $p['item'] ?> <?= e($p['nombre']) ?></li><?php endforeach; ?></ul><?php else: ?><p class="text-success font-bold">Todas entregadas.</p><?php endif; ?>
    </div>
    <div class="card">
        <div class="text-sm font-bold text-gray mb-1">Entregadas:</div>
        <?php if ($donePolladas): ?><ul class="text-sm text-gray" style="padding-left:1.25rem;line-height:1.6;"><?php foreach ($donePolladas as $p): ?><li>#<?= (int) $p['item'] ?> <?= e($p['nombre']) ?> - <?= e(date('H:i', strtotime((string) $p['entregado_at']))) ?> - <?= e($p['entregado_por_name']) ?></li><?php endforeach; ?></ul><?php else: ?><p class="text-gray">Aún no hay entregas.</p><?php endif; ?>
    </div>
    <div class="d-flex align-center gap-2 mt-1 mb-1" style="margin-top:2rem;"><div class="icon-box" style="background:rgba(105,74,56,0.1);color:var(--primary);">S/</div><h2 class="text-primary font-black" style="margin:0;">Resumen Ventas</h2></div>
    <div class="card">
        <div class="d-flex justify-between align-center mb-1"><span class="text-sm text-gray font-bold">Precio unitario:</span><span class="font-bold"><?= e(money($price)) ?></span></div>
        <div class="grid-3 mb-1"><div class="text-center"><div class="text-sm text-gray font-bold">Stock</div><div class="font-black text-xl"><?= $beer['stock_inicial'] ?></div></div><div class="text-center"><div class="text-sm text-gray font-bold">Vendidas</div><div class="font-black text-xl text-primary"><?= $beer['vendidas'] ?></div></div><div class="text-center"><div class="text-sm text-gray font-bold">Quedan</div><div class="font-black text-xl text-danger"><?= $beer['stock_restante'] ?></div></div></div>
        <hr style="border-top:1px solid var(--border);border-bottom:none;border-left:none;border-right:none;margin:1.5rem 0;">
        <div class="d-flex justify-between align-center mb-1"><span class="text-sm text-gray font-bold">Efectivo:</span><span class="font-black text-success"><?= e(money($beer['efectivo'])) ?></span></div>
        <div class="d-flex justify-between align-center mb-1"><span class="text-sm text-gray font-bold">Yape:</span><span class="font-black" style="color:var(--yape);"><?= e(money($beer['yape'])) ?></span></div>
        <div class="d-flex justify-between align-center mb-1"><span class="text-sm text-gray font-bold">Pendiente:</span><span class="font-black text-warning"><?= e(money($beer['pendiente'])) ?></span></div>
        <div class="d-flex justify-between align-center mb-1"><span class="text-sm text-gray font-bold">Anulado:</span><span class="font-black text-danger"><?= e(money($beer['total_anulado'])) ?></span></div>
        <hr style="border-top:1px solid var(--border);border-bottom:none;border-left:none;border-right:none;margin:1.5rem 0;">
        <div class="d-flex justify-between align-center mb-1"><span class="text-gray font-bold">Total cobrado:</span><span class="font-black text-success text-2xl"><?= e(money($beer['total_cobrado'])) ?></span></div>
        <div class="d-flex justify-between align-center"><span class="text-gray font-bold">Total vendido:</span><span class="font-black text-primary text-2xl"><?= e(money($beer['total_vendido'])) ?></span></div>
    </div>
    <?php if ($user['role'] === 'admin'): ?>
        <form method="post" class="card no-print"><?= csrf_field() ?><input type="hidden" name="action" value="config"><h3 class="card-title">Configuración admin</h3><label class="form-label">Precio cerveza</label><input class="form-control mb-1" type="number" step="0.01" min="0.1" name="cerveza_precio" value="<?= e((string) $price) ?>" required><label class="form-label">Stock inicial</label><input class="form-control mb-1" type="number" min="0" name="cerveza_stock_inicial" value="<?= (int) $beer['stock_inicial'] ?>" required><button class="btn btn-primary">Guardar configuración</button></form>
        <form method="post" class="no-print mb-1"><?= csrf_field() ?><input type="hidden" name="action" value="backup"><button class="btn btn-warning">Crear backup ahora</button></form>
    <?php endif; ?>
    <button id="btnPrint" class="btn btn-outline mb-1 w-100 no-print">Imprimir resumen</button>
    <a href="export-csv.php" class="btn btn-primary mb-1 w-100 no-print" style="text-decoration:none;">Descargar CSV</a>
</div>
<?php page_end('resumen'); ?>
