<?php

declare(strict_types=1);
require __DIR__ . '/../app.php';
require __DIR__ . '/../includes/dashboard-data.php';
function expect_data(bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); }
$now = strtotime('2026-01-31 12:30:00 UTC');
$p = dashboard_period('7', $now);
expect_data($p['start'] === '2026-01-24 17:00:00' && $p['end'] === '2026-01-31 17:00:00', 'Batas periode WIB dalam UTC salah');
expect_data($p['previous_start'] === '2026-01-17 17:00:00', 'Periode sebelumnya salah');
$beforeMidnight = dashboard_period('7', strtotime('2025-12-31 16:59:59 UTC'));
$afterMidnight = dashboard_period('7', strtotime('2025-12-31 17:00:00 UTC'));
expect_data($beforeMidnight['today'] === '2025-12-31' && $beforeMidnight['end'] === '2025-12-31 17:00:00', 'Menjelang tengah malam WIB salah');
expect_data($afterMidnight['today'] === '2026-01-01' && $afterMidnight['end'] === '2026-01-01 17:00:00', 'Pergantian tahun WIB salah');
expect_data(dashboard_wib('2025-12-31 16:59:59', 'Y-m-d H:i:s') === '2025-12-31 23:59:59', 'Konversi sebelum tengah malam salah');
expect_data(dashboard_wib('2025-12-31 17:00:00', 'Y-m-d H:i:s') === '2026-01-01 00:00:00', 'Konversi melewati tahun salah');
expect_data(dashboard_wib('2026-02-30 00:00:00', 'Y-m-d H:i:s') === '' && dashboard_wib("invalid\0", 'Y-m-d') === '', 'Waktu tidak valid harus ditangani');
expect_data(dashboard_daily_dates(3, '2026-01-01') === ['2025-12-30', '2025-12-31', '2026-01-01'], 'Tanggal grafik WIB salah');
expect_data(dashboard_period('90', $now)['days'] === '90', 'Periode 90 hari tidak tersedia');
expect_data(dashboard_period('all', $now)['start'] === null && dashboard_period('all', $now)['previous_start'] === null, 'Semua waktu harus tanpa batas awal');
expect_data(dashboard_period(['all'], $now)['days'] === '7', 'Periode tidak valid harus ditolak');
expect_data(dashboard_trend(20, 10) === '+100,0% vs periode sebelumnya', 'Tren positif salah');
expect_data(dashboard_trend(5, 10) === '-50,0% vs periode sebelumnya', 'Tren negatif salah');
expect_data(dashboard_trend(0, 0) === '— vs periode sebelumnya', 'Pembagi nol harus ditangani');
foreach (['=1+1', '+SUM(A1)', '-1+2', '@cmd', "  =cmd", "\t@cmd", "\rtext"] as $value) {
    expect_data(str_starts_with(dashboard_csv_cell($value), "'"), 'Formula spreadsheet harus dinetralkan');
}
expect_data(dashboard_csv_cell('Biasa') === 'Biasa', 'Teks biasa harus tetap');
foreach ([null, '', 'Label lama'] as $label) {
    $view = ['event_type' => 'view', 'target_label' => $label];
    expect_data(dashboard_event_label($view) === 'Landing Page', 'Aktivitas view harus berlabel Landing Page');
    expect_data(dashboard_csv_row($view)[2] === 'Landing Page', 'CSV harus menampilkan Landing Page');
}
expect_data(dashboard_event_label(['event_type' => 'social_click', 'target_label' => 'Instagram']) === 'Instagram', 'Label klik harus tetap');
expect_data(dashboard_event_label(['event_type' => 'link_click']) === '' && dashboard_event_label(['event_type' => 'link_click'], 'Halaman publik') === 'Halaman publik', 'Fallback non-view harus tetap');
$row = dashboard_csv_row(['created_at' => '2026-01-01 00:00:00', 'event_type' => 'link_click', 'target_label' => '=cmd', 'device' => 'Mobile', 'visitor_id' => 'secret']);
expect_data($row === ['2026-01-01 07:00:00', 'link_click', "'=cmd", 'Mobile'], 'Kolom ekspor salah atau membocorkan ID');
echo "Tes data dashboard berhasil.\n";
