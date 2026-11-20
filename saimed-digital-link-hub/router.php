<?php

// Router untuk server pengembangan PHP. Aset statis dilayani langsung.
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $path;

if (preg_match('#^/(config|includes|templates|tests)/#i', $path)) {
    http_response_code(403);
    exit('Akses ditolak');
}

if ($path !== '/' && is_file($file) && preg_match('/\.(css|js|png|jpg|svg|ico)$/i', $path)) {
    return false;
}

require __DIR__ . '/index.php';
