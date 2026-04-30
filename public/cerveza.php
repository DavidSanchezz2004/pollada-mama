<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/layout.php';
$user = require_login();

function handle_cerveza_action(array $user): void
{
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    $pdo = db();
    if ($action === 'registrar') {
        $qty = filter_input(INPUT_POST, 'cantidad', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 99]]);
        $method = strtoupper((string) ($_POST['metodo_pago'] ?? ''));
        if (!$qty || !in_array($method, ['EFECTIVO', 'YAPE', 'PENDIENTE'], true)) {
            throw new RuntimeException('Venta inválida.');
        }
        $ref = clean_text((string) ($_POST['referencia'] ?? ''), 120);
        if ($method === 'PENDIENTE' && $ref === '') {
            throw new RuntimeException('La referencia es obligatoria.');
        }
        $price = (float) get_config('cerveza_precio', '8');
        $total = $qty * $price;
        $state = $method === 'PENDIENTE' ? 'PENDIENTE' : 'PAGADO';
        $stmt = $pdo->prepare('INSERT INTO cerveza_ventas (cantidad, precio_unitario, total, metodo_pago, estado, referencia, registrado_por_user_id, registrado_por_name, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$qty, $price, $total, $method, $state, $ref ?: null, (int) $user['id'], $user['name'], app_now()]);
        audit_log('cerveza_registrada', 'cerveza_ventas', (int) $pdo->lastInsertId());
        flash('success', 'Venta registrada.');
    } elseif ($action === 'pagar') {
        $id = filter_input(INPUT_POST, 'venta_id', FILTER_VALIDATE_INT);
        $method = strtoupper((string) ($_POST['metodo_pago'] ?? ''));
        if (!$id || !in_array($method, ['EFECTIVO', 'YAPE'], true)) {
            throw new RuntimeException('Pago inválido.');
        }
        $stmt = $pdo->prepare("UPDATE cerveza_ventas SET estado='PAGADO', metodo_pago=?, pagado_por_user_id=?, pagado_por_name=?, pagado_at=?, updated_at=? WHERE id=? AND estado='PENDIENTE'");
        $stmt->execute([$method, (int) $user['id'], $user['name'], app_now(), app_now(), $id]);
        audit_log('cerveza_pagada', 'cerveza_ventas', $id);
        flash('success', 'Pendiente marcado como pagado.');
    } elseif ($action === 'anular') {
        $id = filter_input(INPUT_POST, 'venta_id', FILTER_VALIDATE_INT);
        if (!$id) {
            throw new RuntimeException('Venta inválida.');
        }
        $stmt = $pdo->prepare("UPDATE cerveza_ventas SET estado='ANULADO', anulado_por_user_id=?, anulado_por_name=?, anulado_at=?, updated_at=? WHERE id=? AND estado!='ANULADO'");
        $stmt->execute([(int) $user['id'], $user['name'], app_now(), app_now(), $id]);
        audit_log('cerveza_anulada', 'cerveza_ventas', $id);
        flash('danger', 'Venta anulada.');
    } elseif ($action === 'precio' && $user['role'] === 'admin') {
        $price = filter_input(INPUT_POST, 'precio', FILTER_VALIDATE_FLOAT);
        if ($price === false || $price <= 0 || $price > 999) {
            throw new RuntimeException('Precio inválido.');
        }
        set_config('cerveza_precio', number_format((float) $price, 2, '.', ''));
        audit_log('config_updated', 'config', null, ['cerveza_precio' => $price]);
        flash('success', 'Precio actualizado.');
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        handle_cerveza_action($user);
    } catch (Throwable $e) {
        app_log('beer_action_error', ['error' => $e->getMessage()]);
        flash('danger', $e->getMessage());
    }
    redirect('cerveza.php');
}

$price = (float) get_config('cerveza_precio', '8');
$stats = cerveza_stats();
$sales = db()->query('SELECT * FROM cerveza_ventas ORDER BY id DESC LIMIT 10')->fetchAll();
$quantities = [1, 2, 3, 4, 6, 12];
page_start('Punto de Venta', 'cerveza', $user);
?>
<div class="container">
    <div class="d-flex justify-between align-center mb-1">
        <div class="font-black text-xl">Precio: <?= e(money($price)) ?></div>
        <button class="btn btn-small btn-outline" style="width:auto;padding:0 1rem;" onclick="showModal('precioModal')">Cambiar precio</button>
    </div>
    <div class="grid-2 mb-1">
        <div class="card text-center" style="padding:0.75rem;margin:0;"><div class="text-sm text-gray font-bold">Vendidas</div><div class="font-black text-2xl text-primary"><?= (int) $stats['vendidas'] ?></div></div>
        <div class="card text-center" style="padding:0.75rem;margin:0;background:var(--success);color:white;"><div class="text-sm font-bold" style="color:rgba(255,255,255,0.8);">Cobrado</div><div class="font-black text-2xl"><?= e(money($stats['total_cobrado'])) ?></div></div>
    </div>
    <div class="grid-3 mb-1">
        <div class="card text-center" style="padding:0.5rem;margin:0;border-bottom:3px solid var(--success);"><div class="text-sm text-gray font-bold">Efectivo</div><div class="font-bold text-success text-xl"><?= e(money($stats['efectivo'])) ?></div></div>
        <div class="card text-center" style="padding:0.5rem;margin:0;border-bottom:3px solid var(--yape);"><div class="text-sm text-gray font-bold">Yape</div><div class="font-bold text-xl" style="color:var(--yape);"><?= e(money($stats['yape'])) ?></div></div>
        <div class="card text-center" style="padding:0.5rem;margin:0;border-bottom:3px solid var(--warning);"><div class="text-sm text-gray font-bold">Pdte.</div><div class="font-bold text-warning text-xl"><?= e(money($stats['pendiente'])) ?></div></div>
    </div>
    <?php foreach (['EFECTIVO' => 'beer-efectivo', 'YAPE' => 'beer-yape', 'PENDIENTE' => 'beer-pdte'] as $method => $class): ?>
        <div class="mt-1">
            <div class="section-title <?= $method === 'YAPE' ? '' : ($method === 'PENDIENTE' ? 'text-warning' : 'text-success') ?>" style="<?= $method === 'YAPE' ? 'color:var(--yape);' : '' ?>"><?= $method === 'PENDIENTE' ? 'POR COBRAR (PENDIENTE)' : e($method) ?></div>
            <div class="beer-grid">
                <?php foreach ($quantities as $qty): $total = $qty * $price; ?>
                    <?php if ($method === 'PENDIENTE'): ?>
                        <button class="btn-beer <?= e($class) ?>" type="button" data-pending-qty="<?= $qty ?>" data-pending-total="<?= e(money($total)) ?>"><span class="qty"><?= $qty ?></span><span class="total"><?= e(money($total)) ?></span></button>
                    <?php else: ?>
                        <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="action" value="registrar"><input type="hidden" name="metodo_pago" value="<?= e($method) ?>"><input type="hidden" name="cantidad" value="<?= $qty ?>"><button class="btn-beer <?= e($class) ?>" type="submit"><span class="qty"><?= $qty ?></span><span class="total"><?= e(money($total)) ?></span></button></form>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
    <a href="cerveza-pendientes.php" class="btn btn-outline mb-1" style="border-color:var(--warning);color:var(--warning);text-decoration:none;">Ver pendientes de pago</a>
    <h3 class="mt-1 mb-1 text-dark font-black">Últimas ventas</h3>
    <div id="recentSalesList"><?php foreach ($sales as $sale) { render_sale_card($sale); } ?></div>
</div>
<div id="pendienteModal" class="modal-overlay"><form method="post" class="modal"><?= csrf_field() ?><input type="hidden" name="action" value="registrar"><input type="hidden" name="metodo_pago" value="PENDIENTE"><input type="hidden" name="cantidad" id="pendingQty"><h2 class="font-black text-warning mb-1">Registrar pendiente</h2><p class="text-gray mb-1 text-sm" id="pendingResume">Identifica a la persona o mesa.</p><div class="form-group" style="margin-top:1.5rem;"><input type="text" name="referencia" id="pendingRef" class="form-control" maxlength="120" placeholder="Ej: Mesa 2 / Carlos" required></div><button class="btn btn-warning w-100 mb-1">Guardar pendiente</button><button type="button" class="btn btn-outline w-100" onclick="hideModal('pendienteModal')">Cancelar</button></form></div>
<div id="precioModal" class="modal-overlay">
    <?php if ($user['role'] === 'admin'): ?>
        <form method="post" class="modal">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="precio">
            <h2 class="font-black text-primary mb-1">Cambiar precio</h2>
            <p class="text-gray mb-1 text-sm">El nuevo precio se usará en los botones rápidos desde ahora.</p>
            <input class="form-control mb-1" type="number" step="0.01" min="0.1" max="999" name="precio" value="<?= e((string) $price) ?>" required>
            <button class="btn btn-primary mb-1">Guardar precio</button>
            <button type="button" class="btn btn-outline" onclick="hideModal('precioModal')">Cancelar</button>
        </form>
    <?php else: ?>
        <div class="modal text-center">
            <h2 class="font-black text-warning mb-1">Solo admin</h2>
            <p class="text-gray mb-1">Para cambiar el precio entra con el usuario admin.</p>
            <button type="button" class="btn btn-primary" onclick="hideModal('precioModal')">Entendido</button>
        </div>
    <?php endif; ?>
</div>
<?php page_end('cerveza'); ?>

<?php function render_sale_card(array $sale): void { ?>
    <div class="card sale-card mb-1" style="padding:1rem;<?= $sale['estado'] === 'PENDIENTE' ? 'border-left:4px solid var(--warning);' : '' ?>">
        <div class="d-flex justify-between align-center mb-1"><span class="text-gray text-sm"><?= e(date('H:i', strtotime((string) $sale['created_at']))) ?></span><?= badge_for((string) ($sale['estado'] === 'PAGADO' ? $sale['metodo_pago'] : $sale['estado'])) ?></div>
        <div class="d-flex justify-between align-center"><span class="text-xl font-bold"><?= (int) $sale['cantidad'] ?> cervezas</span><span class="text-xl font-black"><?= e(money((float) $sale['total'])) ?></span></div>
        <?php if ($sale['referencia']): ?><div class="mt-1 text-sm text-dark font-bold">Ref: <?= e($sale['referencia']) ?></div><?php endif; ?>
        <div class="mt-1 text-sm text-gray mb-1">Registrado por: <?= e($sale['registrado_por_name']) ?></div>
        <?php if ($sale['estado'] === 'PENDIENTE'): ?>
            <div class="btn-row mt-1">
                <?php foreach (['EFECTIVO' => 'btn-success', 'YAPE' => 'btn-yape'] as $method => $btn): ?><form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="action" value="pagar"><input type="hidden" name="venta_id" value="<?= (int) $sale['id'] ?>"><input type="hidden" name="metodo_pago" value="<?= e($method) ?>"><button class="btn btn-small <?= e($btn) ?>"><?= e(ucfirst(strtolower($method))) ?></button></form><?php endforeach; ?>
                <form method="post" class="inline-form confirm-form" data-confirm="¿Seguro que deseas anular esta venta?"><?= csrf_field() ?><input type="hidden" name="action" value="anular"><input type="hidden" name="venta_id" value="<?= (int) $sale['id'] ?>"><button class="btn btn-small btn-outline" style="border-color:var(--danger);color:var(--danger);">Anular</button></form>
            </div>
        <?php elseif ($sale['estado'] === 'PAGADO'): ?>
            <form method="post" class="inline-form confirm-form" data-confirm="¿Seguro que deseas anular esta venta?"><?= csrf_field() ?><input type="hidden" name="action" value="anular"><input type="hidden" name="venta_id" value="<?= (int) $sale['id'] ?>"><button class="btn btn-small btn-outline w-100">Anular venta</button></form>
        <?php endif; ?>
    </div>
<?php } ?>
