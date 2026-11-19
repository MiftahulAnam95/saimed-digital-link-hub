<?php

declare(strict_types=1);

// Menyiapkan ringkasan dan aktivitas untuk dashboard.
$days = in_array((int) ($_GET['days'] ?? 7), [7, 14, 30], true)
    ? (int) ($_GET['days'] ?? 7)
    : 7;
$allLinks = links();
$events = [];
$dbError = false;
try {
    $stmt = db()->prepare(
        'SELECT * FROM analytics_events WHERE created_at >= :start ORDER BY created_at DESC',
    );
    $stmt->execute([
        'start' => gmdate(
            'Y-m-d 00:00:00+00',
            strtotime('-' . ($days - 1) . ' days'),
        ),
    ]);
    $events = $stmt->fetchAll();
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
$daily = [];
$demoCount = 0;
for ($i = $days - 1; $i >= 0; $i--) {
    $daily[gmdate('Y-m-d', strtotime("-$i days"))] = [
        'views' => 0,
        'clicks' => 0,
    ];
}
foreach ($events as $event) {
    $date = gmdate('Y-m-d', strtotime($event['created_at']));
    $isView = $event['event_type'] === 'view';
    $isClick = in_array(
        $event['event_type'],
        ['link_click', 'social_click'],
        true,
    );
    if ($isView) {
        $views++;
    }
    if ($isClick) {
        $clicks++;
    }
    if (isset($daily[$date])) {
        if ($isView) {
            $daily[$date]['views']++;
        }
        if ($isClick) {
            $daily[$date]['clicks']++;
        }
    }
    if ($isView || $isClick) {
        $visitors[$event['visitor_id']] = true;
        $devices[$event['device']] = ($devices[$event['device']] ?? 0) + 1;
        $browsers[$event['browser']] = ($browsers[$event['browser']] ?? 0) + 1;
    }
    if ($event['event_type'] === 'link_click' && $event['link_key']) {
        $linkClicks[$event['link_key']] =
            ($linkClicks[$event['link_key']] ?? 0) + 1;
    }
    if (boolval_db($event['is_demo'])) {
        $demoCount++;
    }
}
arsort($devices);
arsort($browsers);

