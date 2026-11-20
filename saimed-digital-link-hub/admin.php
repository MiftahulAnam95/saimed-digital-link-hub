<?php

declare(strict_types=1);

// index.php telah memuat app.php dan memulai sesi.
$tab = $_GET['tab'] ?? 'overview';
if (!in_array($tab, ['overview', 'links', 'activity'], true)) {
    $tab = 'overview';
}

require_once __DIR__ . '/includes/sso.php';
$autoload = __DIR__ . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}
$loggedIn = false;
$ssoError = '';
$token = $_COOKIE['jwt_token_test'] ?? '';
if (is_string($token) && $token !== '') {
    try {
        if (!class_exists(\Firebase\JWT\JWT::class)) {
            throw new RuntimeException('Dependensi SSO belum terpasang.');
        }
        $identity = sso_authenticate(sso_db(), $token, sso_app_url());
        $tokenHash = hash('sha256', $token);
        if ($identity && isset($_SESSION['sso_token_hash'], $_SESSION['sso_last_activity'])
            && hash_equals($_SESSION['sso_token_hash'], $tokenHash)
            && time() - $_SESSION['sso_last_activity'] > 1800) {
            $_SESSION = [];
            session_destroy();
            header('Location: ' . sso_logout_url(), true, 303);
            exit();
        }
        if ($identity) {
            if (!isset($_SESSION['sso_token_hash']) || !hash_equals($_SESSION['sso_token_hash'], $tokenHash)) {
                session_regenerate_id(true);
                $_SESSION['csrf'] = bin2hex(random_bytes(32));
            }
            $_SESSION['sso_token_hash'] = $tokenHash;
            $_SESSION['sso_last_activity'] = time();
            $loggedIn = true;
        }
    } catch (Throwable $e) {
        error_log('SAIMED SSO: ' . $e->getMessage());
        $ssoError = 'SSO belum tersedia. Periksa konfigurasi dan koneksi server.';
    }
}
if (!$loggedIn) {
    unset($_SESSION['admin'], $_SESSION['sso_token_hash'], $_SESSION['sso_last_activity']);
}
require __DIR__ . '/includes/admin-actions.php';

$notice = $_SESSION['notice'] ?? '';
unset($_SESSION['notice']);

if (!$loggedIn) {
    require __DIR__ . '/templates/admin-login.php';
    return;
}

require __DIR__ . '/includes/admin-stats.php';
require __DIR__ . '/templates/admin-dashboard.php';
