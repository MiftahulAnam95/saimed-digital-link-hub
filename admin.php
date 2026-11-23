<?php

declare(strict_types=1);

// index.php telah memuat app.php dan memulai sesi.
$tab = $_GET['tab'] ?? 'overview';
if (!in_array($tab, ['overview', 'links', 'activity'], true)) {
    $tab = 'overview';
}

require_once __DIR__ . '/akses.php';
[$loggedIn, $ssoError, $identity, $profilePhotoUrl, $sessionRemaining] = sso_check_admin_access();
require __DIR__ . '/includes/admin-actions.php';

$notice = $_SESSION['notice'] ?? '';
unset($_SESSION['notice']);

if (!$loggedIn) {
    $ssoLoginUrl = sso_prepare_login($loggedIn, $ssoError);
    // Tampilkan kesalahan tanpa redirect ulang jika login gagal, supaya tidak looping.
    require __DIR__ . '/templates/admin-login.php';
    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['export'] ?? '') === 'csv') {
    require __DIR__ . '/includes/admin-export.php';
}

require __DIR__ . '/includes/admin-stats.php';
require __DIR__ . '/templates/admin-dashboard.php';
