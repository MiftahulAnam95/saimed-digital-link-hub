<?php

declare(strict_types=1);

require __DIR__ . '/../app.php';

function expect(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "GAGAL: $message\n");
        exit(1);
    }
}

expect(safe_url('https://example.com') === 'https://example.com', 'URL HTTPS diterima');
expect(safe_url('/official') === '/official', 'URL internal diterima');
foreach (['//evil.example', '/\\evil.example', 'javascript:alert(1)'] as $url) {
    expect(safe_url($url) === null, 'URL berbahaya ditolak: ' . $url);
}
expect(safe_url("https://example.com/\npath") === null, 'Karakter kontrol ditolak');
expect(count(fallback_links()) === 4, 'Empat tautan bawaan tersedia');
expect(parse_agent('Mozilla/5.0 (iPhone) Safari/605.1')[0] === 'Mobile', 'Perangkat dideteksi');
foreach (['instagram', 'shopee', 'tokopedia', 'whatsapp', 'website', 'link'] as $iconName) {
    $iconHtml = icon($iconName);
    expect(is_file(__DIR__ . '/../assets/icons/' . $iconName . '.svg'), 'File ikon tersedia: ' . $iconName);
    expect(str_contains($iconHtml, 'src="assets/icons/' . $iconName . '.svg"'), 'Ikon dimuat sebagai gambar terpisah: ' . $iconName);
    expect(!str_contains($iconHtml, '<svg'), 'Definisi SVG tidak disisipkan ke halaman: ' . $iconName);
}
expect(str_contains(icon('../../app.php'), 'assets/icons/link.svg'), 'Nama ikon dibatasi');

// Template harus tetap menampilkan teks dari database sebagai teks, bukan HTML.
$publicLinks = fallback_links();
$publicLinks[0]['title'] = '<script>alert(1)</script>';
ob_start();
require __DIR__ . '/../templates/home.php';
$html = ob_get_clean();
expect(str_contains($html, '&lt;script&gt;'), 'Judul tautan di-escape');
expect(!str_contains($html, '<script>alert(1)</script>'), 'Skrip tidak dirender');
expect(str_contains($html, 'assets/public.js'), 'Interaksi halaman dimuat');
expect(!str_contains($html, 'assets/admin.css'), 'Halaman publik tidak memuat CSS admin');

// Header bersama harus mengamankan judul dan memuat CSS sesuai halaman.
$page_title = '<judul uji>';
$admin_page = true;
ob_start();
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/footer.php';
$adminHeader = ob_get_clean();
expect(str_contains($adminHeader, '&lt;judul uji&gt;'), 'Judul header di-escape');
expect(str_contains($adminHeader, 'assets/admin.css'), 'CSS admin dimuat');

echo "Smoke test berhasil.\n";
