<?php

declare(strict_types=1);

// Koneksi database pengguna SSO, terpisah dari database Link Hub.
function sso_db(): mysqli
{
    // Nilai bawaan untuk server lokal.
    $host = '127.0.0.1';
    $username = 'root';
    $password = '';
    $database = 'test_user';
    $port = '3306';

    // Pengaturan server (jika ada) dapat mengganti nilai localhost ini.
    $host = getenv('SSO_DB_HOST') ?: $host;
    $usernameEnv = getenv('SSO_DB_USERNAME');
    $passwordEnv = getenv('SSO_DB_PASSWORD');
    $database = getenv('SSO_DB_DATABASE') ?: $database;
    $port = getenv('SSO_DB_PORT') ?: $port;
    $username = $usernameEnv === false ? $username : $usernameEnv;
    $password = $passwordEnv === false ? $password : $passwordEnv;

    if (!preg_match('/^[a-zA-Z0-9_.:-]+$/', $host)
        || !preg_match('/^[a-zA-Z0-9_$-]+$/', $database)
        || !preg_match('/^[0-9]+$/', $port) || (int) $port < 1 || (int) $port > 65535) {
        throw new RuntimeException('Konfigurasi database SSO tidak valid');
    }

    // Jangan tampilkan rincian kesalahan koneksi atau password kepada pengunjung.
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $koneksi_user = mysqli_connect($host, $username, $password, $database, (int) $port);
    mysqli_set_charset($koneksi_user, 'utf8mb4');

    return $koneksi_user;
}
