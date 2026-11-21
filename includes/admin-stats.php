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
    $sql = 'SELECT * FROM analytics_events WHERE is_demo = 0 AND created_at < :end';
    $params = ['end' => $period['end']];
    if ($period['start'] !== null) {
        $sql .= ' AND created_at >= :start';
        $params['start'] = $period['start'];
    }
    $stmt = db()->prepare($sql . ' ORDER BY created_at DESC, id DESC');
    $stmt->execute($params);
    $events = $stmt->fetchAll();
    if ($period['previous_start'] !== null) {
        $stmt = db()->prepare('SELECT event_type, COUNT(*) AS total FROM analytics_events WHERE is_demo = 0 AND created_at >= :start AND created_at < :end GROUP BY event_type');
        $stmt->execute(['start' => $period['previous_start'], 'end' => $period['start']]);
        foreach ($stmt->fetchAll() as $row) {
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
for ($i = $chartDays - 1; $i >= 0; $i--) {
    $daily[gmdate('Y-m-d', strtotime("-$i days"))] = ['views' => 0, 'clicks' => 0];
}
foreach ($events as $event) {
    $date = gmdate('Y-m-d', strtotime($event['created_at']));
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
        if (!isset($performance[$key])) {
            $performance[$key] = ['title' => $event['target_label'] ?: 'Tautan lainnya', 'icon' => in_array($key, ['instagram', 'whatsapp-social'], true) ? ($key === 'instagram' ? 'instagram' : 'whatsapp') : 'link', 'count' => 0];
        }
        $performance[$key]['count']++;
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
