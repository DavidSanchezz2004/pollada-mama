<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/layout.php';
$user = require_login();
$pollada = pollada_stats();
$beer = cerveza_stats();
page_start('Dashboard', 'dashboard', $user);
?>
<div class="container">
    <h2 class="mb-1 text-2xl font-black">Hola, <?= e($user['name']) ?></h2>
    <a href="polladas.php" class="link-clean">
        <div class="card d-flex align-center" style="background: var(--yellow); border-color: var(--yellow-dark); padding: 1.5rem; gap: 1rem;">
            <div class="icon-box" style="background: rgba(0,0,0,0.1); color: var(--text);">📦</div>
            <div><h3 class="font-bold text-dark text-xl mb-1">ENTREGAR POLLADA</h3><p class="text-dark text-sm">Gestión y control de despachos.</p></div>
        </div>
    </a>
    <a href="cerveza.php" class="link-clean">
        <div class="card d-flex align-center" style="background: var(--primary); border-color: var(--primary-light); padding: 1.5rem; gap: 1rem;">
            <div class="icon-box" style="background: rgba(255,255,255,0.1); color: var(--white);">S/</div>
            <div><h3 class="font-bold text-white text-xl mb-1">VENDER CERVEZA</h3><p class="text-white text-sm" style="opacity: 0.8;">POS rápido: efectivo, Yape y pendientes.</p></div>
        </div>
    </a>
    <a href="resumen.php" class="link-clean">
        <div class="card d-flex align-center" style="padding: 1.5rem; gap: 1rem;">
            <div class="icon-box" style="background: rgba(15, 23, 42, 0.05); color: var(--text);">📊</div>
            <div><h3 class="font-bold text-dark text-xl mb-1">RESUMEN DEL DÍA</h3><p class="text-gray text-sm">Ventas, entregas y pendientes.</p></div>
        </div>
    </a>
    <h3 class="mt-1 mb-1 text-gray" style="margin-top: 2rem;">Métricas de hoy</h3>
    <div class="grid-2">
        <div class="card text-center" style="padding: 1.25rem;">
            <div class="text-sm text-gray font-bold mb-1">Polladas entregadas</div>
            <div class="font-black text-success text-2xl"><?= $pollada['entregadas'] ?>/<?= $pollada['total'] ?></div>
            <div class="progress-bar"><div class="progress-fill" style="width: <?= (int) $pollada['avance'] ?>%;"></div></div>
            <div class="text-sm text-gray mt-1">Pendientes: <?= $pollada['pendientes'] ?></div>
        </div>
        <div class="card text-center" style="padding: 1.25rem;">
            <div class="text-sm text-gray font-bold mb-1">Cerveza cobrada</div>
            <div class="font-black text-primary text-2xl"><?= e(money($beer['total_cobrado'])) ?></div>
            <div class="text-sm text-warning mt-1 font-bold">Pendiente: <?= e(money($beer['pendiente'])) ?></div>
        </div>
        <div class="card text-center" style="padding: 1.25rem;">
            <div class="text-sm text-gray font-bold mb-1">Cervezas vendidas</div>
            <div class="font-black text-primary text-2xl"><?= (int) $beer['vendidas'] ?></div>
        </div>
        <div class="card text-center" style="padding: 1.25rem;">
            <div class="text-sm text-gray font-bold mb-1">Pendiente cerveza</div>
            <div class="font-black text-warning text-2xl"><?= e(money($beer['pendiente'])) ?></div>
        </div>
    </div>
</div>
<?php page_end('dashboard'); ?>
