<?php

declare(strict_types=1);

require __DIR__ . '/../config/koneksi.php';
function expect_db_config(bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); }
foreach (['DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD', 'DATABASE_URL'] as $name) putenv($name);
$config = db_config();
expect_db_config($config === ['host' => '127.0.0.1', 'port' => '3306', 'database' => 'test_saimed_link', 'username' => 'root', 'password' => ''], 'Konfigurasi bawaan tidak sesuai');
putenv('DB_DATABASE=test-saimed-link');
expect_db_config(db_config()['database'] === 'test-saimed-link', 'Nama database dengan tanda hubung harus didukung');
putenv('DB_DATABASE');
putenv('DB_HOST=db.example');
putenv('DB_PORT=3307');
putenv('DB_DATABASE=custom_db');
putenv('DB_USERNAME=user');
putenv('DB_PASSWORD=test-password');
$config = db_config();
expect_db_config($config === ['host' => 'db.example', 'port' => '3307', 'database' => 'custom_db', 'username' => 'user', 'password' => 'test-password'], 'Konfigurasi DB_* harus tetap dapat diubah');
putenv('DATABASE_URL=mysql://url-user:url-pass@db.example:3308/url_database');
$config = db_config();
expect_db_config($config === ['host' => 'db.example', 'port' => '3308', 'database' => 'url_database', 'username' => 'url-user', 'password' => 'url-pass'], 'DATABASE_URL harus didahulukan');
putenv('DATABASE_URL');
putenv('DB_DATABASE=invalid;dbname=other');
try {
    db_config();
    throw new LogicException('Nama database tidak valid harus ditolak');
} catch (RuntimeException $e) {
    expect_db_config($e->getMessage() === 'Konfigurasi MySQL tidak valid', 'Kesalahan konfigurasi tidak sesuai');
}
echo "Tes konfigurasi database berhasil.\n";
