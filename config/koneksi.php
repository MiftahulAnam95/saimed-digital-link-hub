<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/database-config.php';

// Pengaturan database Link Hub untuk localhost.
function db_config(): array
{
    $host = '127.0.0.1';
    $username = 'root';
    $password = '';
    $database = 'test_link_saimed';
    $port = '3306';

    // Pengaturan server (jika ada) dapat mengganti nilai localhost ini.
    return database_config(compact('host', 'username', 'password', 'database', 'port'));
}

// Buka koneksi ketika dibutuhkan agar halaman publik tetap memiliki fallback.
function db(): mysqli
{
    static $koneksi = null;
    if ($koneksi instanceof mysqli) {
        return $koneksi;
    }

    $config = db_config();
    $host = $config['host'];
    $username = $config['username'];
    $password = $config['password'];
    $database = $config['database'];
    $port = (int) $config['port'];

    // Kesalahan ditangani pemanggil agar beranda tetap bisa menampilkan fallback.
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $baru = mysqli_connect($host, $username, $password, $database, $port);
    mysqli_set_charset($baru, 'utf8mb4');
    mysqli_query($baru, "SET time_zone = '+00:00'");
    $koneksi = $baru;

    return $koneksi;
}
