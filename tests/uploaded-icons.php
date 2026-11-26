<?php

declare(strict_types=1);

require __DIR__ . '/../app.php';
function expect_icon(bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); }
function rejects_icon(callable $fn, string $message): void {
    try { $fn(); } catch (InvalidArgumentException $e) { return; }
    throw new RuntimeException($message);
}
$validName = 'u' . str_repeat('a', 24) . '.png';
expect_icon(uploaded_icon_path($validName) !== null, 'Nama acak valid diterima');
foreach (['../../index.php', 'u' . str_repeat('a', 24) . '.php', 'u' . str_repeat('a', 24) . '.svg', 'u' . str_repeat('A', 24) . '.png'] as $name) {
    expect_icon(uploaded_icon_path($name) === null, 'Nama berbahaya harus ditolak');
    expect_icon(str_contains(icon($name), 'assets/images/icons/link.svg'), 'Nama tidak aman harus memakai ikon cadangan');
}
expect_icon(str_contains(icon($validName), '?api=icon&amp;file=' . $validName), 'Logo unggahan ditampilkan melalui endpoint');
expect_icon(validate_icon_image(__DIR__ . '/../assets/images/saimed-logo.png') === 'png', 'PNG valid harus dikenali');
$temp = tempnam(sys_get_temp_dir(), 'saimed-test-');
try {
    file_put_contents($temp, '<?php echo "bukan gambar";');
    rejects_icon(fn() => validate_icon_image($temp), 'File bukan gambar harus ditolak');
    file_put_contents($temp, str_repeat('a', 2 * 1024 * 1024 + 1));
    rejects_icon(fn() => validate_icon_image($temp), 'Gambar terlalu besar harus ditolak');
    rejects_icon(fn() => upload_link_icon(['error' => UPLOAD_ERR_OK, 'tmp_name' => $temp], true), 'File lokal bukan unggahan HTTP harus ditolak');
    rejects_icon(fn() => upload_link_icon(null, true), 'Logo baru wajib diunggah');
    rejects_icon(fn() => upload_link_icon(['error' => UPLOAD_ERR_INI_SIZE], true), 'Error unggahan harus ditolak');
    expect_icon(upload_link_icon(null, false) === null, 'Logo lama bisa dipertahankan');
} finally { unlink($temp); }
echo "Tes validasi logo berhasil.\n";
