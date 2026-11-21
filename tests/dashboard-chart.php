<?php

declare(strict_types=1);

require __DIR__ . '/../includes/dashboard-chart.php';

function expect_chart(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$daily = [
    '2026-01-01' => ['views' => 10, 'clicks' => 2],
    '2026-01-02' => ['views' => 20, 'clicks' => 5],
    '2026-01-03' => ['views' => 0, 'clicks' => 1],
];
$chart = dashboard_chart($daily);
expect_chart($chart['has_data'], 'Grafik harus mengenali data');
expect_chart($chart['scale'] === [20, 15, 10, 5, 0], 'Skala harus sesuai data terbesar');
expect_chart($chart['views'] === '0.00,50.00 50.00,0.00 100.00,100.00', 'Koordinat kunjungan harus sesuai data');
expect_chart($chart['clicks'] === '0.00,90.00 50.00,75.00 100.00,95.00', 'Koordinat klik harus sesuai data');
expect_chart($chart['view_path'] === 'M 0.00,50.00 C 25.00,50.00 25.00,0.00 50.00,0.00 C 75.00,0.00 75.00,100.00 100.00,100.00', 'Kurva harus melalui tiap titik tanpa melampaui nilai');
expect_chart($chart['points'][1]['views'] === 20 && $chart['points'][1]['clicks'] === 5 && $chart['points'][1]['date'] === '02 Jan 2026', 'Data hover harus sesuai titik harian');
expect_chart(str_ends_with($chart['area_path'], 'L 100,100 L 0,100 Z'), 'Area harus mengikuti kurva');
expect_chart($chart['labels'][1]['position'] === 50.0 && $chart['labels'][1]['text'] === '02 Jan', 'Label harus sejajar dengan titik');
$empty = dashboard_chart(['2026-01-01' => ['views' => 0, 'clicks' => 0]]);
expect_chart(!$empty['has_data'] && $empty['scale'][0] > 0, 'Data kosong tidak boleh menghasilkan pembagian nol');
expect_chart(dashboard_chart([])['area_path'] === '' && dashboard_chart([])['points'] === [], 'Data kosong tidak boleh membuat grafik palsu');
$single = dashboard_chart(['2026-01-01' => ['views' => 2, 'clicks' => 1]]);
expect_chart($single['points'][0]['x'] === 50.0 && str_contains($single['view_path'], ' L '), 'Satu titik harus dapat ditampilkan dan disentuh');
foreach ([7, 14, 30, 90] as $days) {
    $series = [];
    for ($i = 0; $i < $days; $i++) {
        $series[gmdate('Y-m-d', strtotime('2026-01-01 +' . $i . ' days'))] = ['views' => $i, 'clicks' => 0];
    }
    $result = dashboard_chart($series);
    expect_chart(count(explode(' ', $result['views'])) === $days, 'Setiap hari harus memiliki titik');
    expect_chart(count($result['labels']) === 6, 'Grafik harus membatasi jumlah label');
    expect_chart($result['labels'][0]['position'] === 0.0 && $result['labels'][5]['position'] === 100.0, 'Label awal dan akhir harus sejajar');
}
echo "Tes grafik dashboard berhasil.\n";
