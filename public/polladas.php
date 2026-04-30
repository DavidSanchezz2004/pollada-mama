<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/layout.php';
$user = require_login();
$stats = pollada_stats();
$rows = db()->query('SELECT * FROM polladas ORDER BY item ASC')->fetchAll();
page_start('Gestión de Entregas', 'polladas', $user);
?>
<div class="container">
    <div class="d-flex justify-between align-center mb-1">
        <div class="text-center w-100"><div class="text-sm text-gray font-bold">Total</div><div class="font-black text-xl"><?= $stats['total'] ?></div></div>
        <div class="text-center w-100" style="border-left: 1px solid var(--border); border-right: 1px solid var(--border);"><div class="text-sm text-gray font-bold">Entregadas</div><div class="font-black text-success text-xl"><?= $stats['entregadas'] ?></div></div>
        <div class="text-center w-100"><div class="text-sm text-gray font-bold">Pendientes</div><div class="font-black text-warning text-xl"><?= $stats['pendientes'] ?></div></div>
    </div>
    <div class="form-group mb-1"><input type="text" id="searchPollada" class="form-control" placeholder="Buscar por nombre o número..."></div>
    <div class="chips">
        <button class="chip active" data-filter="todas">Todas</button>
        <button class="chip" data-filter="pendiente">Pendientes</button>
        <button class="chip" data-filter="entregado">Entregadas</button>
    </div>
    <div class="list" id="polladasList">
        <?php foreach ($rows as $row): $done = $row['estado'] === 'ENTREGADO'; ?>
            <div class="card pollada-card" style="border-left: 4px solid var(--<?= $done ? 'success' : 'warning' ?>); <?= $done ? 'background:#f8fafc;' : '' ?>" data-status="<?= $done ? 'entregado' : 'pendiente' ?>">
                <div class="d-flex justify-between align-center">
                    <div class="text-gray font-bold text-sm">#<?= (int) $row['item'] ?></div>
                    <?= badge_for((string) $row['estado']) ?>
                </div>
                <div class="name font-black text-xl my-1" style="<?= $done ? 'color: var(--text-muted);' : '' ?>"><?= e($row['nombre']) ?></div>
                <div class="d-flex justify-between align-center mb-1"><div><span class="text-gray text-sm">Pago:</span> <span class="text-success font-bold"><?= e($row['pago']) ?></span></div></div>
                <?php if (!$done): ?>
                    <a href="entregar-pollada.php?id=<?= (int) $row['id'] ?>" class="btn btn-primary w-100 text-white" style="text-decoration:none;">📸 FOTO + ENTREGAR</a>
                <?php else: ?>
                    <div class="text-sm text-gray mb-1 muted-box">
                        <div class="d-flex justify-between mb-1"><span><strong>Hora:</strong> <?= e(date('H:i', strtotime((string) $row['entregado_at']))) ?></span><span><strong>Por:</strong> <?= e($row['entregado_por_name']) ?></span></div>
                        <div><strong>Obs:</strong> <?= e($row['observacion'] ?: '-') ?></div>
                    </div>
                    <a class="btn btn-small btn-outline w-100" href="ver-foto.php?id=<?= (int) $row['id'] ?>" target="_blank">Ver comprobante</a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php page_end('polladas'); ?>
