# Pollada Familiar

Sistema PHP puro + SQLite para control de entregas de polladas, POS rápido de cerveza, pendientes, resumen y backups.

## Requisitos

- PHP 8.2 o superior.
- Extensiones PHP: `pdo_sqlite`, `fileinfo`, `zip`.
- Apache/Laragon o Nginx apuntando siempre a la carpeta `public`.

Si aparece el mensaje `La extensión pdo_sqlite no está habilitada`, activa `extension=pdo_sqlite` y `extension=sqlite3` en el `php.ini` usado por Laragon/PHP y reinicia Apache.

## Local Laragon

1. Copiar el proyecto a `C:\laragon\www\pollada-mama`.
2. Entrar a `http://localhost/pollada-mama/public/login.php`.
3. Usuarios iniciales solo para local:
   - `admin` / `AdminPollada2026!`
   - `mama` / `MamaPollada2026!`
   - `prima` / `PrimaPollada2026!`
4. Cambiar contraseñas inmediatamente:
   `php scripts/change_password.php admin NuevaClaveSegura123!`
5. Probar backup manual desde `Resumen` con usuario admin.
6. Configurar backup cada 2 horas con Windows Task Scheduler usando `scripts/cron-example.txt`.

## VPS

1. Subir a `/var/www/pollada-mama`.
2. Configurar el virtual host apuntando a `/var/www/pollada-mama/public`.
3. Permisos:
   `chown -R www-data:www-data data uploads backups logs`
   `chmod -R 750 data uploads backups logs`
4. Activar HTTPS.
5. Configurar cron:
   `0 */2 * * * /usr/bin/php /var/www/pollada-mama/scripts/backup.php >> /var/www/pollada-mama/logs/backup.log 2>&1`
6. Cambiar las contraseñas iniciales antes de usar en producción.

## Easypanel

1. Sube este proyecto a GitHub.
2. En Easypanel crea un servicio tipo App con source `Github`.
3. Usa:
   - Owner: `DavidSanchezz2004`
   - Repository: `pollada-mama`
   - Branch: `main`
   - Build Path: `/`
4. Easypanel detectará el `Dockerfile` y expondrá Apache en el puerto 80.
5. Configura un dominio con HTTPS.
6. Cambia las contraseñas iniciales después del primer login.

## Nginx ejemplo

```nginx
server {
    listen 443 ssl;
    server_name ejemplo.com;
    root /var/www/pollada-mama/public;
    index login.php index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    }

    location ~ /\.(?!well-known).* { deny all; }
    location ~* \.(sqlite|db|env|ini|log|bak|zip)$ { deny all; }
}
```

## Backups

- Manual: usuario admin desde `Resumen`.
- Automático: `php scripts/backup.php`.
- Se guardan en `backups/` como `backup_pollada_YYYY-mm-dd_HH-MM-SS.zip`.
- Mantiene los últimos 30 backups.

## Seguridad aplicada

- Sesiones con cookies `httponly`, `samesite=Lax` y `secure` bajo HTTPS.
- CSRF en formularios POST.
- Prepared statements con PDO.
- Fotos fuera de `public`, servidas por `public/ver-foto.php`.
- Uploads validados por tamaño, extensión lógica y MIME real.
- No se borran ventas: se anulan.
- Auditoría en `audit_log` y logs en `logs/app.log`.
