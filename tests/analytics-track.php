<?php

declare(strict_types=1);

// Simulasi MySQLi dan respons HTTP untuk memeriksa parameter tracking.
$scenario = $argv[1] ?? '';
if (!in_array($scenario, ['valid', 'null', 'invalid', 'method', 'bad_ip', 'missing_ip'], true)) {
    throw new InvalidArgumentException('Pilih skenario yang tersedia.');
}
require __DIR__ . '/../includes/analytics.php';
class TrackFakeDb {
    public array $params = [];
    public string $sql = '';
    public function prepare(string $sql): TrackFakeStatement {
        $this->sql = $sql;
        return new TrackFakeStatement($this);
    }
}
class TrackFakeStatement {
    public function __construct(private TrackFakeDb $db) {}
    public function execute(array $params): bool { $this->db->params = $params; return true; }
}
$trackDb = new TrackFakeDb();
function db(): TrackFakeDb { global $trackDb; return $trackDb; }
function json_response(array $data, int $status = 200): void {
    global $response, $responseStatus;
    $response = $data;
    $responseStatus = $status;
    exit;
}
$_SERVER['REQUEST_METHOD'] = $scenario === 'method' ? 'GET' : 'POST';
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (iPhone) Safari/605.1';
$_SERVER['REMOTE_ADDR'] = match ($scenario) {
    'null' => '2001:db8:85a3:8d3:1319:8a2e:370:7334',
    'bad_ip' => 'bukan-ip',
    default => '192.0.2.15',
};
if ($scenario === 'missing_ip') unset($_SERVER['REMOTE_ADDR']);
// Header dan nilai dari klien tidak dapat dipercaya sebagai sumber alamat IP.
$_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.99';
$body = match ($scenario) {
    'valid' => json_encode(['eventType' => 'social_click', 'linkKey' => "x' OR 1=1 --", 'targetLabel' => 'Instagram', 'visitorId' => 'visitor', 'path' => '/', 'ip_address' => '203.0.113.99']),
    'null', 'bad_ip', 'missing_ip' => json_encode(['eventType' => 'view']),
    'invalid' => json_encode(['eventType' => 'bukan-event']),
    default => '{}',
};
register_shutdown_function(static function () use ($scenario, $trackDb): void {
    global $response, $responseStatus;
    if ($scenario === 'invalid' || $scenario === 'method') {
        if ($responseStatus !== ($scenario === 'invalid' ? 400 : 405) || $trackDb->params) throw new RuntimeException('Request tidak valid harus ditolak sebelum query');
    } else {
        if ($responseStatus !== 200 || empty($response['tracked']) || count($trackDb->params) !== 12
            || substr_count($trackDb->sql, '?') !== 12) throw new RuntimeException('Query tracking atau jumlah parameter salah');
        if ($scenario === 'valid' && ($trackDb->params[1] !== "x' OR 1=1 --" || $trackDb->params[2] !== "x' OR 1=1 --"
            || str_contains($trackDb->sql, "x' OR 1=1 --") || $trackDb->params[9] !== 'Mobile')) throw new RuntimeException('Input harus tetap sebagai parameter');
        if ($scenario === 'null' && ($trackDb->params[1] !== null || $trackDb->params[2] !== null || $trackDb->params[3] !== null)) throw new RuntimeException('Nilai opsional kosong harus tetap null');
        $expectedIp = match ($scenario) {
            'valid' => '192.0.2.15', 'null' => '2001:db8:85a3:8d3:1319:8a2e:370:7334', default => null,
        };
        if (!str_contains($trackDb->sql, 'ip_address') || $trackDb->params[5] !== $expectedIp) throw new RuntimeException('IP harus berasal dari REMOTE_ADDR yang valid');
    }
    echo "Tes tracking MySQLi $scenario berhasil.\n";
});
track($body);
throw new RuntimeException('Tracking harus menghasilkan respons');
