<?php
// Memeriksa apakah koneksi database tersedia.
require_once __DIR__ . '/../app.php';

try {
    db()->query('SELECT 1');
    json_response(['ok' => true]);
} catch (Throwable $exception) {
    json_response(['ok' => false], 500);
}
