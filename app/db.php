<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
define('APP_DATA', APP_ROOT . DIRECTORY_SEPARATOR . 'data');
define('APP_UPLOADS', APP_ROOT . DIRECTORY_SEPARATOR . 'uploads');
define('APP_POLLADA_UPLOADS', APP_UPLOADS . DIRECTORY_SEPARATOR . 'polladas');
define('APP_BACKUPS', APP_ROOT . DIRECTORY_SEPARATOR . 'backups');
define('APP_LOGS', APP_ROOT . DIRECTORY_SEPARATOR . 'logs');
define('APP_DB_PATH', APP_DATA . DIRECTORY_SEPARATOR . 'pollada.sqlite');

function ensure_storage_directories(): void
{
    foreach ([APP_DATA, APP_UPLOADS, APP_POLLADA_UPLOADS, APP_BACKUPS, APP_LOGS] as $dir) {
        if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
            throw new RuntimeException('No se pudo crear el directorio: ' . $dir);
        }
    }
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    ensure_storage_directories();
    if (!extension_loaded('pdo_sqlite')) {
        throw new RuntimeException('La extensión pdo_sqlite no está habilitada en PHP.');
    }

    $pdo = new PDO('sqlite:' . APP_DB_PATH, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA journal_mode = WAL');
    init_database($pdo);

    return $pdo;
}

function init_database(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            username TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            role TEXT NOT NULL,
            is_active INTEGER DEFAULT 1,
            last_login_at TEXT NULL,
            failed_attempts INTEGER DEFAULT 0,
            locked_until TEXT NULL,
            created_at TEXT NOT NULL,
            updated_at TEXT NULL
        );
        CREATE TABLE IF NOT EXISTS polladas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            item INTEGER NOT NULL,
            nombre TEXT NOT NULL,
            pago TEXT DEFAULT 'PAGO',
            estado TEXT DEFAULT 'PENDIENTE',
            foto_path TEXT NULL,
            observacion TEXT NULL,
            entregado_por_user_id INTEGER NULL,
            entregado_por_name TEXT NULL,
            entregado_at TEXT NULL,
            created_at TEXT NOT NULL,
            updated_at TEXT NULL
        );
        CREATE TABLE IF NOT EXISTS cerveza_ventas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            cantidad INTEGER NOT NULL,
            precio_unitario REAL NOT NULL,
            total REAL NOT NULL,
            metodo_pago TEXT NOT NULL,
            estado TEXT NOT NULL,
            referencia TEXT NULL,
            observacion TEXT NULL,
            registrado_por_user_id INTEGER NOT NULL,
            registrado_por_name TEXT NOT NULL,
            pagado_por_user_id INTEGER NULL,
            pagado_por_name TEXT NULL,
            pagado_at TEXT NULL,
            anulado_por_user_id INTEGER NULL,
            anulado_por_name TEXT NULL,
            anulado_at TEXT NULL,
            created_at TEXT NOT NULL,
            updated_at TEXT NULL
        );
        CREATE TABLE IF NOT EXISTS config (
            key TEXT PRIMARY KEY,
            value TEXT NOT NULL,
            updated_at TEXT NULL
        );
        CREATE TABLE IF NOT EXISTS audit_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NULL,
            username TEXT NULL,
            action TEXT NOT NULL,
            entity TEXT NULL,
            entity_id INTEGER NULL,
            ip_address TEXT NULL,
            user_agent TEXT NULL,
            details TEXT NULL,
            created_at TEXT NOT NULL
        );
    ");

    seed_users($pdo);
    seed_config($pdo);
    seed_polladas($pdo);
}

function app_now(): string
{
    return (new DateTimeImmutable('now', new DateTimeZone('America/Lima')))->format('Y-m-d H:i:s');
}

function seed_users(PDO $pdo): void
{
    $count = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($count > 0) {
        return;
    }

    $users = [
        ['Administrador', 'admin', 'AdminPollada2026!', 'admin'],
        ['Mamá', 'mama', 'MamaPollada2026!', 'mama'],
        ['Prima', 'prima', 'PrimaPollada2026!', 'prima'],
    ];
    $stmt = $pdo->prepare('INSERT INTO users (name, username, password_hash, role, created_at) VALUES (?, ?, ?, ?, ?)');
    foreach ($users as [$name, $username, $password, $role]) {
        $stmt->execute([$name, $username, password_hash($password, PASSWORD_DEFAULT), $role, app_now()]);
    }
}

function seed_config(PDO $pdo): void
{
    $defaults = [
        'cerveza_precio' => '8',
        'cerveza_stock_inicial' => '0',
        'app_name' => 'Pollada Familiar',
        'backup_every_hours' => '2',
    ];
    $stmt = $pdo->prepare('INSERT OR IGNORE INTO config (key, value, updated_at) VALUES (?, ?, ?)');
    foreach ($defaults as $key => $value) {
        $stmt->execute([$key, $value, app_now()]);
    }
}

function seed_polladas(PDO $pdo): void
{
    $count = (int) $pdo->query('SELECT COUNT(*) FROM polladas')->fetchColumn();
    if ($count > 0) {
        return;
    }

    $names = [
        1 => 'ALBINO CRUZ DIANA',
        2 => 'ANDRADE PACHECO ERICK JOEL',
        3 => 'AZAÑA MORENO LEODAN FAVIO',
        4 => 'CABALLERO VALDIVIA LUIS ARMANDO',
        5 => 'CANESSA FARFAN GUILLERMO JUAN',
        6 => 'CARRILLO CHAVEZ LUIS OMAR',
        7 => 'CERNA DIAZ EDUARDO GERARDO',
        8 => 'CHILET GIRON ALBERTO SAMUEL',
        9 => 'CHUQUIVAL VEGA OMAR',
        10 => 'EGUSQUIZA MORAZZANI EDWIN PERCY',
        11 => 'FACHIN AREVALO NILA JULIANA',
        12 => 'FLORES VILLADORUNA YORIC ALFONSO',
        13 => 'GARCIA ROMERO JULIAN',
        14 => 'GONGORA QUINTA FERNANDO',
        15 => 'GONZALES CORTEZ CARLOS ENRIQUE',
        16 => 'GUADAÑA JULON KEVIN SANTOS',
        17 => 'HUACHILLO ROMERO JORGE RODOLFO',
        18 => 'HUAMAN CURISINCHE JUAN CARLOS',
        19 => 'HUAMAN CURISINCHE JUAN CARLOS',
        20 => 'JIMENEZ UBILLUS EMANUEL JOEL',
        21 => 'LIMACHE SANDOVAL ISABELLE SOFÍA',
        22 => 'LOPEZ SANCHEZ ESTEBAN',
        23 => 'LOZA MATELLINI CARLOS ENRIQUE',
        24 => 'MEDINA VILLACREZ ANGELA GRESIA',
        25 => 'MOLINA SALAS JULIO CESAR',
        26 => 'MOLINA SALAS JULIO CESAR',
        27 => 'OBREGON HACEN JUAN CARLOS',
        28 => 'PASTOR CORDOVA JUAN',
        29 => 'PILCO ATANACIO JOSE ALFREDO',
        30 => 'QUICAÑO MELENDEZ CESAR VALENTIN',
        31 => 'QUISPE CALDERON LUIS ALFREDO',
        32 => 'RAMOS HUARANGA HÉCTOR ALBER',
        33 => 'REYES GONZALES JULISSA SARA',
        34 => 'RODRIGUEZ ANAHUI LUIS ALFREDO',
        35 => 'SALCEDO HERRERA ELVIS NELSON',
        36 => 'SALVATIERRA RUMAY LESDY MARIA',
        37 => 'SEA CASTILLO FELIPE JOHNNATHAN',
        38 => 'TAPIA CASTILLO ROGER RAFAEL',
        39 => 'TEJEDA SOLIS JHONNATAN',
        40 => 'TERRENES HUAMAN CESAR GABRIEL',
        41 => 'VASQUEZ LLAMO JESUS MANUEL',
        42 => 'VELIZ GRADOS YSABEL MARIA',
        43 => 'VILLACORTA SANCHEZ HENRY',
        44 => 'BERRU CHIROQUE ALDO',
        45 => 'CORDOVA YAYA CARLOS RICHARD',
        46 => 'GUERRA LOPEZ BRIGITTE TERESA',
        47 => 'MOLINA CURACA LUIS ARTURO',
        48 => 'VARGAS MUÑOZ CARLOS',
        49 => 'VILELA LAVINZ YASSIRA NATALY',
        50 => 'MALPARTIDA PULIDO ADRIAN JOSE',
        51 => 'OSCAR TOMERO',
        52 => 'JOSSIMAR',
        53 => 'ANGELA MARIN',
        54 => 'KEVIN',
        55 => 'CHAVEZ MOOK',
        56 => 'MENDIVEL',
    ];

    $stmt = $pdo->prepare('INSERT INTO polladas (item, nombre, pago, estado, created_at) VALUES (?, ?, ?, ?, ?)');
    foreach ($names as $item => $name) {
        $stmt->execute([$item, $name, 'PAGO', 'PENDIENTE', app_now()]);
    }
}
