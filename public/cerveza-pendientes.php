<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/layout.php';
$user = require_login();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        verify_csrf();
        $id = filter_input(INPUT_POST, 'venta_id', FILTER_VALIDATE_INT);
        $action = (string) ($_POST['action'] ?? '');
        if (!$id) {
            throw new RuntimeException('Venta inválida.');
        }
        if ($action === 'pagar') {
            $method = strtoupper((string) ($_POST['metodo_pago'] ?? ''));
            if (!in_array($method, ['EFECTIVO', 'YAPE'], true)) {
                throw new RuntimeException('Método inválido.');
            }
            $stmt = db()->prepare("UPDATE cerveza_ventas SET estado='PAGADO', metodo_pago=?, pagado_por_user_id=?, pagado_por_name=?, pagado_at=?, updated_at=? WHERE id=? AND estado='PENDIENTE'");
            $stmt->execute([$method, (int) $user['id'], $user['name'], app_now(), app_now(), $id]);
            audit_log('cerveza_pagada', 'cerveza_ventas', $id);
            flash('success', 'Pendiente pagado.');
        } elseif ($action === 'anular') {
            $stmt = db()->prepare("UPDATE cerveza_ventas SET estado='ANULADO', anulado_por_user_id=?, anulado_por_name=?, anulado_at=?, updated_at=? WHERE id=? AND estado='PENDIENTE'");
            $stmt->execute([(int) $user['id'], $user['name'], app_now(), app_now(), $id]);
            audit_log('cerveza_anulada', 'cerveza_ventas', $id);
            flash('danger', 'Venta anulada.');
        }
    } catch (Throwable $e) {
        flash('danger', $e->getMessage());
    }
    redirect('cerveza-pendientes.php');
}
$sales = db()->query("SELECT * FROM cerveza_ventas WHERE estado='PENDIENTE' ORDER BY id DESC")->fetchAll();
$total = array_sum(array_map(static fn(array $s): float => (float) $s['total'], $sales));
page_start('Cobranza Pendiente', 'cerveza', $user, 'rgba(245, 158, 11, 0.9)');
?>
<div class="container">
    <div class="card text-center" style="border:2px solid var(--warning);background:rgba(245,158,11,0.05);">
        <div class="grid-2"><div><div class="text-sm text-gray font-bold">Por cobrar</div><div class="font-black text-xl text-warning"><?= count($sales) ?> ventas</div></div><div><div class="text-sm text-gray font-bold">Monto Total</div><div class="font-black text-xl text-danger"><?= e(money($total)) ?></div></div></div>
    </div>
    <?php foreach ($sales as $sale): ?>
        <div class="card sale-card mb-1" style="border-left:4px solid var(--warning);">
            <div class="d-flex justify-between align-center mb-1"><span class="text-gray text-sm"><?= e(date('H:i', strtotime((string) $sale['created_at']))) ?> - <?= e($sale['registrado_por_name']) ?></span><span class="badge badge-warning"><?= e($sale['referencia']) ?></span></div>
            <div class="d-flex justify-between align-center"><span class="text-xl font-bold"><?= (int) $sale['cantidad'] ?> cervezas</span><span class="text-xl font-black"><?= e(money((float) $sale['total'])) ?></span></div>
            <div class="btn-row mt-1">
                <?php foreach (['EFECTIVO' => 'btn-success', 'YAPE' => 'btn-yape'] as $method => $btn): ?><form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="action" value="pagar"><input type="hidden" name="venta_id" value="<?= (int) $sale['id'] ?>"><input type="hidden" name="metodo_pago" value="<?= e($method) ?>"><button class="btn btn-small <?= e($btn) ?>"><?= e(ucfirst(strtolower($method))) ?></button></form><?php endforeach; ?>
                <form method="post" class="inline-form confirm-form" data-confirm="¿Seguro que deseas anular esta venta?"><?= csrf_field() ?><input type="hidden" name="action" value="anular"><input type="hidden" name="venta_id" value="<?= (int) $sale['id'] ?>"><button class="btn btn-small btn-outline" style="border-color:var(--danger);color:var(--danger);">Anular</button></form>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if (!$sales): ?><div class="card text-center text-gray">No hay pendientes de cerveza.</div><?php endif; ?>
</div>
<?php page_end('cerveza'); ?>
