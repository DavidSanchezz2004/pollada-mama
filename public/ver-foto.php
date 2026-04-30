<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_login();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(404);
    exit;
}
$stmt = db()->prepare("SELECT foto_path FROM polladas WHERE id = ? AND estado = 'ENTREGADO'");
$stmt->execute([$id]);
$path = $stmt->fetchColumn();
if (!$path || !is_string($path) || str_contains($path, '..')) {
    http_response_code(404);
    exit;
}
$file = APP_POLLADA_UPLOADS . DIRECTORY_SEPARATOR . $path;
if (!is_file($file)) {
    http_response_code(404);
    exit;
}
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($file) ?: 'application/octet-stream';
if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
    http_response_code(403);
    exit;
}
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($file));
header('Cache-Control: private, max-age=3600');
readfile($file);
