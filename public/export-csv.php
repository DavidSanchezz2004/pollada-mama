<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_login();
audit_log('export_csv', 'report');
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="resumen_pollada_' . date('Ymd_His') . '.csv"');
$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");
$pollada = pollada_stats();
$beer = cerveza_stats();
fputcsv($out, ['RESUMEN POLLADAS']);
fputcsv($out, ['Total', 'Entregadas', 'Pendientes', 'Avance %']);
fputcsv($out, [$pollada['total'], $pollada['entregadas'], $pollada['pendientes'], $pollada['avance']]);
fputcsv($out, []);
fputcsv($out, ['DETALLE POLLADAS']);
fputcsv($out, ['Item', 'Nombre', 'Pago', 'Estado', 'Entregado por', 'Entregado at', 'Observacion']);
foreach (db()->query('SELECT item, nombre, pago, estado, entregado_por_name, entregado_at, observacion FROM polladas ORDER BY item ASC') as $row) {
    fputcsv($out, $row);
}
fputcsv($out, []);
fputcsv($out, ['RESUMEN CERVEZA']);
fputcsv($out, ['Vendidas', 'Efectivo', 'Yape', 'Pendiente', 'Total vendido', 'Total cobrado', 'Total anulado']);
fputcsv($out, [$beer['vendidas'], $beer['efectivo'], $beer['yape'], $beer['pendiente'], $beer['total_vendido'], $beer['total_cobrado'], $beer['total_anulado']]);
fputcsv($out, []);
fputcsv($out, ['DETALLE VENTAS CERVEZA']);
fputcsv($out, ['ID', 'Cantidad', 'Precio unitario', 'Total', 'Metodo', 'Estado', 'Referencia', 'Registrado por', 'Creado', 'Pagado por', 'Pagado at', 'Anulado por', 'Anulado at']);
foreach (db()->query('SELECT id, cantidad, precio_unitario, total, metodo_pago, estado, referencia, registrado_por_name, created_at, pagado_por_name, pagado_at, anulado_por_name, anulado_at FROM cerveza_ventas ORDER BY id ASC') as $row) {
    fputcsv($out, $row);
}
fputcsv($out, []);
fputcsv($out, ['PENDIENTES CERVEZA']);
foreach (db()->query("SELECT id, cantidad, total, referencia, registrado_por_name, created_at FROM cerveza_ventas WHERE estado='PENDIENTE' ORDER BY id ASC") as $row) {
    fputcsv($out, $row);
}
fclose($out);
