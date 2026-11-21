<?php

declare(strict_types=1);

require __DIR__ . '/../app.php';

function expect_dashboard(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

// Data simulasi untuk memastikan template tidak memerlukan login atau database nyata.
$_SESSION = ['csrf' => 'test-csrf'];
$days = 14;
$views = 20;
$clicks = 5;
$visitors = ['satu' => true];
$devices = ['Mobile' => 1];
$browsers = ['Browser' => 1];
$daily = ['2026-01-01' => ['views' => 1, 'clicks' => 1]];
$allLinks = fallback_links();
$allLinks[0]['title'] = '<script>alert(1)</script>';
$linkClicks = [];
$events = [];
$demoCount = 0;
$notice = '';
$dbError = false;
$identity = ['name' => '<img src=x onerror=alert(1)>'];
$profilePhotoUrl = 'https://example.test/avatar.png?a=1&b=2';
$sessionRemaining = 185;

foreach (['overview' => 'Ringkasan', 'links' => 'Kelola Tautan', 'activity' => 'Aktivitas'] as $tab => $label) {
    ob_start();
    require __DIR__ . '/../templates/admin-dashboard.php';
    $html = ob_get_clean();
    expect_dashboard(str_contains($html, '<aside class="dash-sidebar"'), 'Sidebar harus ditampilkan');
    expect_dashboard(str_contains($html, 'aria-label="Navigasi dashboard"'), 'Sidebar harus memiliki label navigasi');
    expect_dashboard(str_contains($html, 'aria-current="page"'), 'Halaman aktif harus ditandai');
    expect_dashboard(str_contains($html, 'href="?page=admin&amp;tab=' . $tab . '&amp;days=14"'), 'Navigasi harus mempertahankan periode');
    expect_dashboard(str_contains($html, '<h1>'), 'Halaman harus memiliki judul utama');
    expect_dashboard(str_contains($html, 'name="action" value="logout"'), 'Aksi logout harus tetap tersedia');
    expect_dashboard(str_contains($html, 'name="csrf" value="test-csrf"'), 'Form harus memiliki token CSRF');
    expect_dashboard(str_contains($html, 'name="days"'), 'Pemilihan periode harus tetap tersedia');
    expect_dashboard(str_contains($html, 'Sisa sesi') && str_contains($html, '03:05'), 'Sisa sesi harus tampil');
    expect_dashboard(str_contains($html, '&lt;img src=x onerror=alert(1)&gt;'), 'Nama user harus di-escape');
    expect_dashboard(str_contains($html, 'https://example.test/avatar.png?a=1&amp;b=2'), 'URL foto profil harus di-escape');
    expect_dashboard(str_contains($html, 'assets/dashboard-session.js'), 'Timer harus dimuat');
    expect_dashboard(!str_contains($html, '<script>alert(1)</script>'), 'Data tautan tidak boleh menjadi HTML');
    if ($tab === 'overview') {
        expect_dashboard(str_contains($html, 'Aktivitas Harian'), 'Grafik aktivitas harus tersedia');
        expect_dashboard(str_contains($html, 'Lihat rincian harian'), 'Tabel data grafik harus tersedia');
        expect_dashboard(str_contains($html, '100% interaksi menggunakan perangkat mobile'), 'Persentase perangkat harus sesuai data');
        expect_dashboard(str_contains($html, '<polyline'), 'Grafik harus memakai data harian');
    }
    if ($tab === 'links') {
        expect_dashboard(str_contains($html, 'name="action" value="add"'), 'Tambah tautan harus tersedia');
        expect_dashboard(str_contains($html, 'name="action" value="save"'), 'Simpan tautan harus tersedia');
        expect_dashboard(str_contains($html, 'name="action" value="delete"'), 'Hapus tautan harus tersedia');
        expect_dashboard(str_contains($html, '&lt;script&gt;'), 'Judul tautan harus di-escape');
    }
    if ($tab === 'activity') {
        expect_dashboard(str_contains($html, 'Log Aktivitas Pengunjung'), 'Log aktivitas harus tersedia');
    }
}
echo "Tes template dashboard berhasil.\n";
