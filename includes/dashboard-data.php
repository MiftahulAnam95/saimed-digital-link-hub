<?php

declare(strict_types=1);

/** Periode berdasarkan hari kalender UTC; batas akhir bersifat eksklusif. */
function dashboard_period($value, ?int $now = null): array
{
    $value = is_scalar($value) ? (string) $value : '';
    $days = in_array($value, ['7', '14', '30', '90', 'all'], true) ? $value : '7';
    $now = $now ?? time();
    $today = gmmktime(0, 0, 0, (int) gmdate('n', $now), (int) gmdate('j', $now), (int) gmdate('Y', $now));
    $start = $days === 'all' ? null : gmdate('Y-m-d H:i:s', $today - ((int) $days - 1) * 86400);
    return [
        'days' => $days,
        'start' => $start,
        'previous_start' => $start === null ? null : gmdate('Y-m-d H:i:s', strtotime($start . ' UTC') - (int) $days * 86400),
        'end' => gmdate('Y-m-d H:i:s', $today + 86400),
    ];
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
        $event['created_at'] ?? '', $event['event_type'] ?? '',
        $event['target_label'] ?? '', $event['device'] ?? '',
        boolval_db($event['is_demo'] ?? false) ? 'Ya' : 'Tidak',
    ];
    return array_map('dashboard_csv_cell', $row);
}
