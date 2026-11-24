<?php

declare(strict_types=1);

// Simulasi MySQLi dan respons HTTP untuk memeriksa parameter tracking.
$scenario = $argv[1] ?? '';
if (!in_array($scenario, ['valid', 'null', 'invalid', 'method'], true)) {
    throw new InvalidArgumentException('Pilih valid, null, invalid, atau method.');
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
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$body = match ($scenario) {
    'valid' => json_encode(['eventType' => 'social_click', 'linkKey' => "x' OR 1=1 --", 'targetLabel' => 'Instagram', 'visitorId' => 'visitor', 'path' => '/']),
    'null' => json_encode(['eventType' => 'view']),
    'invalid' => json_encode(['eventType' => 'bukan-event']),
    default => '{}',
};
register_shutdown_function(static function () use ($scenario, $trackDb): void {
    global $response, $responseStatus;
    if ($scenario === 'invalid' || $scenario === 'method') {
        if ($responseStatus !== ($scenario === 'invalid' ? 400 : 405) || $trackDb->params) throw new RuntimeException('Request tidak valid harus ditolak sebelum query');
    } else {
        if ($responseStatus !== 200 || empty($response['tracked']) || count($trackDb->params) !== 11
            || substr_count($trackDb->sql, '?') !== 11) throw new RuntimeException('Query tracking atau jumlah parameter salah');
        if ($scenario === 'valid' && ($trackDb->params[1] !== "x' OR 1=1 --" || $trackDb->params[2] !== "x' OR 1=1 --"
            || str_contains($trackDb->sql, "x' OR 1=1 --") || $trackDb->params[8] !== 'Mobile')) throw new RuntimeException('Input harus tetap sebagai parameter');
        if ($scenario === 'null' && ($trackDb->params[1] !== null || $trackDb->params[2] !== null || $trackDb->params[3] !== null)) throw new RuntimeException('Nilai opsional kosong harus tetap null');
    }
    echo "Tes tracking MySQLi $scenario berhasil.\n";
});
track($body);
throw new RuntimeException('Tracking harus menghasilkan respons');
