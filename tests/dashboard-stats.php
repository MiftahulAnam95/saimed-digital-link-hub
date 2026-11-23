<?php

declare(strict_types=1);
date_default_timezone_set('UTC');
function expect_stats(bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); }
function boolval_db($value): bool { return $value === true || $value === 1 || $value === '1'; }
function links(bool $active = false): array { global $testLinks; return $testLinks; }
$testLinks = [['key' => 'shopee', 'title' => 'Shopee', 'icon' => 'shopee']];
class StatsDatabase {
    public function __construct(public array $events) {}
    public function prepare(string $sql): StatsStatement {
        expect_stats(str_contains($sql, 'is_demo = 0'), 'Semua query statistik harus mengecualikan data contoh');
        return new StatsStatement($this->events, $sql);
    }
}
class StatsStatement {
    private array $results = [];
    public function __construct(private array $events, private string $sql) {}
    public function execute(array $params): void {
        $filtered = array_values(array_filter($this->events, static fn($e) => !$e['is_demo'] && $e['created_at'] < $params['end'] && (!isset($params['start']) || $e['created_at'] >= $params['start'])));
        if (str_contains($this->sql, 'GROUP BY')) {
            $counts = [];
            foreach ($filtered as $event) $counts[$event['event_type']] = ($counts[$event['event_type']] ?? 0) + 1;
            $this->results = [];
            foreach ($counts as $type => $count) $this->results[] = ['event_type' => $type, 'total' => $count];
        } else $this->results = $filtered;
    }
    public function fetchAll(): array { return $this->results; }
}
function db(): StatsDatabase { global $testDb; return $testDb; }
function event_at(int $daysAgo, string $type, ?string $key, string $label, bool $demo = false): array {
    return ['created_at' => gmdate('Y-m-d H:i:s', strtotime(gmdate('Y-m-d', time() + 7 * 3600) . ' 05:00:00 UTC') - $daysAgo * 86400), 'event_type' => $type, 'link_key' => $key, 'target_label' => $label, 'device' => 'Mobile', 'browser' => 'Chrome', 'visitor_id' => '1', 'is_demo' => $demo];
}
$testDb = new StatsDatabase([
    event_at(0, 'view', null, 'Halaman'), event_at(1, 'link_click', 'shopee', 'Shopee'),
    event_at(2, 'social_click', 'instagram', 'Instagram'), event_at(1, 'link_click', 'demo-link', 'Tautan contoh', true), event_at(3, 'link_action', 'shopee', 'Salin'),
    event_at(8, 'view', null, 'Halaman'), event_at(8, 'link_click', 'shopee', 'Shopee'), event_at(8, 'social_click', 'instagram', 'Instagram'), event_at(8, 'view', null, 'Kunjungan contoh', true),
    event_at(100, 'link_click', 'deleted', 'Tautan lama'), event_at(-2, 'view', null, 'Masa depan'),
]);
function stats_for(string $value): array {
    $_GET = ['days' => $value];
    require __DIR__ . '/../includes/admin-stats.php';
    return compact('days', 'views', 'clicks', 'previousViews', 'previousClicks', 'performance', 'topLink', 'chartDays', 'daily', 'events', 'devices', 'browsers');
}
$seven = stats_for('7');
expect_stats($seven['views'] === 1 && $seven['clicks'] === 2, 'Hanya event dalam periode boleh dihitung');
expect_stats($seven['previousViews'] === 1 && $seven['previousClicks'] === 2, 'Periode sebelumnya salah');
expect_stats($seven['performance']['shopee']['count'] === 1 && $seven['performance']['social:instagram']['count'] === 1, 'Klik sosial dan tautan harus masuk laporan');
expect_stats($seven['topLink']['count'] === 1 && !isset($seven['performance']['demo-link']), 'Insight tidak boleh memasukkan data contoh');
expect_stats(count($seven['events']) === 4 && $seven['devices']['Mobile'] === 3 && $seven['browsers']['Chrome'] === 3, 'Aktivitas dan audiens tidak boleh memasukkan data contoh');
expect_stats(array_sum(array_column($seven['daily'], 'clicks')) === 2, 'Grafik tidak boleh memasukkan data contoh');
$all = stats_for('all');
expect_stats($all['views'] === 2 && $all['clicks'] === 5, 'Semua waktu harus mengecualikan masa depan');
expect_stats($all['performance']['social:instagram']['count'] === 2, 'Klik Instagram lama harus dihitung sesuai periode');
expect_stats($all['chartDays'] === 30 && count($all['daily']) === 30, 'Grafik semua waktu harus memakai 30 hari terakhir');
expect_stats($all['performance']['deleted']['count'] === 1, 'Klik tautan yang dihapus harus tetap dihitung');
expect_stats($all['previousViews'] === 0 && $all['previousClicks'] === 0, 'Semua waktu tidak memiliki periode sebelumnya');
$ninety = stats_for('90');
expect_stats($ninety['clicks'] === 4 && $ninety['chartDays'] === 90, 'Periode 90 hari salah');
// Peristiwa pada tengah malam WIB harus masuk hari dan periode yang benar.
require_once __DIR__ . '/../includes/dashboard-data.php';
$period = dashboard_period('7');
$start = strtotime($period['start'] . ' UTC');
$todayStart = strtotime($period['today'] . ' 00:00:00 Asia/Jakarta');
$first = event_at(0, 'view', null, 'boundary');
$beforePeriod = $first; $beforePeriod['created_at'] = gmdate('Y-m-d H:i:s', $start - 1);
$atPeriod = $first; $atPeriod['created_at'] = gmdate('Y-m-d H:i:s', $start);
$beforeToday = $first; $beforeToday['created_at'] = gmdate('Y-m-d H:i:s', $todayStart - 1);
$atToday = $first; $atToday['created_at'] = gmdate('Y-m-d H:i:s', $todayStart);
$testDb = new StatsDatabase([$beforePeriod, $atPeriod, $beforeToday, $atToday]);
$boundary = stats_for('7');
$yesterday = (new DateTimeImmutable($period['today'], new DateTimeZone('Asia/Jakarta')))->modify('-1 day')->format('Y-m-d');
expect_stats($boundary['views'] === 3 && $boundary['previousViews'] === 1, 'Batas periode WIB salah');
expect_stats($boundary['daily'][$period['today']]['views'] === 1 && $boundary['daily'][$yesterday]['views'] === 1, 'Pengelompokan hari WIB salah');
$testDb = new StatsDatabase([event_at(0, 'view', null, 'Contoh saja', true)]);
$onlyDemo = stats_for('7');
expect_stats($onlyDemo['views'] === 0 && $onlyDemo['clicks'] === 0 && !$onlyDemo['events'], 'Database yang hanya berisi data contoh harus menampilkan kondisi kosong');
expect_stats($onlyDemo['performance']['social:instagram'] === ['title' => 'Instagram', 'icon' => 'instagram', 'count' => 0], 'Instagram harus tampil tanpa data contoh atau klik');
expect_stats($onlyDemo['topLink'] === null, 'Instagram nol klik tidak boleh menjadi tautan terpopuler');
$testLinks[] = ['key' => 'instagram', 'title' => 'Instagram Toko', 'icon' => 'link'];
$testDb = new StatsDatabase([
    event_at(0, 'social_click', 'instagram', 'Instagram'),
    event_at(0, 'link_click', 'instagram', 'Instagram Toko'),
]);
$distinct = stats_for('7');
expect_stats($distinct['performance']['social:instagram']['count'] === 1, 'Klik ikon Instagram profil harus dihitung');
expect_stats($distinct['performance']['instagram']['count'] === 1, 'Klik kartu berkunci sama harus tetap terpisah');
expect_stats($distinct['clicks'] === 2 && array_sum(array_column($distinct['performance'], 'count')) === 2, 'Klik tidak boleh dihitung ganda');
$testDb = new StatsDatabase([
    event_at(0, 'social_click', 'instagram', 'Instagram'),
    event_at(0, 'link_click', 'social:instagram', 'Tautan lain'),
]);
$colliding = stats_for('7');
expect_stats($colliding['performance']['social:instagram:']['count'] === 1 && $colliding['performance']['social:instagram']['count'] === 1, 'Kunci internal tidak boleh mencampur event lain');
echo "Tes statistik dashboard berhasil.\n";
