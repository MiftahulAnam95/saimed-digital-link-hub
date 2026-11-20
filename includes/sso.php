<?php

declare(strict_types=1);

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require_once __DIR__ . '/../config/koneksi_sso.php';

// URL diatur secara eksplisit, tidak dibentuk dari header Host milik pengguna.
function sso_app_url(): string
{
    return rtrim(getenv('SSO_APP_URL') ?: 'http://localhost/saimed-digital-link-hub', '/');
}

function sso_logout_url(): string
{
    $base = rtrim(getenv('SSO_BASE_URL') ?: 'http://localhost/test-sso', '/');
    // test-sso mengharapkan base64url dari nonce (12 byte) + tag (16 byte) + ciphertext.
    // decrypt() di server menggunakan AES_SECRET_KEY; URL_KEY bukan argumen yang digunakan.
    $key = getenv('SSO_AES_SECRET_KEY');
    if (!is_string($key) || strlen($key) !== 32 || !function_exists('openssl_encrypt')) {
        throw new RuntimeException('SSO_AES_SECRET_KEY harus 32 byte dan ekstensi OpenSSL harus aktif.');
    }
    $nonce = random_bytes(12);
    $tag = '';
    $ciphertext = openssl_encrypt(sso_app_url(), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag, '', 16);
    if ($ciphertext === false || strlen($tag) !== 16) {
        throw new RuntimeException('Gagal mengenkripsi URL untuk SSO.');
    }
    $encrypted = rtrim(strtr(base64_encode($nonce . $tag . $ciphertext), '+/', '-_'), '=');
    return $base . '/logout.php?url=' . rawurlencode($encrypted);
}

// Hanya kode penyebab yang dicatat. Jangan catat cookie, JWT, key, atau data pengguna.
function sso_reject(string $reason): ?array
{
    error_log('SAIMED SSO: validasi ditolak [' . $reason . ']');
    return null;
}

/** Validasi token dan akses pada setiap request, termasuk setelah login. */
function sso_authenticate(PDO $database, string $token, string $website): ?array
{
    if ($token === '' || strlen($token) > 8192) {
        return sso_reject('format_token');
    }
    if (!class_exists(JWT::class)) {
        return sso_reject('dependensi_jwt');
    }
    $stmt = $database->prepare(
        'SELECT ut.key_token, ut.expired_token_time, us.id_user_status, us.status_perangkat,
                ua.id_user_akses, ua.is_blocked, u.id_user, u.nama_user, ur.nama_role
         FROM user_token ut
         INNER JOIN user_status us ON us.id_token = ut.id_token
         INNER JOIN user_akses ua ON us.id_user_akses = ua.id_user_akses
         INNER JOIN website_management w ON ua.id_website = w.id_website
         INNER JOIN user u ON ua.id_user = u.id_user
         INNER JOIN user_role ur ON u.id_user_role = ur.id_user_role
         WHERE ut.token = :token AND w.url_website = :website LIMIT 1'
    );
    $stmt->execute(['token' => $token, 'website' => $website]);
    $row = $stmt->fetch();
    if (!$row) {
        return sso_reject('token_atau_akses_website_tidak_ditemukan');
    }
    if ($row['nama_role'] !== 'Super Admin') {
        return sso_reject('role_bukan_super_admin');
    }
    if ($row['status_perangkat'] !== 'Online') {
        return sso_reject('perangkat_tidak_online');
    }
    if ((string) $row['is_blocked'] !== '0') {
        return sso_reject('akses_diblokir_atau_tidak_valid');
    }
    if (empty($row['id_user_akses'])) {
        return sso_reject('akses_tidak_valid');
    }
    if (!is_string($row['key_token']) || $row['key_token'] === '') {
        return sso_reject('key_token_kosong');
    }
    // Referensi SSO menggunakan waktu Asia/Jakarta untuk kolom expired_token_time.
    try {
        $expires = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', (string) $row['expired_token_time'], new DateTimeZone('Asia/Jakarta'));
        $dateErrors = DateTimeImmutable::getLastErrors();
        if (!$expires || ($dateErrors && ($dateErrors['warning_count'] || $dateErrors['error_count']))) {
            return sso_reject('waktu_token_database_tidak_valid');
        }
        if ($expires->getTimestamp() <= time()) {
            return sso_reject('token_database_kedaluwarsa');
        }
    } catch (Throwable $e) {
        return sso_reject('waktu_token_database_tidak_valid');
    }
    try {
        $claims = JWT::decode($token, new Key($row['key_token'], 'HS256'));
    } catch (\Firebase\JWT\ExpiredException $e) {
        return sso_reject('jwt_kedaluwarsa');
    } catch (\Firebase\JWT\BeforeValidException $e) {
        return sso_reject('jwt_belum_berlaku');
    } catch (\Firebase\JWT\SignatureInvalidException $e) {
        return sso_reject('tanda_tangan_jwt_tidak_valid');
    } catch (Throwable $e) {
        return sso_reject('jwt_tidak_valid');
    }
    if (!isset($claims->id_user) || !is_scalar($claims->id_user)
        || (string) $claims->id_user !== (string) $row['id_user']) {
        return sso_reject('id_user_tidak_cocok');
    }
    return ['id' => (string) $row['id_user'], 'name' => $row['nama_user']];
}
