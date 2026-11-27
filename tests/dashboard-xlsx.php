<?php

declare(strict_types=1);

require __DIR__ . '/../includes/dashboard-data.php';
require __DIR__ . '/../includes/dashboard-xlsx.php';
function expect_xlsx(bool $condition, string $message): void { if (!$condition) throw new RuntimeException($message); }
class XlsxFakeResult {
    private int $index = 0;
    public function __construct(private array $rows) {}
    public function fetch_assoc(): mixed { return $this->rows[$this->index++] ?? false; }
}
function check_xlsx(array $rows): void {
    $path = tempnam(sys_get_temp_dir(), 'test-xlsx-');
    try {
        dashboard_write_xlsx(new XlsxFakeResult($rows), $path);
        $zip = new ZipArchive();
        expect_xlsx($zip->open($path) === true, 'File harus menjadi ZIP/XLSX yang valid');
        foreach (['[Content_Types].xml', '_rels/.rels', 'xl/workbook.xml', 'xl/_rels/workbook.xml.rels', 'xl/styles.xml', 'xl/worksheets/sheet1.xml'] as $part) {
            $xml = $zip->getFromName($part);
            expect_xlsx(is_string($xml), 'Bagian XLSX tidak ada: ' . $part);
            $dom = new DOMDocument();
            expect_xlsx($dom->loadXML($xml), 'XML tidak valid: ' . $part);
        }
        $sheet = new DOMDocument(); $sheet->loadXML($zip->getFromName('xl/worksheets/sheet1.xml'));
        $xpath = new DOMXPath($sheet); $xpath->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        expect_xlsx((int) $xpath->evaluate('count(//x:cols/x:col[@customWidth="1"])') === 4, 'Empat lebar kolom harus ditentukan');
        expect_xlsx($xpath->evaluate('string(//x:col[@min="3"]/@width)') === '48', 'Kolom nama tautan harus lebar');
        expect_xlsx($xpath->evaluate('string(//x:pane/@state)') === 'frozen', 'Header harus dibekukan');
        expect_xlsx($xpath->evaluate('string(//x:autoFilter/@ref)') === 'A1:D' . (count($rows) + 1), 'Filter harus mencakup semua baris');
        expect_xlsx($xpath->evaluate('string(//x:c[@r="A1"]/x:is/x:t)') === 'Waktu (WIB)', 'Header harus benar');
        if ($rows) {
            expect_xlsx($xpath->evaluate('string(//x:c[@r="A2"]/x:is/x:t)') === '2026-01-01 19:00:00', 'Waktu harus dikonversi ke WIB');
            expect_xlsx($xpath->evaluate('string(//x:c[@r="C2"]/x:is/x:t)') === "=SUM(1;2) & <tautan>\nÉ", 'Teks harus dipertahankan dan di-escape');
            expect_xlsx($xpath->evaluate('string(//x:c[@r="C2"]/@t)') === 'inlineStr' && $xpath->evaluate('count(//x:f)') === 0.0, 'Input tidak boleh menjadi formula');
        }
        $zip->close();
    } finally { @unlink($path); }
}
check_xlsx([]);
check_xlsx([['created_at' => '2026-01-01 12:00:00', 'event_type' => 'link_click', 'target_label' => "=SUM(1;2) & <tautan>\nÉ\x01\u{FFFE}", 'device' => 'Mobile']]);
echo "Tes Excel dashboard berhasil.\n";
