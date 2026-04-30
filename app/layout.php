<?php
declare(strict_types=1);

function page_start(string $title, string $active = '', ?array $user = null, string $headerColor = ''): void
{
    $appName = get_config('app_name', 'Pollada Familiar');
    $flash = take_flash();
    $style = $headerColor !== '' ? ' style="background: ' . e($headerColor) . ';"' : '';
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">';
    echo '<title>' . e($title) . ' - ' . e($appName) . '</title><link rel="stylesheet" href="style.css"></head><body>';
    echo '<header class="header"' . $style . '><h1>' . e($title) . '</h1><div class="d-flex align-center gap-2">';
    if ($user) {
        echo '<span class="user mr-1">' . e($user['name']) . '</span><a href="logout.php" class="text-white text-sm" style="text-decoration:none;">Salir</a>';
    }
    echo '</div></header>';
    if ($flash) {
        echo '<div id="serverFlash" data-type="' . e($flash['type']) . '" data-message="' . e($flash['message']) . '"></div>';
    }
}

function page_end(string $active = ''): void
{
    echo '<nav class="bottom-nav">';
    nav_item('dashboard.php', 'Inicio', $active === 'dashboard', 'm2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25');
    nav_item('polladas.php', 'Entregas', $active === 'polladas', 'M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z');
    nav_item('cerveza.php', 'POS', $active === 'cerveza', 'M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z');
    nav_item('resumen.php', 'Reportes', $active === 'resumen', 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z');
    echo '</nav><script src="app.js"></script></body></html>';
}

function nav_item(string $href, string $label, bool $active, string $path): void
{
    echo '<a href="' . e($href) . '" class="nav-item' . ($active ? ' active' : '') . '"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="' . e($path) . '" /></svg><span>' . e($label) . '</span></a>';
}

function badge_for(string $status): string
{
    $class = match ($status) {
        'ENTREGADO', 'PAGADO' => 'badge-success',
        'ANULADO' => 'badge-danger',
        default => 'badge-warning',
    };
    return '<span class="badge ' . $class . '">' . e($status) . '</span>';
}
