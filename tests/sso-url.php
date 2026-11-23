<?php

declare(strict_types=1);

require __DIR__ . '/../akses.php';

function expect_sso_url(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

putenv('SSO_BASE_URL=http://localhost/test-sso');
putenv('SSO_APP_URL=http://localhost/saimed-digital-link-hub');
putenv('SSO_AES_SECRET_KEY');
try {
    sso_logout_url();
    throw new LogicException('Key yang tidak diatur diterima');
} catch (RuntimeException $e) {
    expect_sso_url(str_contains($e->getMessage(), 'SSO_AES_SECRET_KEY'), 'Kesalahan key tidak sesuai');
}
putenv('SSO_AES_SECRET_KEY=terlalu-pendek');
try {
    sso_logout_url();
    throw new LogicException('Key pendek diterima');
} catch (RuntimeException $e) {
    expect_sso_url(str_contains($e->getMessage(), 'SSO_AES_SECRET_KEY'), 'Kesalahan key tidak sesuai');
}
$key = str_repeat('a', 32); // hanya untuk pengujian
putenv('SSO_AES_SECRET_KEY=' . $key);
$url = sso_logout_url();
expect_sso_url(str_starts_with($url, 'http://localhost/test-sso/logout.php?url='), 'URL logout tidak sesuai');
parse_str((string) parse_url($url, PHP_URL_QUERY), $params);
$encrypted = $params['url'] ?? '';
expect_sso_url((bool) preg_match('/^[A-Za-z0-9_-]+$/', $encrypted), 'Ciphertext bukan base64url');
$data = base64_decode(strtr($encrypted, '-_', '+/') . str_repeat('=', (4 - strlen($encrypted) % 4) % 4), true);
expect_sso_url(is_string($data) && strlen($data) > 28, 'Panjang ciphertext tidak valid');
$nonce = substr($data, 0, 12);
$tag = substr($data, 12, 16);
$ciphertext = substr($data, 28);
$decoded = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag);
expect_sso_url($decoded === sso_app_url(), 'URL tidak dapat didekripsi');
expect_sso_url(openssl_decrypt($ciphertext, 'aes-256-gcm', str_repeat('b', 32), OPENSSL_RAW_DATA, $nonce, $tag) === false, 'Key salah diterima');
expect_sso_url(sso_logout_url() !== $url, 'Nonce harus baru untuk setiap URL');
echo "Tes enkripsi URL SSO berhasil.\n";
