<?php

declare(strict_types=1);

/** Memuat konfigurasi database Link Hub tanpa membuka koneksi. */
function db_config(): array
{
    // Nilai bawaan cocok untuk instalasi XAMPP lokal. Atur variabel ini di server produksi.
    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '3306';
    $database = getenv('DB_DATABASE') ?: 'test_saimed_link';
    $username = getenv('DB_USERNAME');
    $password = getenv('DB_PASSWORD');
    $username = $username === false ? 'root' : $username;
    $password = $password === false ? '' : $password;

    // DATABASE_URL juga didukung jika penyedia hosting menyediakan URL koneksi.
    $databaseUrl = getenv('DATABASE_URL');
    if ($databaseUrl) {
        $parts = parse_url($databaseUrl);
        if (!$parts || ($parts['scheme'] ?? '') !== 'mysql'
            || empty($parts['host']) || empty($parts['path'])) {
            throw new RuntimeException('DATABASE_URL harus berupa URL MySQL yang valid');
        }
        $host = $parts['host'];
        $port = (string) ($parts['port'] ?? 3306);
        $database = rawurldecode(ltrim($parts['path'], '/'));
        $username = rawurldecode($parts['user'] ?? '');
        $password = rawurldecode($parts['pass'] ?? '');
    }

    if (!preg_match('/^[a-zA-Z0-9_.:-]+$/', $host)
        || !preg_match('/^[a-zA-Z0-9_$-]+$/', $database)
        || !preg_match('/^[0-9]+$/', (string) $port) || (int) $port < 1 || (int) $port > 65535) {
        throw new RuntimeException('Konfigurasi MySQL tidak valid');
    }

    return [
        'host' => $host,
        'port' => (string) $port,
        'database' => $database,
        'username' => $username,
        'password' => $password,
    ];
}

/** Membuka koneksi MySQL sekali untuk setiap permintaan. */
function db(): PDO
{
    static $connection = null;
    if ($connection instanceof PDO) {
        return $connection;
    }
    $config = db_config();
    $host = $config['host'];
    $port = $config['port'];
    $database = $config['database'];
    $username = $config['username'];
    $password = $config['password'];

    $dsn = 'mysql:host=' . $host . ';port=' . $port
        . ';dbname=' . $database . ';charset=utf8mb4';
    $connection = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // Waktu analytics disimpan dalam UTC; dashboard menampilkannya sebagai WIB.
    $connection->exec("SET time_zone = '+00:00'");

    return $connection;
}
