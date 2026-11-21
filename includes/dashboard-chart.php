<?php

declare(strict_types=1);

/** Mengubah data harian menjadi koordinat grafik tanpa menambah data contoh. */
function dashboard_chart(array $daily): array
{
    $values = [];
    $maximum = 0;
    foreach ($daily as $date => $point) {
        $views = max(0, (int) ($point['views'] ?? 0));
        $clicks = max(0, (int) ($point['clicks'] ?? 0));
        $values[] = ['date' => (string) $date, 'views' => $views, 'clicks' => $clicks];
        $maximum = max($maximum, $views, $clicks);
    }

    // Empat interval bulat membuat label skala tetap terbaca untuk data kecil maupun besar.
    $roughStep = max(1, (int) ceil($maximum / 4));
    $magnitude = pow(10, floor(log10($roughStep)));
    $step = (int) (ceil($roughStep / $magnitude) * $magnitude);
    $scale = $step * 4;
    $count = count($values);
    $viewPoints = [];
    $clickPoints = [];
    $points = [];
    foreach ($values as $index => $point) {
        $x = $count > 1 ? $index * 100 / ($count - 1) : 50;
        $viewY = 100 - $point['views'] * 100 / $scale;
        $clickY = 100 - $point['clicks'] * 100 / $scale;
        $coordinate = static fn(float $number): string => number_format($number, 2, '.', '');
        $viewPoints[] = $coordinate($x) . ',' . $coordinate($viewY);
        $clickPoints[] = $coordinate($x) . ',' . $coordinate($clickY);
        $time = strtotime($point['date']);
        $points[] = [
            'x' => round($x, 2), 'viewY' => round($viewY, 2), 'clickY' => round($clickY, 2),
            'date' => $time === false ? $point['date'] : gmdate('d M Y', $time),
            'views' => $point['views'], 'clicks' => $point['clicks'],
        ];
    }

    $labelIndexes = [];
    if ($count > 0) {
        $labelsToShow = min($count, 6);
        for ($index = 0; $index < $labelsToShow; $index++) {
            $labelIndexes[] = $labelsToShow > 1 ? (int) round($index * ($count - 1) / ($labelsToShow - 1)) : 0;
        }
    }
    $labels = [];
    foreach (array_unique($labelIndexes) as $index) {
        $time = strtotime($values[$index]['date']);
        $labels[] = [
            'text' => $time === false ? '' : gmdate('d M', $time),
            'position' => $count > 1 ? round($index * 100 / ($count - 1), 2) : 50,
        ];
    }

    $viewPath = dashboard_smooth_path($points, 'viewY');
    $clickPath = dashboard_smooth_path($points, 'clickY');
    $areaPath = $viewPath !== '' ? $viewPath . ' L ' . end($points)['x'] . ',100 L ' . $points[0]['x'] . ',100 Z' : '';
    return [
        'points' => $points,
        'view_path' => $viewPath,
        'click_path' => $clickPath,
        'area_path' => $areaPath,
        'views' => implode(' ', $viewPoints),
        'clicks' => implode(' ', $clickPoints),
        'area' => $viewPoints ? '0,100 ' . implode(' ', $viewPoints) . ' 100,100' : '',
        'scale' => [$scale, $step * 3, $step * 2, $step, 0],
        'labels' => $labels,
        'has_data' => $maximum > 0,
    ];
}

/** Kurva kubik dengan kontrol horizontal: melalui titik tanpa melewati nilai ekstrem. */
function dashboard_smooth_path(array $points, string $yKey): string
{
    if (!$points) return '';
    $format = static fn(float $number): string => number_format($number, 2, '.', '');
    $path = 'M ' . $format($points[0]['x']) . ',' . $format($points[0][$yKey]);
    if (count($points) === 1) return $path . ' L ' . $format($points[0]['x']) . ',' . $format($points[0][$yKey]);
    for ($i = 1, $count = count($points); $i < $count; $i++) {
        $left = $points[$i - 1];
        $right = $points[$i];
        $middle = ($left['x'] + $right['x']) / 2;
        $path .= ' C ' . $format($middle) . ',' . $format($left[$yKey])
            . ' ' . $format($middle) . ',' . $format($right[$yKey])
            . ' ' . $format($right['x']) . ',' . $format($right[$yKey]);
    }
    return $path;
}
