<?php

declare(strict_types=1);

// Excel cells are explicitly strings so text starting with =, +, - or @ is not a formula.
function dashboard_xlsx_text($value): string
{
    $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', (string) ($value ?? ''));
    $escaped = htmlspecialchars($text, ENT_QUOTES | ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8');
    return preg_replace('/[\x{FFFE}\x{FFFF}]/u', '', $escaped);
}

/** Write the event report into an XLSX archive without retaining all worksheet rows in memory. */
function dashboard_write_xlsx($result, string $destination): void
{
    if (!class_exists('ZipArchive')) throw new RuntimeException('Ekstensi PHP zip belum diaktifkan.');
    $sheetPath = tempnam(sys_get_temp_dir(), 'saimed-sheet-');
    if ($sheetPath === false) throw new RuntimeException('Tidak dapat membuat file sementara.');
    $sheet = null;
    $zip = null;
    $zipOpened = false;
    try {
        $sheet = @fopen($sheetPath, 'wb');
        if ($sheet === false) throw new RuntimeException('Tidak dapat menulis file sementara.');
        $write = static function (string $content) use ($sheet): void {
            $length = strlen($content);
            if (fwrite($sheet, $content) !== $length) throw new RuntimeException('Gagal menulis laporan Excel.');
        };
        $write('<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . '<cols><col min="1" max="1" width="23" customWidth="1"/><col min="2" max="2" width="21" customWidth="1"/>'
            . '<col min="3" max="3" width="48" customWidth="1"/><col min="4" max="4" width="18" customWidth="1"/></cols><sheetData>');
        $rowNumber = 0;
        $writeRow = static function (array $values, int $style) use (&$rowNumber, $write): void {
            $rowNumber++;
            // Excel's worksheet row limit; fail rather than silently truncate data.
            if ($rowNumber > 1048576) throw new RuntimeException('Data melebihi batas baris Excel. Gunakan ekspor CSV.');
            $xml = '<row r="' . $rowNumber . '"' . ($style === 1 ? ' ht="25" customHeight="1"' : '') . '>';
            foreach ($values as $index => $value) {
                $cellStyle = $style === 1 ? 1 : ($index === 2 ? 2 : 0);
                $xml .= '<c r="' . chr(65 + $index) . $rowNumber . '" t="inlineStr" s="' . $cellStyle . '"><is><t xml:space="preserve">'
                    . dashboard_xlsx_text($value) . '</t></is></c>';
            }
            $write($xml . '</row>');
        };
        $writeRow(['Waktu (WIB)', 'Aktivitas', 'Nama tautan', 'Perangkat'], 1);
        while ($event = $result->fetch_assoc()) {
            $writeRow([
                dashboard_wib($event['created_at'] ?? null, 'Y-m-d H:i:s'),
                $event['event_type'] ?? '', $event['target_label'] ?? '', $event['device'] ?? '',
            ], 0);
        }
        $write('</sheetData><autoFilter ref="A1:D' . $rowNumber . '"/></worksheet>');
        if (!fclose($sheet)) throw new RuntimeException('Gagal menyelesaikan laporan Excel.');
        $sheet = null;

        $zip = new ZipArchive();
        if ($zip->open($destination, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) throw new RuntimeException('Tidak dapat membuat file Excel.');
        $zipOpened = true;
        $files = [
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Aktivitas" sheetId="1" r:id="rId1"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>',
            'xl/styles.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="11"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF0875C1"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="3"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment vertical="center"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>',
        ];
        foreach ($files as $name => $content) {
            if (!$zip->addFromString($name, $content)) throw new RuntimeException('Gagal membuat struktur Excel.');
        }
        if (!$zip->addFile($sheetPath, 'xl/worksheets/sheet1.xml')) throw new RuntimeException('Gagal menambahkan data Excel.');
        $closed = $zip->close();
        $zipOpened = false;
        if (!$closed) throw new RuntimeException('Gagal menyelesaikan file Excel.');
    } finally {
        if (is_resource($sheet)) fclose($sheet);
        if ($zipOpened) $zip->close();
        @unlink($sheetPath);
    }
}
