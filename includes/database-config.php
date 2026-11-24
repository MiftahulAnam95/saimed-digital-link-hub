<?php

declare(strict_types=1);

/** Memuat konfigurasi database Link Hub tanpa membuka koneksi. */
function database_config(array $defaults): array
{
    // Nilai bawaan cocok untuk instalasi XAMPP lokal. Atur variabel ini di server produksi.
    $host = getenv('DB_HOST') ?: $defaults['host'];
    $port = getenv('DB_PORT') ?: $defaults['port'];
    $database = getenv('DB_DATABASE') ?: $defaults['database'];
    $username = getenv('DB_USERNAME');
    $password = getenv('DB_PASSWORD');
    $username = $username === false ? $defaults['username'] : $username;
    $password = $password === false ? $defaults['password'] : $password;

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

