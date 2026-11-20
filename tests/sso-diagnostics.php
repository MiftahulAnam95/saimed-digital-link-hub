<?php

declare(strict_types=1);

require __DIR__ . '/sso.php';

// Pastikan log berisi kategori saja, bukan token, key, atau data pengguna.
$logFile = tempnam(sys_get_temp_dir(), 'sso-log-');
if ($logFile === false) {
    throw new RuntimeException('Tidak dapat membuat file log pengujian');
}
$oldLog = ini_get('error_log');
$oldLogErrors = ini_get('log_errors');
ini_set('error_log', $logFile);
ini_set('log_errors', '1');
try {
    $db->row = [];
    sso_authenticate($db, $token, $website);
    $db->row = $original;
    sso_authenticate($db, \Firebase\JWT\JWT::encode($payload, 'wrong-secret', 'HS256'), $website);
    $db->row = $original;
    sso_authenticate($db, \Firebase\JWT\JWT::encode($payload + ['id_user' => 99], $key, 'HS256'), $website);
    sso_authenticate($db, \Firebase\JWT\JWT::encode(['id_user' => 12, 'exp' => time() - 1], $key, 'HS256'), $website);
    $logs = file_get_contents($logFile);
    expect_sso(is_string($logs), 'Log harus tersedia');
    foreach (['token_atau_akses_website_tidak_ditemukan', 'tanda_tangan_jwt_tidak_valid', 'id_user_tidak_cocok', 'jwt_kedaluwarsa'] as $reason) {
        expect_sso(str_contains($logs, $reason), 'Kategori penolakan harus dicatat: ' . $reason);
    }
    foreach ([$token, $key, 'wrong-secret', 'Admin', $website] as $secret) {
        expect_sso(!str_contains($logs, $secret), 'Log tidak boleh berisi token, key, atau data pengguna');
    }
} finally {
    ini_set('error_log', $oldLog === false ? '' : $oldLog);
    ini_set('log_errors', $oldLogErrors === false ? '0' : $oldLogErrors);
    unlink($logFile);
}
echo "Tes diagnostik SSO berhasil.\n";
