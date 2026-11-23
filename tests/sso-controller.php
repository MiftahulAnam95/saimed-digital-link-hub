<?php

declare(strict_types=1);

// Jalankan setiap skenario dalam proses sendiri karena redirect menghentikan request.
$scenario = $argv[1] ?? '';
if (!in_array($scenario, ['start', 'retry', 'returned', 'config'], true)) {
    throw new InvalidArgumentException('Pilih skenario start, retry, returned, atau config.');
}
require __DIR__ . '/../app.php';
require __DIR__ . '/../akses.php';
session_name('SAIMEDTESTSESSID');
session_start();
$_GET = [];
$_COOKIE = [];
$_SESSION = [];
$_SERVER['REQUEST_METHOD'] = $scenario === 'retry' ? 'POST' : 'GET';
putenv('SSO_AES_SECRET_KEY=' . str_repeat('a', 32));
if ($scenario === 'config') {
    putenv('SSO_AES_SECRET_KEY');
}
if ($scenario === 'retry' || $scenario === 'returned') {
    $_SESSION['sso_login_returned'] = true;
}
if ($scenario === 'retry') {
    $_SESSION['csrf'] = 'test-csrf';
    $_POST = ['csrf' => 'test-csrf', 'action' => 'sso-login'];
}
ob_start();
if ($scenario === 'start' || $scenario === 'retry') {
    register_shutdown_function(function () use ($scenario): void {
        $output = ob_get_contents();
        if ($output !== '' || !sso_login_pending() || !empty($_SESSION['sso_login_returned'])) {
            fwrite(STDERR, "Gagal memulai redirect SSO: $scenario\n");
            exit(1);
        }
        ob_end_clean();
        echo "Tes kontrol SSO $scenario berhasil.\n";
    });
}
require __DIR__ . '/../admin.php';
if ($scenario === 'start' || $scenario === 'retry') {
    throw new RuntimeException('Redirect harus menghentikan request.');
}
$html = ob_get_clean();
if ($scenario === 'returned' && (!str_contains($html, 'Cookie SSO tidak ditemukan')
    || !str_contains($html, 'Coba lagi melalui SSO') || sso_login_pending())) {
    throw new RuntimeException('Kembali tanpa cookie harus menampilkan kesalahan tanpa loop.');
}
if ($scenario === 'config' && (!str_contains($html, 'SSO belum tersedia')
    || str_contains($html, 'Coba lagi melalui SSO') || sso_login_pending())) {
    throw new RuntimeException('Konfigurasi tidak lengkap harus menampilkan kesalahan.');
}
echo "Tes kontrol SSO $scenario berhasil.\n";
