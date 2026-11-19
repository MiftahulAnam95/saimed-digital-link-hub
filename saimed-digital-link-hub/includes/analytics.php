<?php

declare(strict_types=1);

/** Mengambil informasi perangkat dari user agent pengunjung. */
function parse_agent(string $userAgent): array
{
    if (preg_match('/ipad|tablet/i', $userAgent)) {
        $device = 'Tablet';
    } elseif (preg_match('/iphone|android|mobile|windows phone/i', $userAgent)) {
        $device = 'Mobile';
    } else {
        $device = 'Desktop';
    }

    $browserPatterns = [
        'Edge' => '/edg\//i',
        'Opera' => '/opr\/|opera/i',
        'Firefox' => '/firefox\//i',
        'Chrome' => '/chrome\/|crios\//i',
        'Safari' => '/safari\//i',
    ];
    $osPatterns = [
        'Android' => '/android/i',
        'iOS' => '/iphone|ipad|ipod/i',
        'Windows' => '/windows/i',
        'macOS' => '/mac os|macintosh/i',
        'Linux' => '/linux/i',
    ];

    $browser = 'Lainnya';
    foreach ($browserPatterns as $name => $pattern) {
        if (preg_match($pattern, $userAgent)) {
            $browser = $name;
            break;
        }
    }

    $os = 'Lainnya';
    foreach ($osPatterns as $name => $pattern) {
        if (preg_match($pattern, $userAgent)) {
            $os = $name;
            break;
        }
    }

    return [$device, $browser, $os];
}

function clean($value, int $maxLength): ?string
{
    if (!is_string($value) || trim($value) === '') {
        return null;
    }

    return substr(trim($value), 0, $maxLength);
}

/** Endpoint untuk mencatat kunjungan dan klik dari halaman publik. */
function track(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(['error' => 'Metode tidak diizinkan'], 405);
    }

    $body = json_decode(file_get_contents('php://input'), true);
    $allowedEvents = ['view', 'link_click', 'social_click', 'link_action'];
    if (!is_array($body) || !in_array($body['eventType'] ?? null, $allowedEvents, true)) {
        json_response(['ok' => false, 'error' => 'Jenis event tidak valid'], 400);
    }

    $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 700);
    [$device, $browser, $os] = parse_agent($userAgent);
    $linkKey = clean($body['linkKey'] ?? null, 120);
    $fallbackId = 'anon-' . substr(hash(
        'sha256',
        ($_SERVER['REMOTE_ADDR'] ?? '') . $userAgent . gmdate('Y-m-d')
    ), 0, 24);

    try {
        $sql = 'INSERT INTO analytics_events
            (event_type, link_id, link_key, target_label, visitor_id, path, referrer,
             user_agent, device, browser, os)
            VALUES
            (:event, (SELECT id FROM links WHERE `key` = :lookup), :key, :label, :visitor,
             :path, :referrer, :ua, :device, :browser, :os)';
        $statement = db()->prepare($sql);
        $statement->execute([
            'event' => $body['eventType'],
            'lookup' => $linkKey,
            'key' => $linkKey,
            'label' => clean($body['targetLabel'] ?? null, 180),
            'visitor' => clean($body['visitorId'] ?? null, 120) ?? $fallbackId,
            'path' => clean($body['path'] ?? null, 240) ?? '/',
            'referrer' => clean($body['referrer'] ?? null, 500),
            'ua' => $userAgent,
            'device' => $device,
            'browser' => $browser,
            'os' => $os,
        ]);

        json_response(['ok' => true, 'tracked' => true]);
    } catch (Throwable $exception) {
        error_log('SAIMED tracking: ' . $exception->getMessage());
        json_response(['ok' => true, 'tracked' => false], 202);
    }
}
