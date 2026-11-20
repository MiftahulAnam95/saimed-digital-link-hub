<?php

declare(strict_types=1);

// Simpan tujuan dan status percobaan hanya di sesi aplikasi, bukan di URL browser.
function sso_mark_login_pending(): void
{
    unset($_SESSION['sso_login_returned']);
    $_SESSION['sso_login_pending_until'] = time() + 600;
}

function sso_login_pending(): bool
{
    $until = $_SESSION['sso_login_pending_until'] ?? null;
    if (!is_int($until) || $until <= time()) {
        unset($_SESSION['sso_login_pending_until']);
        return false;
    }
    return true;
}

function sso_mark_login_returned(): void
{
    unset($_SESSION['sso_login_pending_until']);
    $_SESSION['sso_login_returned'] = true;
}

function sso_should_return_to_admin(string $page, string $method): bool
{
    if (!sso_login_pending() || $page !== 'home' || $method !== 'GET') {
        return false;
    }
    // Juga tangani cookie yang tidak terkirim agar kesalahannya terlihat di admin.
    sso_mark_login_returned();
    return true;
}

function sso_should_start_login(string $method, bool $loggedIn, bool $hasError): bool
{
    return $method === 'GET' && !$loggedIn && !$hasError
        && empty($_SESSION['sso_login_returned']) && !sso_login_pending();
}
