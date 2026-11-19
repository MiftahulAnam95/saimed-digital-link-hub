<?php

declare(strict_types=1);

// index.php telah memuat app.php dan memulai sesi.
$tab = $_GET['tab'] ?? 'overview';
if (!in_array($tab, ['overview', 'links', 'activity'], true)) {
    $tab = 'overview';
}

$loggedIn = !empty($_SESSION['admin']);
require __DIR__ . '/includes/admin-actions.php';

$notice = $_SESSION['notice'] ?? '';
unset($_SESSION['notice']);

if (!$loggedIn) {
    require __DIR__ . '/templates/admin-login.php';
    return;
}

require __DIR__ . '/includes/admin-stats.php';
require __DIR__ . '/templates/admin-dashboard.php';
