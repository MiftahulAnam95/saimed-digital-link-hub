<?php

declare(strict_types=1);

require __DIR__ . '/../includes/links.php';
$_GET = ['days' => '90', 'export' => ($argv[1] ?? '') === 'xlsx' ? 'xlsx' : 'csv'];
class ExportTestDb {
    public function prepare(string $sql): ExportTestStatement {
        if (!str_contains($sql, 'created_at >= ?') || !str_contains($sql, 'created_at < ?') || !str_contains($sql, 'is_demo = 0')) throw new RuntimeException('Filter ekspor hilang');
        return new ExportTestStatement();
    }
}
class ExportTestStatement {
    private int $index = 0;
    public function execute(array $params): void {
        if (!isset($params[0], $params[1]) || $params[0] <= $params[1]) throw new RuntimeException('Parameter ekspor hilang');
    }
    public function get_result(): self { return $this; }
    public function fetch_assoc(): mixed {
        $events = [
            ['created_at' => '2026-01-01 12:00:00', 'event_type' => 'link_click', 'target_label' => '=SUM(1;2)', 'device' => 'Mobile', 'is_demo' => 0],
            ['created_at' => '2026-01-01 12:01:00', 'event_type' => 'view', 'target_label' => null, 'device' => 'Mobile', 'is_demo' => 0],
            ['created_at' => '2026-01-01 12:02:00', 'event_type' => 'view', 'target_label' => 'Label lama', 'device' => 'Desktop', 'is_demo' => 0],
            ['created_at' => '2026-01-01 12:03:00', 'event_type' => 'social_click', 'target_label' => 'Instagram', 'device' => 'Mobile', 'is_demo' => 0],
        ];
        return $events[$this->index++] ?? false;
    }
}
function db(): ExportTestDb { return new ExportTestDb(); }
ob_start();
register_shutdown_function(static function (): void {
    $csv = ob_get_clean();
    if (($_GET['export'] ?? '') === 'xlsx') {
        $path = tempnam(sys_get_temp_dir(), 'test-export-');
        file_put_contents($path, $csv);
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) throw new RuntimeException('Ekspor harus menghasilkan file XLSX');
        $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if (!str_contains($xml, '2026-01-01 19:00:00') || !str_contains($xml, '=SUM(1;2)') || !str_contains($xml, 't="inlineStr"') || substr_count($xml, '>Landing Page</t>') !== 2 || !str_contains($xml, '>Instagram</t>') || str_contains($xml, 'Label lama')) throw new RuntimeException('Isi Excel salah atau tidak aman');
        $zip->close(); unlink($path);
        echo "Tes endpoint ekspor Excel berhasil.\n";
        return;
    }
    $expected = "\xEF\xBB\xBFsep=;\r\n";
    if (!str_starts_with($csv, $expected)) throw new RuntimeException('Format CSV salah');
    $stream = fopen('php://temp', 'w+');
    fwrite($stream, substr($csv, strlen($expected)));
    rewind($stream);
    $header = fgetcsv($stream, 0, ';', '"', '');
    $row = fgetcsv($stream, 0, ';', '"', '');
    $view = fgetcsv($stream, 0, ';', '"', '');
    $oldView = fgetcsv($stream, 0, ';', '"', '');
    $social = fgetcsv($stream, 0, ';', '"', '');
    fclose($stream);
    if ($header !== ['Waktu (WIB)', 'Aktivitas', 'Nama tautan', 'Perangkat']
        || $row !== ['2026-01-01 19:00:00', 'link_click', "'=SUM(1;2)", 'Mobile']
        || $view !== ['2026-01-01 19:01:00', 'view', 'Landing Page', 'Mobile']
        || $oldView !== ['2026-01-01 19:02:00', 'view', 'Landing Page', 'Desktop']
        || $social !== ['2026-01-01 19:03:00', 'social_click', 'Instagram', 'Mobile']) {
        throw new RuntimeException('Konten CSV atau perlindungan formula salah');
    }
    echo "Tes ekspor dashboard berhasil.\n";
});
require __DIR__ . '/../includes/admin-export.php';
