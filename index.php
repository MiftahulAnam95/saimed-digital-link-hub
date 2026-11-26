<?php

declare(strict_types=1);

require __DIR__ . '/app.php';

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$api = $_GET['api'] ?? '';

if ($api === 'track' || in_array($requestPath, ['/api/track', '/api/track.php'], true)) {
    require __DIR__ . '/api/track.php';
    exit;
}

if ($api === 'health' || in_array($requestPath, ['/api/health', '/api/health.php'], true)) {
    require __DIR__ . '/api/health.php';
    exit;
}

if ($api === 'icon' || in_array($requestPath, ['/api/icon', '/api/icon.php'], true)) {
    require __DIR__ . '/api/icon.php';
    exit;
}

if (isset($_GET['api'])) {
    json_response(['error' => 'Tidak ditemukan'], 404);
}

// SSO juga memakai sesi PHP; pisahkan agar logout SSO tidak menghapus sesi aplikasi.
session_name('SAIMEDSESSID');
ini_set('session.use_strict_mode', '1');
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_start();

// Parameter page tetap didukung di shared hosting tanpa aturan rewrite.
$page = $_GET['page'] ?? 'home';
if (!isset($_GET['page']) && str_ends_with((string) $requestPath, '/admin')) {
    $page = 'admin';
} elseif (!isset($_GET['page']) && str_ends_with((string) $requestPath, '/official')) {
    $page = 'official';
}

require_once __DIR__ . '/akses.php';
if (sso_should_return_to_admin(is_string($page) ? $page : '', $_SERVER['REQUEST_METHOD'])) {
    redirect_admin();
}

if ($page === 'admin') {
    require __DIR__ . '/admin.php';
    exit;
}

if ($page === 'official') {
    require __DIR__ . '/official.php';
    exit;
}

if ($page !== 'home') {
    http_response_code(404);
    exit('Halaman tidak ditemukan');
}

$publicLinks = links(true);
require __DIR__ . '/templates/home.php';
