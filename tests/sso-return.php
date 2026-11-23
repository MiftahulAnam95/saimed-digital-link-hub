<?php

declare(strict_types=1);

require __DIR__ . '/../akses.php';

function expect_return(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$_SESSION = [];
expect_return(!sso_should_return_to_admin('home', 'GET'), 'Halaman publik biasa tidak boleh diarahkan');
expect_return(sso_should_start_login('GET', false, false), 'Admin belum login harus langsung masuk SSO');
expect_return(!sso_should_start_login('POST', false, false), 'POST tidak boleh memulai login otomatis');
expect_return(!sso_should_start_login('GET', true, false), 'Admin terautentikasi tidak dialihkan ke SSO');
expect_return(!sso_should_start_login('GET', false, true), 'Kesalahan konfigurasi atau database tidak boleh memicu redirect');
sso_mark_login_pending();
expect_return(!sso_should_start_login('GET', false, false), 'Login yang berlangsung tidak boleh diulang otomatis');
expect_return(!sso_should_return_to_admin('official', 'GET'), 'Halaman resmi tidak boleh dialihkan');
expect_return(!sso_should_return_to_admin('home', 'POST'), 'POST tidak boleh dialihkan');
expect_return(sso_should_return_to_admin('home', 'GET'), 'Kembali dari SSO harus menuju admin, termasuk tanpa cookie');
expect_return(!sso_should_return_to_admin('home', 'GET'), 'Pengalihan hanya boleh terjadi sekali');
expect_return(!sso_should_start_login('GET', false, false), 'Token ditolak setelah kembali tidak boleh menyebabkan loop');
sso_mark_login_pending();
expect_return(empty($_SESSION['sso_login_returned']), 'Percobaan ulang harus membersihkan status sebelumnya');
sso_mark_login_returned();
expect_return(!sso_should_start_login('GET', false, false), 'Kembali langsung ke admin tidak boleh menyebabkan loop');
$_SESSION = ['sso_login_pending_until' => time() - 1];
expect_return(!sso_should_return_to_admin('home', 'GET'), 'Login yang kedaluwarsa tidak boleh dialihkan');
expect_return(!isset($_SESSION['sso_login_pending_until']), 'Status kedaluwarsa harus dihapus');
echo "Tes kembali dari SSO berhasil.\n";
