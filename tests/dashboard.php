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
foreach ($allLinks as $i => &$fixtureLink) $fixtureLink['id'] = $i + 1;
unset($fixtureLink);
$allLinks[0]['title'] = '<script>alert(1)</script>';
$allLinks[0]['sort_order'] = 2;
$allLinks[1]['sort_order'] = 2; // Data lama duplikat harus tampil sebagai posisi unik.
$allLinks[2]['sort_order'] = 9;
$allLinks[1]['is_active'] = false;
$linkClicks = [];
$performance = [
    ['title' => '<tautan>', 'icon' => 'link', 'count' => 3],
    ['title' => 'Instagram', 'icon' => 'instagram', 'count' => 2],
];
$topLink = $performance[0];
$previousViews = 10;
$previousClicks = 10;
$events = [];
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
    expect_dashboard((bool) preg_match('~<div class="dash-controls">\s*<form method="get" class="period-form">.*?</form>\s*<div class="report-actions">~s', $html), 'Filter dan tombol laporan harus dalam kelompok yang sama');
    expect_dashboard(str_contains($html, 'href="?page=admin&amp;export=csv&amp;days=14"'), 'Ekspor harus mempertahankan periode');
    expect_dashboard(str_contains($html, '<noscript><button class="button" type="submit">Terapkan</button></noscript>'), 'Filter tanpa JavaScript harus tetap tersedia');
    expect_dashboard(!str_contains($html, 'Data analytics tersimpan di MySQL'), 'Kartu analytics tidak boleh ditampilkan');
    expect_dashboard(!str_contains($html, 'Muat Data Contoh'), 'Tombol kartu data contoh tidak boleh ditampilkan');
    expect_dashboard(!str_contains($html, 'analytics-demo-note') && !str_contains($html, 'name="action" value="demo"'), 'Fitur data contoh tidak boleh ditampilkan');
    expect_dashboard(str_contains($html, 'Sisa sesi') && str_contains($html, '03:05'), 'Sisa sesi harus tampil');
    expect_dashboard(str_contains($html, '&lt;img src=x onerror=alert(1)&gt;'), 'Nama user harus di-escape');
    expect_dashboard(str_contains($html, 'https://example.test/avatar.png?a=1&amp;b=2'), 'URL foto profil harus di-escape');
    expect_dashboard(str_contains($html, 'assets/js/dashboard-session.js'), 'Timer harus dimuat');
    expect_dashboard(!str_contains($html, '<script>alert(1)</script>'), 'Data tautan tidak boleh menjadi HTML');
    if ($tab !== 'links') expect_dashboard(!str_contains($html, 'assets/js/dashboard-links.js'), 'Script dialog hanya dimuat pada tab tautan');
    if ($tab === 'overview') {
        expect_dashboard(str_contains($html, 'Aktivitas Harian'), 'Grafik aktivitas harus tersedia');
        expect_dashboard(str_contains($html, 'Lihat rincian harian'), 'Tabel data grafik harus tersedia');
        expect_dashboard(str_contains($html, '100% interaksi menggunakan perangkat mobile'), 'Persentase perangkat harus sesuai data');
        expect_dashboard(str_contains($html, '<path d="M '), 'Grafik harus memakai data harian');
        expect_dashboard(str_contains($html, 'role="slider"') && str_contains($html, 'data-points='), 'Grafik harus dapat diakses dan berisi data');
        expect_dashboard(str_contains($html, 'assets/js/dashboard-chart.js'), 'Interaksi grafik harus tersedia');
        expect_dashboard(str_contains($html, '+100,0% vs periode sebelumnya'), 'Tren kunjungan harus ditampilkan');
        expect_dashboard(str_contains($html, '-50,0% vs periode sebelumnya'), 'Tren klik harus ditampilkan');
        expect_dashboard(str_contains($html, '&lt;tautan&gt; paling diminati'), 'Insight dan escape nama tautan diperlukan');
        expect_dashboard(str_contains($html, '60,0% kontribusi'), 'Kontribusi harus sesuai total klik');
        expect_dashboard(str_contains($html, 'Ekspor CSV'), 'Ekspor harus tersedia');
        expect_dashboard(str_contains($html, '90 hari terakhir') && str_contains($html, 'Semua waktu'), 'Periode tambahan harus tersedia');
    }
    if ($tab === 'links') {
        expect_dashboard(str_contains($html, '<dialog class="add-link-dialog" id="add-link-dialog" aria-labelledby="add-link-title" aria-describedby="add-link-description">'), 'Tambah tautan harus berupa dialog berlabel');
        expect_dashboard(str_contains($html, 'id="open-add-link" aria-haspopup="dialog" aria-controls="add-link-dialog"'), 'Tombol harus terhubung dengan dialog');
        expect_dashboard(str_contains($html, '<label for="new-link-title">Nama</label>') && str_contains($html, '<label for="new-link-url">URL</label>') && str_contains($html, '<label for="new-link-icon">Logo/Icon</label>'), 'Form dialog harus memiliki tiga label');
        expect_dashboard((bool) preg_match('~<dialog.*?<form method="post" enctype="multipart/form-data" class="add-link-form">.*?name="csrf" value="test-csrf".*?name="action" value="add".*?name="title".*?name="url".*?name="icon_file".*?</form>.*?</dialog>~s', $html), 'Form dialog harus mengirim CSRF dan data tambah');
        expect_dashboard(str_contains($html, '<noscript>') && str_contains($html, 'class="add-link-fallback"'), 'Tambah tautan harus tetap tersedia tanpa JavaScript');
        expect_dashboard(str_contains($html, 'type="file" accept="image/png,image/jpeg" required'), 'Logo baru harus diunggah sebagai PNG atau JPG');
        expect_dashboard(str_contains($html, 'Logo/Icon (opsional: ganti)'), 'Logo tautan lama bisa dipertahankan atau diganti');
        expect_dashboard(str_contains($html, '<table id="links-table"'), 'Daftar tautan harus berupa tabel');
        expect_dashboard(str_contains($html, 'class="links-scroll-hint"') && str_contains($html, 'aria-label="Tabel tautan publik; geser ke samping untuk melihat seluruh kolom"'), 'Tabel harus punya petunjuk scroll yang dapat diakses');
        expect_dashboard(str_contains($html, 'aria-label="Edit &lt;script&gt;alert(1)&lt;/script&gt;"'), 'Label aksi harus menjelaskan tautan dan tetap aman');

        expect_dashboard(str_contains($html, 'assets/vendor/datatables/dataTables.min.js') && str_contains($html, 'assets/vendor/sweetalert2/sweetalert2.min.js'), 'DataTables dan SweetAlert2 harus dimuat secara lokal');
        expect_dashboard(str_contains($html, 'id="edit-link-dialog"') && str_contains($html, 'id="edit-link-form"'), 'Edit harus menggunakan dialog');
        expect_dashboard(str_contains($html, 'data-title="&lt;script&gt;alert(1)&lt;/script&gt;"'), 'Data dialog harus di-escape');
        expect_dashboard(str_contains($html, 'name="is_active"') && str_contains($html, 'name="featured"') && str_contains($html, 'name="sort_order"'), 'Dialog edit harus mempertahankan semua pengaturan');
        expect_dashboard(str_contains($html, 'class="edit-link-fallback"'), 'Edit tanpa JavaScript harus tetap tersedia');
        expect_dashboard(str_contains($html, 'data-order="1"') && str_contains($html, 'data-order="2"') && str_contains($html, 'data-order="3"'), 'Urutan lama duplikat harus tampil sebagai posisi unik');
        expect_dashboard(str_contains($html, 'id="edit-link-order" name="sort_order"') && !str_contains($html, 'name="sort_order" type="number"'), 'Posisi harus berupa pilihan terbatas');
        expect_dashboard(str_contains($html, 'Posisi 2 · saat ini: Tokopedia (nonaktif)'), 'Posisi nonaktif harus ikut ditampilkan');
        expect_dashboard(str_contains($html, 'name="order_version" value="' . link_order_version($allLinks) . '"'), 'Form harus mengirim snapshot urutan');
        expect_dashboard((bool) preg_match('~<noscript>\s*<details class="edit-link-fallback">.*?<select name="sort_order" required>.*?Posisi 1 · posisi saat ini.*?</select>~s', $html), 'Form tanpa JavaScript harus memiliki pilihan urutan dan posisi awal');
        expect_dashboard(str_contains($html, 'assets/js/dashboard-links.js?v='), 'Script dialog harus dimuat pada tab tautan');
        expect_dashboard(str_contains($html, 'name="action" value="add"'), 'Tambah tautan harus tersedia');
        expect_dashboard(str_contains($html, 'name="action" value="save"'), 'Simpan tautan harus tersedia');
        expect_dashboard(str_contains($html, 'name="action" value="delete"'), 'Hapus tautan harus tersedia');
        expect_dashboard(str_contains($html, '&lt;script&gt;'), 'Judul tautan harus di-escape');
    }
    if ($tab === 'activity') {
        expect_dashboard(str_contains($html, 'Log Aktivitas Pengunjung'), 'Log aktivitas harus tersedia');
        expect_dashboard(str_contains($html, 'id="activity-table"') && str_contains($html, 'assets/js/dashboard-activity.js?v='), 'Aktivitas harus memakai DataTables');
        expect_dashboard(str_contains($html, 'assets/vendor/datatables/dataTables.min.js') && str_contains($html, 'assets/vendor/datatables/dataTables.dataTables.min.css'), 'DataTables lokal harus dimuat pada aktivitas');
        expect_dashboard(!str_contains($html, 'assets/vendor/sweetalert2/sweetalert2.min.js'), 'SweetAlert tidak perlu dimuat di aktivitas');
        expect_dashboard(str_contains($html, 'id="activity-empty"'), 'Pesan kosong harus tersedia jika JavaScript tidak aktif');
    }
}
// Baris Instagram tersedia meskipun belum ada klik dan tidak mengubah insight.
$tab = 'overview';
$clicks = 0;
$performance = [['title' => 'Instagram', 'icon' => 'instagram', 'count' => 0]];
$topLink = null;
ob_start();
require __DIR__ . '/../templates/admin-dashboard.php';
$instagramHtml = ob_get_clean();
expect_dashboard(str_contains($instagramHtml, 'assets/images/icons/instagram.svg') && str_contains($instagramHtml, '>Instagram</span>'), 'Instagram harus ditampilkan bersama ikonnya');
expect_dashboard(str_contains($instagramHtml, '0,0% kontribusi') && str_contains($instagramHtml, '0 <small>klik</small>'), 'Instagram tanpa klik harus menunjukkan nol');
expect_dashboard(!str_contains($instagramHtml, 'Instagram paling diminati'), 'Instagram nol klik tidak boleh menjadi insight terpopuler');

// Warna desktop pada legenda dan ring harus sama-sama biru muda.
$adminCss = file_get_contents(__DIR__ . '/../assets/css/admin.css');
expect_dashboard(str_contains($adminCss, '.dot-desktop { background: #42a5f5; }'), 'Legenda desktop harus biru muda');
expect_dashboard(str_contains($adminCss, '#42a5f5 var(--mobile-end) var(--desktop-end)'), 'Segmen desktop harus sama dengan warna legenda');

// Warna dan proporsi ring harus tetap konsisten meski ada perangkat lain atau data kosong.
$tab = 'overview';
$devices = ['Mobile' => 3, 'Desktop' => 2, 'Tablet' => 4, 'TV' => 1];
ob_start();
require __DIR__ . '/../templates/admin-dashboard.php';
$deviceHtml = ob_get_clean();
expect_dashboard(str_contains($deviceHtml, '--mobile-end: 30.0000%; --desktop-end: 50.0000%; --tablet-end: 90.0000%;'), 'Segmen harus proporsional terhadap semua interaksi');
foreach (['Mobile' => 'dot-mobile', 'Desktop' => 'dot-desktop', 'Tablet' => 'dot-tablet', 'TV' => 'dot-other'] as $name => $class) {
    expect_dashboard(str_contains($deviceHtml, 'class="' . $class . '" aria-hidden="true"></i>' . $name), 'Legenda perangkat harus sesuai: ' . $name);
}
$devices = ['Desktop' => 1];
ob_start();
require __DIR__ . '/../templates/admin-dashboard.php';
$desktopHtml = ob_get_clean();
expect_dashboard(str_contains($desktopHtml, '--mobile-end: 0.0000%; --desktop-end: 100.0000%; --tablet-end: 100.0000%;'), 'Hanya desktop harus menghasilkan ring penuh biru');
$devices = ['Tablet' => 1];
ob_start();
require __DIR__ . '/../templates/admin-dashboard.php';
$tabletHtml = ob_get_clean();
expect_dashboard(str_contains($tabletHtml, '--mobile-end: 0.0000%; --desktop-end: 0.0000%; --tablet-end: 100.0000%;'), 'Hanya tablet harus menghasilkan ring penuh ungu');
$devices = [];
ob_start();
require __DIR__ . '/../templates/admin-dashboard.php';
$emptyHtml = ob_get_clean();
expect_dashboard(str_contains($emptyHtml, 'device-ring--empty') && str_contains($emptyHtml, 'Belum ada data perangkat'), 'Data kosong harus memakai ring netral');
// Tampilan aktivitas dan tanggal grafik memakai WIB, termasuk saat melewati tahun.
$events = [['target_label' => 'Halaman publik', 'device' => 'Mobile', 'event_type' => 'view', 'created_at' => '2025-12-31 17:00:00', 'visitor_id' => 'visitor']];
$daily = ['2026-01-01' => ['views' => 1, 'clicks' => 0]];
$tab = 'overview';
ob_start();
require __DIR__ . '/../templates/admin-dashboard.php';
$wibOverview = ob_get_clean();
expect_dashboard(str_contains($wibOverview, '01 Jan 00:00 WIB'), 'Aktivitas terbaru harus dikonversi ke WIB');
expect_dashboard(str_contains($wibOverview, 'Tanggal (WIB)') && str_contains($wibOverview, '01 Jan 2026'), 'Tanggal grafik harus memakai kalender WIB');
$tab = 'activity';
ob_start();
require __DIR__ . '/../templates/admin-dashboard.php';
$wibActivity = ob_get_clean();
expect_dashboard(str_contains($wibActivity, 'Waktu (WIB)') && str_contains($wibActivity, '01 Jan 2026 00:00'), 'Log aktivitas harus dikonversi ke WIB');
expect_dashboard(str_contains($wibActivity, 'data-order="2025-12-31 17:00:00"'), 'Urutan aktivitas harus memakai UTC agar urut kronologis');
$events = [];
for ($i = 0; $i < 51; $i++) {
    $events[] = ['target_label' => $i === 0 ? '<script>alert(1)</script>' : 'Halaman ' . $i, 'device' => 'Mobile', 'event_type' => 'view', 'created_at' => '2026-01-01 00:00:00', 'visitor_id' => 'visitor'];
}
ob_start();
require __DIR__ . '/../templates/admin-dashboard.php';
$activityHtml = ob_get_clean();
expect_dashboard(substr_count($activityHtml, 'data-order="2026-01-01 00:00:00"') === 50, 'Hanya 50 event terbaru yang boleh ditampilkan');
expect_dashboard(str_contains($activityHtml, '&lt;script&gt;alert(1)&lt;/script&gt;') && !str_contains($activityHtml, '<script>alert(1)</script>'), 'Label aktivitas harus di-escape');
echo "Tes template dashboard berhasil.\n";
