<?php

declare(strict_types=1);

// Database pengguna SSO terpisah dari database analytics aplikasi.
function sso_db(): PDO
{
    $host = getenv('SSO_DB_HOST') ?: '127.0.0.1';
    $port = getenv('SSO_DB_PORT') ?: '3306';
    $name = getenv('SSO_DB_DATABASE') ?: 'test_user';
    $user = getenv('SSO_DB_USERNAME');
    $password = getenv('SSO_DB_PASSWORD');
    if (!preg_match('/^[a-zA-Z0-9_.:-]+$/', $host)
        || !preg_match('/^[a-zA-Z0-9_$-]+$/', $name)
        || !preg_match('/^[0-9]+$/', $port) || (int) $port < 1 || (int) $port > 65535) {
        throw new RuntimeException('Konfigurasi database SSO tidak valid');
    }
    return new PDO("mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4",
        $user === false ? 'root' : $user, $password === false ? '' : $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 5,
        ]);
}
