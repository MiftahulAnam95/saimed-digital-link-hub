<?php

declare(strict_types=1);

require __DIR__ . '/../includes/sso-return.php';

function expect_return(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$_SESSION = [];
expect_return(!sso_should_return_to_admin('home', 'GET', 'cookie'), 'Halaman publik biasa tidak boleh diarahkan');
sso_mark_login_pending();
expect_return(!sso_should_return_to_admin('home', 'GET', null), 'Tanpa cookie SSO tidak boleh dialihkan');
expect_return(!sso_should_return_to_admin('official', 'GET', 'cookie'), 'Halaman resmi tidak boleh dialihkan');
expect_return(!sso_should_return_to_admin('home', 'POST', 'cookie'), 'POST tidak boleh dialihkan');
expect_return(sso_should_return_to_admin('home', 'GET', 'cookie'), 'Kembali dari SSO harus menuju admin');
expect_return(!sso_should_return_to_admin('home', 'GET', 'cookie'), 'Pengalihan hanya boleh terjadi sekali');
$_SESSION['sso_login_pending_until'] = time() - 1;
expect_return(!sso_should_return_to_admin('home', 'GET', 'cookie'), 'Login yang kedaluwarsa tidak boleh dialihkan');
expect_return(!isset($_SESSION['sso_login_pending_until']), 'Status kedaluwarsa harus dihapus');
echo "Tes kembali dari SSO berhasil.\n";
