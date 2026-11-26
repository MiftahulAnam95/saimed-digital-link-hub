<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/uploaded-icons.php';
$name = $_GET['file'] ?? '';
$path = is_string($name) ? uploaded_icon_path($name) : null;
if ($path === null || !is_file($path)) {
    http_response_code(404);
    exit('Logo tidak ditemukan');
}
header('Content-Type: ' . (str_ends_with($name, '.png') ? 'image/png' : 'image/jpeg'));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=86400');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
