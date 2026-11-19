<?php
// Router untuk server pengembangan PHP; aset statis dilayani langsung.
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__.$path;
if ($path !== '/' && is_file($file) && preg_match('/\.(css|js|png|jpg|svg|ico)$/i', $path)) return false;
require __DIR__.'/index.php';
