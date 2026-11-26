<?php

declare(strict_types=1);

// Logo disimpan di folder internal, hanya disajikan sebagai gambar lewat api/icon.php.
function uploaded_icon_path(string $name): ?string
{
    if (!preg_match('/^u[a-f0-9]{24}\.(png|jpg)$/D', $name)) return null;
    return __DIR__ . '/../config/uploads/' . $name;
}

function validate_icon_image(string $path): string
{
    $size = @filesize($path);
    if ($size === false || $size < 1 || $size > 2 * 1024 * 1024) {
        throw new InvalidArgumentException('Ukuran logo maksimal 2 MB.');
    }
    $image = @getimagesize($path);
    if (!$image || !in_array($image[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG], true)) {
        throw new InvalidArgumentException('Logo harus berupa gambar PNG atau JPG yang valid.');
    }
    if ($image[0] < 1 || $image[1] < 1 || $image[0] > 4096 || $image[1] > 4096 || $image[0] * $image[1] > 16000000) {
        throw new InvalidArgumentException('Dimensi logo maksimal 4096 piksel per sisi dan 16 megapiksel.');
    }
    return $image[2] === IMAGETYPE_PNG ? 'png' : 'jpg';
}

function upload_link_icon(?array $upload, bool $required): ?string
{
    $error = $upload['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($error === UPLOAD_ERR_NO_FILE) {
        if ($required) throw new InvalidArgumentException('Pilih gambar logo/icon terlebih dahulu.');
        return null;
    }
    if ($error !== UPLOAD_ERR_OK || !isset($upload['tmp_name']) || !is_string($upload['tmp_name']) || !is_uploaded_file($upload['tmp_name'])) {
        throw new InvalidArgumentException('Gagal mengunggah logo. Pastikan ukuran file maksimal 2 MB.');
    }
    $extension = validate_icon_image($upload['tmp_name']);
    $name = 'u' . bin2hex(random_bytes(12)) . '.' . $extension;
    $path = uploaded_icon_path($name);
    $directory = dirname($path);
    if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('Folder penyimpanan logo tidak dapat dibuat.');
    }
    if (!move_uploaded_file($upload['tmp_name'], $path)) {
        throw new RuntimeException('Logo tidak dapat disimpan.');
    }
    return $name;
}

function delete_uploaded_icon(string $name): void
{
    $path = uploaded_icon_path($name);
    if ($path !== null && is_file($path) && !@unlink($path)) {
        error_log('SAIMED: gagal menghapus file logo lama.');
    }
}
