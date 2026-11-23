<?php

// Router untuk server pengembangan PHP. Aset statis dilayani langsung.
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $path;

if (preg_match('#^/(config|includes|templates|tests|vendor)/#i', $path)) {
    http_response_code(403);
    exit('Akses ditolak');
}

// URL aset lama tetap berfungsi saat HTML versi sebelumnya masih tersimpan di cache.
$legacyAssets = [
    '/assets/style.css' => 'css/style.css',
    '/assets/admin.css' => 'css/admin.css',
    '/assets/public.js' => 'js/public.js',
    '/assets/dashboard-chart.js' => 'js/dashboard-chart.js',
    '/assets/dashboard-session.js' => 'js/dashboard-session.js',
    '/assets/saimed-logo.png' => 'images/saimed-logo.png',
    '/assets/favicon.ico' => '../favicon.ico',
];
if (preg_match('#^/assets/icons/([a-z0-9-]+\.svg)$#i', $path, $matches)) {
    $legacyAssets[$path] = '../images/icons/' . $matches[1];
}
if (isset($legacyAssets[$path])) {
    header('Location: ' . $legacyAssets[$path], true, 301);
    exit;
}

if ($path !== '/' && is_file($file) && preg_match('/\.(css|js|png|jpg|svg|ico)$/i', $path)) {
    return false;
}

require __DIR__ . '/index.php';
