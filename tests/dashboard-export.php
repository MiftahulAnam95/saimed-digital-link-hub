<?php

declare(strict_types=1);

require __DIR__ . '/../includes/links.php';
$_GET = ['days' => '90'];
class ExportTestDb {
    public function prepare(string $sql): ExportTestStatement {
        if (!str_contains($sql, 'created_at >= :start') || !str_contains($sql, 'created_at < :end')) throw new RuntimeException('Filter ekspor hilang');
        return new ExportTestStatement();
    }
}
class ExportTestStatement {
    private int $index = 0;
    public function execute(array $params): void {
        if (!isset($params['start'], $params['end'])) throw new RuntimeException('Parameter ekspor hilang');
    }
    public function fetch(): mixed {
        if ($this->index++) return false;
        return ['created_at' => '2026-01-01 12:00:00', 'event_type' => 'link_click', 'target_label' => '=SUM(1;2)', 'device' => 'Mobile', 'is_demo' => 0];
    }
}
function db(): ExportTestDb { return new ExportTestDb(); }
ob_start();
register_shutdown_function(static function (): void {
    $csv = ob_get_clean();
    $expected = "\xEF\xBB\xBFsep=;\r\n";
    if (!str_starts_with($csv, $expected)) throw new RuntimeException('Format CSV salah');
    $stream = fopen('php://temp', 'w+');
    fwrite($stream, substr($csv, strlen($expected)));
    rewind($stream);
    $header = fgetcsv($stream, 0, ';', '"', '');
    $row = fgetcsv($stream, 0, ';', '"', '');
    fclose($stream);
    if ($header !== ['Waktu (UTC)', 'Aktivitas', 'Nama tautan', 'Perangkat', 'Data contoh']
        || $row !== ['2026-01-01 12:00:00', 'link_click', "'=SUM(1;2)", 'Mobile', 'Tidak']) {
        throw new RuntimeException('Konten CSV atau perlindungan formula salah');
    }
    echo "Tes ekspor dashboard berhasil.\n";
});
require __DIR__ . '/../includes/admin-export.php';
