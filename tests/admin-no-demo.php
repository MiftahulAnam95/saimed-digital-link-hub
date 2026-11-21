<?php

declare(strict_types=1);

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = ['action' => 'demo'];
$_SESSION = [];
$loggedIn = true;
$tab = 'overview';
function check_csrf(): void {}
function db() { throw new RuntimeException('Aksi yang tidak dikenal tidak boleh membuka database'); }
function redirect_admin(string $tab = 'overview'): void { throw new LogicException('redirect:' . $tab); }
try {
    require __DIR__ . '/../includes/admin-actions.php';
    throw new RuntimeException('Aksi lama harus ditolak');
} catch (LogicException $e) {
    if ($e->getMessage() !== 'redirect:overview' || ($_SESSION['notice'] ?? '') !== 'Tindakan tidak dikenal.') {
        throw new RuntimeException('Aksi lama tidak ditolak dengan benar');
    }
}
echo "Tes penolakan fitur data contoh berhasil.\n";
