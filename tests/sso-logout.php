<?php

declare(strict_types=1);

require __DIR__ . '/../akses.php';
putenv('SSO_AES_SECRET_KEY=' . str_repeat('a', 32));
session_name('SAIMEDLOGOUTTEST');
session_start();
$_SESSION = ['sso_token_hash' => 'old', 'sso_last_activity' => time()];
ob_start();
register_shutdown_function(function (): void {
    $output = ob_get_clean();
    if ($output !== '' || $_SESSION !== [] || session_status() !== PHP_SESSION_NONE) {
        fwrite(STDERR, "GAGAL: Logout SSO harus menghapus sesi sebelum redirect.\n");
        return;
    }
    echo "Tes logout SSO berhasil.\n";
});
sso_handle_logout_action();
throw new RuntimeException('Logout harus menghentikan request.');
