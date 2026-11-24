<?php

declare(strict_types=1);

// Dipanggil hanya setelah autentikasi Super Admin pada admin.php.
require_once __DIR__ . '/dashboard-data.php';
$period = dashboard_period($_GET['days'] ?? '7');
try {
    $sql = 'SELECT created_at, event_type, target_label, device FROM analytics_events WHERE is_demo = 0 AND created_at < ?';
    $params = [$period['end']];
    if ($period['start'] !== null) {
        $sql .= ' AND created_at >= ?';
        $params[] = $period['start'];
    }
    $sql .= ' ORDER BY created_at ASC, id ASC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->get_result();
} catch (Throwable $e) {
    error_log('SAIMED analytics export: database tidak tersedia');
    http_response_code(503);
    exit('Ekspor tidak tersedia. Periksa koneksi database.');
}
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="saimed-analytics-' . ($period['days'] === 'all' ? 'semua' : $period['days'] . '-hari') . '.csv"');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
$out = fopen('php://output', 'wb');
fwrite($out, "\xEF\xBB\xBFsep=;\r\n");
fputcsv($out, ['Waktu (WIB)', 'Aktivitas', 'Nama tautan', 'Perangkat'], ';', '"', '');
while ($event = $result->fetch_assoc()) {
    fputcsv($out, dashboard_csv_row($event), ';', '"', '');
}
fclose($out);
exit;
