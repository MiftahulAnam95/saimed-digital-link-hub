<?php
declare(strict_types=1);
require __DIR__.'/../app.php';
function expect(bool $condition, string $message): void { if (!$condition) { fwrite(STDERR, "GAGAL: $message\n"); exit(1); } }
expect(safe_url('https://example.com') === 'https://example.com', 'URL HTTPS diterima');
expect(safe_url('/official') === '/official', 'URL internal diterima');
foreach (['//evil.example', '/\\evil.example', 'javascript:alert(1)'] as $url) {
    expect(safe_url($url) === null, 'URL berbahaya ditolak: '.$url);
}
expect(safe_url("https://example.com/\npath") === null, 'Karakter kontrol ditolak');
expect(count(fallback_links()) === 4, 'Empat tautan bawaan tersedia');
expect(parse_agent('Mozilla/5.0 (iPhone) Safari/605.1')[0] === 'Mobile', 'Perangkat dideteksi');
echo "Smoke test berhasil.\n";
