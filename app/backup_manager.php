<?php
declare(strict_types=1);

function create_backup(string $type = 'automatico', ?array $user = null): string
{
    ensure_storage_directories();
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('ZipArchive no está disponible en PHP.');
    }
    if (!file_exists(APP_DB_PATH)) {
        db();
    }

    $lockPath = APP_BACKUPS . DIRECTORY_SEPARATOR . '.backup.lock';
    $lockHandle = fopen($lockPath, 'c+');
    if (!$lockHandle) {
        throw new RuntimeException('No se pudo crear el lock de backup.');
    }
    if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
        $age = file_exists($lockPath) ? time() - filemtime($lockPath) : 0;
        if ($age < 3600) {
            throw new RuntimeException('Ya hay un backup en ejecución.');
        }
    }
    ftruncate($lockHandle, 0);
    fwrite($lockHandle, (string) getmypid());

    try {
        $filename = 'backup_pollada_' . date('Y-m-d_H-i-s') . '.zip';
        $target = APP_BACKUPS . DIRECTORY_SEPARATOR . $filename;
        $zip = new ZipArchive();
        if ($zip->open($target, ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
            throw new RuntimeException('No se pudo crear el ZIP de backup.');
        }

        $zip->addFile(APP_DB_PATH, 'pollada.sqlite');
        if (is_dir(APP_POLLADA_UPLOADS)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(APP_POLLADA_UPLOADS, FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $relative = 'uploads/polladas/' . str_replace('\\', '/', substr($file->getPathname(), strlen(APP_POLLADA_UPLOADS) + 1));
                    $zip->addFile($file->getPathname(), $relative);
                }
            }
        }

        $stats = pollada_stats();
        $beerSalesCount = (int) db()->query("SELECT COUNT(*) FROM cerveza_ventas WHERE estado != 'ANULADO'")->fetchColumn();
        $manifest = [
            'fecha' => app_now(),
            'tamano_db' => filesize(APP_DB_PATH) ?: 0,
            'cantidad_polladas_entregadas' => $stats['entregadas'],
            'cantidad_ventas_cerveza' => $beerSalesCount,
            'usuario' => $user['username'] ?? null,
            'tipo' => $type,
        ];
        $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $zip->close();

        rotate_backups(30);
        audit_log('backup_created', 'backup', null, ['file' => $filename, 'type' => $type]);
        return $target;
    } catch (Throwable $e) {
        app_log('backup_failed', ['error' => $e->getMessage()]);
        try {
            audit_log('backup_failed', 'backup', null, ['error' => $e->getMessage()]);
        } catch (Throwable) {
        }
        throw $e;
    } finally {
        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
        if (file_exists($lockPath)) {
            @unlink($lockPath);
        }
    }
}

function rotate_backups(int $keep): void
{
    $files = glob(APP_BACKUPS . DIRECTORY_SEPARATOR . 'backup_pollada_*.zip') ?: [];
    usort($files, static fn(string $a, string $b): int => filemtime($b) <=> filemtime($a));
    foreach (array_slice($files, $keep) as $old) {
        @unlink($old);
    }
}
