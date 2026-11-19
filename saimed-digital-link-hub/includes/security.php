<?php

declare(strict_types=1);

function csrf(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}
function check_csrf(): void
{
    if (
        !isset($_POST['csrf'], $_SESSION['csrf']) ||
        !hash_equals($_SESSION['csrf'], (string) $_POST['csrf'])
    ) {
        http_response_code(403);
        exit('Permintaan tidak valid.');
    }
}
function redirect_admin(string $tab = 'overview'): void
{
    header('Location: ?page=admin&tab=' . rawurlencode($tab), true, 303);
    exit();
}
