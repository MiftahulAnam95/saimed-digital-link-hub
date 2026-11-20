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
    return $base . '/logout.php?url=' . rawurlencode(sso_app_url());
}

/** Validasi token dan akses pada setiap request, termasuk setelah login. */
function sso_authenticate(PDO $database, string $token, string $website): ?array
{
    if ($token === '' || strlen($token) > 8192 || !class_exists(JWT::class)) {
        return null;
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
    if (!$row || $row['nama_role'] !== 'Super Admin' || $row['status_perangkat'] !== 'Online'
        || (string) $row['is_blocked'] !== '0' || empty($row['id_user_akses'])
        || !is_string($row['key_token']) || $row['key_token'] === '') {
        return null;
    }
    try {
        // Referensi SSO menggunakan waktu Asia/Jakarta untuk kolom expired_token_time.
        $expires = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', (string) $row['expired_token_time'], new DateTimeZone('Asia/Jakarta'));
        $dateErrors = DateTimeImmutable::getLastErrors();
        if (!$expires || ($dateErrors && ($dateErrors['warning_count'] || $dateErrors['error_count']))
            || $expires->getTimestamp() <= time()) {
            return null;
        }
        $claims = JWT::decode($token, new Key($row['key_token'], 'HS256'));
        if (!isset($claims->id_user) || !is_scalar($claims->id_user)
            || (string) $claims->id_user !== (string) $row['id_user']) {
            return null;
        }
    } catch (Throwable $e) {
        return null;
    }
    return ['id' => (string) $row['id_user'], 'name' => $row['nama_user']];
}
