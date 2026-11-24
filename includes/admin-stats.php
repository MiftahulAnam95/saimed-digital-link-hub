<?php

declare(strict_types=1);

require_once __DIR__ . '/dashboard-data.php';
$period = dashboard_period($_GET['days'] ?? '7');
$days = $period['days'];
$periodLabel = $days === 'all' ? 'Semua waktu' : $days . ' hari terakhir';
$chartDays = $days === 'all' ? 30 : (int) $days;
$allLinks = links();
$events = [];
$previousViews = 0;
$previousClicks = 0;
$dbError = false;
try {
    $sql = 'SELECT * FROM analytics_events WHERE is_demo = 0 AND created_at < ?';
    $params = [$period['end']];
    if ($period['start'] !== null) {
        $sql .= ' AND created_at >= ?';
        $params[] = $period['start'];
    }
    $stmt = db()->prepare($sql . ' ORDER BY created_at DESC, id DESC');
    $stmt->execute($params);
    $events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    if ($period['previous_start'] !== null) {
        $stmt = db()->prepare('SELECT event_type, COUNT(*) AS total FROM analytics_events WHERE is_demo = 0 AND created_at >= ? AND created_at < ? GROUP BY event_type');
        $stmt->execute([$period['previous_start'], $period['start']]);
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
            if ($row['event_type'] === 'view') $previousViews += (int) $row['total'];
            if (in_array($row['event_type'], ['link_click', 'social_click'], true)) $previousClicks += (int) $row['total'];
        }
    }
} catch (Throwable $e) {
    $dbError = true;
    error_log('SAIMED analytics: ' . $e->getMessage());
}
$views = 0;
$clicks = 0;
$visitors = [];
$devices = [];
$browsers = [];
$linkClicks = [];
$performance = [];
$daily = [];
foreach ($allLinks as $link) {
    $performance[$link['key']] = ['title' => $link['title'], 'icon' => $link['icon'], 'count' => 0];
}
// Ikon Instagram profil selalu tersedia, walaupun belum menerima klik.
// Pisahkan kunci internalnya dari tautan CRUD dengan nama kunci yang sama.
$instagramPerformanceKey = 'social:instagram';
$eventKeys = array_column($events, 'link_key');
while (isset($performance[$instagramPerformanceKey]) || in_array($instagramPerformanceKey, $eventKeys, true)) {
    $instagramPerformanceKey .= ':';
}
unset($eventKeys);
$performance[$instagramPerformanceKey] = ['title' => 'Instagram', 'icon' => 'instagram', 'count' => 0];
foreach (dashboard_daily_dates($chartDays, $period['today']) as $date) {
    $daily[$date] = ['views' => 0, 'clicks' => 0];
}
foreach ($events as $event) {
    $date = dashboard_wib($event['created_at'], 'Y-m-d');
    $isView = $event['event_type'] === 'view';
    $isClick = in_array($event['event_type'], ['link_click', 'social_click'], true);
    if ($isView) $views++;
    if ($isClick) $clicks++;
    if (isset($daily[$date])) {
        if ($isView) $daily[$date]['views']++;
        if ($isClick) $daily[$date]['clicks']++;
    }
    if ($isView || $isClick) {
        $visitors[$event['visitor_id']] = true;
        $devices[$event['device']] = ($devices[$event['device']] ?? 0) + 1;
        $browsers[$event['browser']] = ($browsers[$event['browser']] ?? 0) + 1;
    }
    if ($isClick) {
        $key = $event['link_key'] ?: '__unattributed__';
        $performanceKey = $event['event_type'] === 'social_click' && $key === 'instagram'
            ? $instagramPerformanceKey : $key;
        if (!isset($performance[$performanceKey])) {
            $performance[$performanceKey] = ['title' => $event['target_label'] ?: 'Tautan lainnya', 'icon' => in_array($key, ['instagram', 'whatsapp-social'], true) ? ($key === 'instagram' ? 'instagram' : 'whatsapp') : 'link', 'count' => 0];
        }
        $performance[$performanceKey]['count']++;
        $linkClicks[$key] = ($linkClicks[$key] ?? 0) + 1;
    }
}
arsort($devices);
arsort($browsers);
uasort($performance, static fn(array $a, array $b): int => $b['count'] <=> $a['count']);
$topLink = null;
foreach ($performance as $item) {
    if ($item['count'] > 0) { $topLink = $item; break; }
}
