<?php

declare(strict_types=1);

// Simpan niat login di sesi aplikasi, bukan pada parameter URL dari browser.
function sso_mark_login_pending(): void
{
    $_SESSION['sso_login_pending_until'] = time() + 600;
}

function sso_should_return_to_admin(string $page, string $method, $token): bool
{
    $until = $_SESSION['sso_login_pending_until'] ?? null;
    if (!is_int($until) || $until <= time()) {
        unset($_SESSION['sso_login_pending_until']);
        return false;
    }
    if ($page !== 'home' || $method !== 'GET' || !is_string($token) || $token === '') {
        return false;
    }
    // Hanya mengarahkan; admin.php tetap memverifikasi JWT dan hak akses.
    unset($_SESSION['sso_login_pending_until']);
    return true;
}
