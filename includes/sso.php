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
    // test-sso menandatangani JWT dengan JWT_SECRET_KEY, bukan key_token acak.
    $jwtKey = getenv('SSO_JWT_SECRET_KEY');
    if (!is_string($jwtKey) || $jwtKey === '') {
        return sso_reject('jwt_secret_belum_diatur');
    }
    $stmt = $database->prepare(
        'SELECT ut.expired_token_time, us.id_user_status, us.status_perangkat,
                ua.id_user_akses, ua.is_blocked, u.id_user, u.nama_user, ur.nama_role
         FROM user_token ut
         INNER JOIN user_status us ON us.id_token = ut.id_token
         INNER JOIN user_akses ua ON us.id_user_akses = ua.id_user_akses
         INNER JOIN website_management w ON ua.id_website = w.id_website
         INNER JOIN user u ON ua.id_user = u.id_user AND ut.id_user = u.id_user
         INNER JOIN user_role ur ON u.id_user_role = ur.id_user_role
         WHERE ut.token = :token AND BINARY ut.token = BINARY :exact_token
           AND w.url_website = :website LIMIT 1'
    );
    $stmt->execute(['token' => $token, 'exact_token' => $token, 'website' => $website]);
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
        $claims = JWT::decode($token, new Key($jwtKey, 'HS256'));
    } catch (\Firebase\JWT\ExpiredException $e) {
        return sso_reject('jwt_kedaluwarsa');
    } catch (\Firebase\JWT\BeforeValidException $e) {
        return sso_reject('jwt_belum_berlaku');
    } catch (\Firebase\JWT\SignatureInvalidException $e) {
        return sso_reject('tanda_tangan_jwt_tidak_valid');
    } catch (Throwable $e) {
        return sso_reject('jwt_tidak_valid');
    }
    // Token terbaru tidak memiliki id_user; identitas terikat melalui JOIN database.
    // Jika klaim tersedia, tetap wajib cocok.
    if (property_exists($claims, 'id_user') && (!is_scalar($claims->id_user)
        || (string) $claims->id_user !== (string) $row['id_user'])) {
        return sso_reject('id_user_tidak_cocok');
    }
    $expiresAt = $expires->getTimestamp();
    if (isset($claims->exp) && is_numeric($claims->exp)) {
        $expiresAt = min($expiresAt, (int) $claims->exp);
    }
    return ['id' => (string) $row['id_user'], 'name' => (string) $row['nama_user'], 'expires_at' => $expiresAt];
}

/** Foto profil adalah data opsional; kegagalannya tidak boleh menggagalkan login. */
function sso_profile_photo_url(PDO $database, string $userId): ?string
{
    try {
        $stmt = $database->prepare('SELECT nama_file FROM photo_profile WHERE id_user = :id_user LIMIT 1');
        $stmt->execute(['id_user' => $userId]);
        $row = $stmt->fetch();
        $filename = $row['nama_file'] ?? null;
        if (!is_string($filename) || $filename === '' || $filename === '.' || $filename === '..'
            || strpbrk($filename, '/\\') !== false || preg_match('~[\x00-\x1f\x7f]~', $filename)) {
            return null;
        }
        $base = getenv('SSO_PROFILE_PHOTO_BASE_URL');
        $base = is_string($base) && $base !== '' ? rtrim($base, '/') : 'https://test-user-account.mandirialkesindo.co.id/profile%20user';
        $parts = parse_url($base);
        if (!filter_var($base, FILTER_VALIDATE_URL) || !$parts || !isset($parts['scheme'], $parts['host'])
            || !in_array(strtolower($parts['scheme']), ['http', 'https'], true)
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
            return null;
        }
        return $base . '/' . rawurlencode($filename);
    } catch (Throwable $e) {
        error_log('SAIMED SSO: foto profil tidak tersedia');
        return null;
    }
}
