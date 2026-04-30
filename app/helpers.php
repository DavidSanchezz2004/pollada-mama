<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function flash(string $type, string $message): void
{
    start_secure_session();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function take_flash(): ?array
{
    start_secure_session();
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_array($flash) ? $flash : null;
}

function get_config(string $key, string $default = ''): string
{
    $stmt = db()->prepare('SELECT value FROM config WHERE key = ?');
    $stmt->execute([$key]);
    $value = $stmt->fetchColumn();
    return $value === false ? $default : (string) $value;
}

function set_config(string $key, string $value): void
{
    $stmt = db()->prepare('INSERT INTO config (key, value, updated_at) VALUES (?, ?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value, updated_at = excluded.updated_at');
    $stmt->execute([$key, $value, app_now()]);
}

function money(float $value): string
{
    return 'S/ ' . number_format($value, 2, '.', '');
}

function audit_log(string $action, ?string $entity = null, ?int $entityId = null, ?array $details = null): void
{
    $user = current_user(false);
    $stmt = db()->prepare('INSERT INTO audit_log (user_id, username, action, entity, entity_id, ip_address, user_agent, details, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $user['id'] ?? null,
        $user['username'] ?? null,
        $action,
        $entity,
        $entityId,
        $_SERVER['REMOTE_ADDR'] ?? null,
        substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250),
        $details ? json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
        app_now(),
    ]);
}

function pollada_stats(): array
{
    $row = db()->query("SELECT COUNT(*) total, SUM(CASE WHEN estado='ENTREGADO' THEN 1 ELSE 0 END) entregadas FROM polladas")->fetch();
    $total = (int) ($row['total'] ?? 0);
    $entregadas = (int) ($row['entregadas'] ?? 0);
    return [
        'total' => $total,
        'entregadas' => $entregadas,
        'pendientes' => max(0, $total - $entregadas),
        'avance' => $total > 0 ? round(($entregadas / $total) * 100) : 0,
    ];
}

function cerveza_stats(): array
{
    $row = db()->query("
        SELECT
            COALESCE(SUM(CASE WHEN estado != 'ANULADO' THEN cantidad ELSE 0 END), 0) vendidas,
            COALESCE(SUM(CASE WHEN estado = 'PAGADO' AND metodo_pago = 'EFECTIVO' THEN total ELSE 0 END), 0) efectivo,
            COALESCE(SUM(CASE WHEN estado = 'PAGADO' AND metodo_pago = 'YAPE' THEN total ELSE 0 END), 0) yape,
            COALESCE(SUM(CASE WHEN estado = 'PENDIENTE' THEN total ELSE 0 END), 0) pendiente,
            COALESCE(SUM(CASE WHEN estado != 'ANULADO' THEN total ELSE 0 END), 0) total_vendido,
            COALESCE(SUM(CASE WHEN estado = 'PAGADO' THEN total ELSE 0 END), 0) total_cobrado,
            COALESCE(SUM(CASE WHEN estado = 'ANULADO' THEN total ELSE 0 END), 0) total_anulado
        FROM cerveza_ventas
    ")->fetch();
    $stock = (int) get_config('cerveza_stock_inicial', '0');
    $vendidas = (int) ($row['vendidas'] ?? 0);
    return [
        'vendidas' => $vendidas,
        'stock_inicial' => $stock,
        'stock_restante' => max(0, $stock - $vendidas),
        'efectivo' => (float) ($row['efectivo'] ?? 0),
        'yape' => (float) ($row['yape'] ?? 0),
        'pendiente' => (float) ($row['pendiente'] ?? 0),
        'total_vendido' => (float) ($row['total_vendido'] ?? 0),
        'total_cobrado' => (float) ($row['total_cobrado'] ?? 0),
        'total_anulado' => (float) ($row['total_anulado'] ?? 0),
    ];
}

function clean_text(string $value, int $max = 200): string
{
    $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    return mb_substr($value, 0, $max, 'UTF-8');
}

function require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        exit('Método no permitido');
    }
}
