<?php

declare(strict_types=1);

// Gunakan database dan token simulasi yang sama dengan tes validasi SSO.
ob_start();
require __DIR__ . '/sso.php';

class AccessTestDatabase extends SsoTestDatabase
{
    public function prepare(string $query): SsoTestStatement
    {
        if (str_contains($query, 'FROM photo_profile')) {
            return new AccessPhotoStatement($this);
        }
        return parent::prepare($query);
    }
}
class AccessPhotoStatement extends SsoTestStatement
{
    public function fetch_assoc(): mixed { return ['nama_file' => 'avatar.png']; }
}

session_name('SAIMEDACCESSTEST');
session_start();
$accessDb = new AccessTestDatabase();
$accessDb->row = $original;
$_COOKIE['jwt_token_test'] = $token;
$_SESSION = [];
[$loggedIn, $error, $admin, $photo, $remaining] = sso_check_admin_access($accessDb);
expect_sso($loggedIn && $error === '' && $admin['id'] === '12', 'Super Admin harus dapat mengakses dashboard');
expect_sso(str_ends_with($photo, '/avatar.png') && $remaining > 0 && $remaining <= 300, 'Foto dan waktu sesi harus tersedia');
expect_sso(($_SESSION['sso_token_hash'] ?? '') === hash('sha256', $token) && !empty($_SESSION['csrf']), 'Sesi dan CSRF harus diperbarui');

$accessDb->row['nama_role'] = 'User';
[$loggedIn] = sso_check_admin_access($accessDb);
expect_sso(!$loggedIn && !isset($_SESSION['sso_token_hash']), 'Role lain tidak boleh mengakses dashboard');

$accessDb->row = $original;
$_SESSION = ['sso_token_hash' => hash('sha256', $token), 'sso_last_activity' => time() - 1801, 'sso_login_returned' => true];
[$loggedIn, $error] = sso_check_admin_access($accessDb);
expect_sso(!$loggedIn && $error !== '' && ($_SESSION['sso_timed_out'] ?? '') === hash('sha256', $token), 'Sesi kedaluwarsa harus ditolak tanpa loop');

$_COOKIE = [];
$_SESSION = ['admin' => true, 'sso_token_hash' => 'lama', 'sso_last_activity' => time()];
[$loggedIn] = sso_check_admin_access($accessDb);
expect_sso(!$loggedIn && !isset($_SESSION['admin']) && !isset($_SESSION['sso_token_hash']) && !isset($_SESSION['sso_last_activity']), 'Akses tanpa cookie harus ditolak dan sesi dibersihkan');
session_destroy();
ob_end_clean();
echo "Tes pemeriksaan akses admin berhasil.\n";
