<?php

declare(strict_types=1);

require __DIR__ . '/app.php';

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$api = $_GET['api'] ?? '';

if ($api === 'track' || $requestPath === '/api/track') {
    track();
}

if ($api === 'health' || $requestPath === '/api/health') {
    try {
        db()->query('SELECT 1');
        json_response(['ok' => true]);
    } catch (Throwable $exception) {
        json_response(['ok' => false], 500);
    }
}

if (isset($_GET['api'])) {
    json_response(['error' => 'Tidak ditemukan'], 404);
}

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
