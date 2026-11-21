<?php

declare(strict_types=1);

/** Periode memakai hari kalender WIB; parameter SQL tetap dalam UTC. */
function dashboard_period($value, ?int $now = null): array
{
    $value = is_scalar($value) ? (string) $value : '';
    $days = in_array($value, ['7', '14', '30', '90', 'all'], true) ? $value : '7';
    $wib = new DateTimeZone('Asia/Jakarta');
    $utc = new DateTimeZone('UTC');
    $today = (new DateTimeImmutable('@' . ($now ?? time())))->setTimezone($wib)->setTime(0, 0);
    $start = $days === 'all' ? null : $today->modify('-' . ((int) $days - 1) . ' days');
    return [
        'days' => $days,
        'today' => $today->format('Y-m-d'),
        'start' => $start ? $start->setTimezone($utc)->format('Y-m-d H:i:s') : null,
        'previous_start' => $start ? $start->modify('-' . (int) $days . ' days')->setTimezone($utc)->format('Y-m-d H:i:s') : null,
        'end' => $today->modify('+1 day')->setTimezone($utc)->format('Y-m-d H:i:s'),
    ];
}

/** Waktu DATETIME analytics tersimpan dalam UTC, tampilkan dalam WIB. */
function dashboard_wib($value, string $format): string
{
    if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/D', $value)) return '';
    $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value, new DateTimeZone('UTC'));
    $errors = DateTimeImmutable::getLastErrors();
    if (!$date || ($errors && ($errors['warning_count'] || $errors['error_count']))) return '';
    return $date->setTimezone(new DateTimeZone('Asia/Jakarta'))->format($format);
}

/** Daftar tanggal grafik berdasarkan kalender WIB, termasuk hari tanpa aktivitas. */
function dashboard_daily_dates(int $days, string $today): array
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $today, new DateTimeZone('Asia/Jakarta'));
    if (!$date || $days < 1) return [];
    $dates = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $dates[] = $date->modify('-' . $i . ' days')->format('Y-m-d');
    }
    return $dates;
}

function dashboard_trend(int $current, int $previous): string
{
    if ($previous === 0) return '— vs periode sebelumnya';
    $change = ($current - $previous) / $previous * 100;
    return ($change > 0 ? '+' : '') . number_format($change, 1, ',', '.') . '% vs periode sebelumnya';
}

/** Lindungi nilai dari eksekusi formula saat CSV dibuka di spreadsheet. */
function dashboard_csv_cell($value): string
{
    $text = (string) ($value ?? '');
    return preg_match('/^[\x00-\x20]*[=+@\-]/', $text) || preg_match('/^[\t\r\n]/', $text) ? "'" . $text : $text;
}

function dashboard_csv_row(array $event): array
{
    $row = [
        dashboard_wib($event['created_at'] ?? null, 'Y-m-d H:i:s'), $event['event_type'] ?? '',
        $event['target_label'] ?? '', $event['device'] ?? '',
    ];
    return array_map('dashboard_csv_cell', $row);
}
