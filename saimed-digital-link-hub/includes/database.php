<?php

declare(strict_types=1);

/** Membuka koneksi PostgreSQL sekali untuk setiap permintaan. */
function db(): PDO
{
    static $connection = null;
    if ($connection instanceof PDO) {
        return $connection;
    }

    $databaseUrl = getenv('DATABASE_URL');
    if (!$databaseUrl) {
        throw new RuntimeException('DATABASE_URL belum dikonfigurasi');
    }

    $parts = parse_url($databaseUrl);
    if (!$parts || !in_array($parts['scheme'] ?? '', ['postgres', 'postgresql'], true)
        || empty($parts['host']) || empty($parts['path'])) {
        throw new RuntimeException('DATABASE_URL tidak valid');
    }

    $dsn = 'pgsql:host=' . $parts['host']
        . ';port=' . ($parts['port'] ?? 5432)
        . ';dbname=' . ltrim($parts['path'], '/');

    parse_str($parts['query'] ?? '', $options);
    $sslModes = ['disable', 'allow', 'prefer', 'require', 'verify-ca', 'verify-full'];
    if (isset($options['sslmode']) && in_array($options['sslmode'], $sslModes, true)) {
        $dsn .= ';sslmode=' . $options['sslmode'];
    }

    $connection = new PDO(
        $dsn,
        rawurldecode($parts['user'] ?? ''),
        rawurldecode($parts['pass'] ?? ''),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    return $connection;
}
