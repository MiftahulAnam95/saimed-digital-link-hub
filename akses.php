<?php

declare(strict_types=1);

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require_once __DIR__ . '/config/koneksi_user.php';

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


// Simpan tujuan dan status percobaan hanya di sesi aplikasi, bukan di URL browser.
function sso_mark_login_pending(): void
{
    unset($_SESSION['sso_login_returned']);
    $_SESSION['sso_login_pending_until'] = time() + 600;
}

function sso_login_pending(): bool
{
    $until = $_SESSION['sso_login_pending_until'] ?? null;
    if (!is_int($until) || $until <= time()) {
        unset($_SESSION['sso_login_pending_until']);
        return false;
    }
    return true;
}

function sso_mark_login_returned(): void
{
    unset($_SESSION['sso_login_pending_until']);
    $_SESSION['sso_login_returned'] = true;
}

function sso_should_return_to_admin(string $page, string $method): bool
{
    if (!sso_login_pending() || $page !== 'home' || $method !== 'GET') {
        return false;
    }
    // Juga tangani cookie yang tidak terkirim agar kesalahannya terlihat di admin.
    sso_mark_login_returned();
    return true;
}

function sso_should_start_login(string $method, bool $loggedIn, bool $hasError): bool
{
    return $method === 'GET' && !$loggedIn && !$hasError
        && empty($_SESSION['sso_login_returned']) && !sso_login_pending();
}

// Dipanggil untuk setiap request admin sebelum tindakan atau data dashboard.
function sso_check_admin_access(?PDO $database = null): array
{
    $autoload = __DIR__ . '/vendor/autoload.php';
    if (is_file($autoload)) {
        require_once $autoload;
    }
    $loggedIn = false;
    $identity = null;
    $profilePhotoUrl = null;
    $sessionRemaining = 0;
    $ssoError = '';
    $token = $_COOKIE['jwt_token_test'] ?? '';
    if (is_string($token) && $token !== '') {
        try {
            if (!class_exists(\Firebase\JWT\JWT::class)) {
                throw new RuntimeException('Dependensi SSO belum terpasang.');
            }
            $ssoDatabase = $database ?? sso_db();
            $identity = sso_authenticate($ssoDatabase, $token, sso_app_url());
            $tokenHash = hash('sha256', $token);
            $timedOut = $identity && (
                (isset($_SESSION['sso_timed_out']) && hash_equals($_SESSION['sso_timed_out'], $tokenHash))
                || (isset($_SESSION['sso_token_hash'], $_SESSION['sso_last_activity'])
                    && hash_equals($_SESSION['sso_token_hash'], $tokenHash)
                    && time() - $_SESSION['sso_last_activity'] >= 1800)
            );
            if ($timedOut) {
                // Token lama tetap diblokir sampai SSO menerbitkan token baru.
                $_SESSION['sso_timed_out'] = $tokenHash;
                if (!empty($_SESSION['sso_login_returned'])) {
                    $identity = null;
                    $ssoError = 'Sesi SSO telah berakhir. Silakan periksa sesi SSO sebelum mencoba lagi.';
                } else {
                    $logoutUrl = sso_logout_url();
                    unset($_SESSION['sso_token_hash'], $_SESSION['sso_last_activity']);
                    session_regenerate_id(true);
                    sso_mark_login_pending();
                    header('Location: ' . $logoutUrl, true, 303);
                    exit();
                }
            }
            if ($identity) {
                unset($_SESSION['sso_timed_out'], $_SESSION['sso_login_pending_until'], $_SESSION['sso_login_returned']);
                if (!isset($_SESSION['sso_token_hash']) || !hash_equals($_SESSION['sso_token_hash'], $tokenHash)) {
                    session_regenerate_id(true);
                    $_SESSION['csrf'] = bin2hex(random_bytes(32));
                }
                $_SESSION['sso_token_hash'] = $tokenHash;
                $_SESSION['sso_last_activity'] = time();
                $loggedIn = true;
                $profilePhotoUrl = sso_profile_photo_url($ssoDatabase, $identity['id']);
                $sessionRemaining = max(0, min($_SESSION['sso_last_activity'] + 1800, $identity['expires_at']) - time());
            }
        } catch (Throwable $e) {
            error_log('SAIMED SSO: ' . $e->getMessage());
            $ssoError = 'SSO belum tersedia. Periksa konfigurasi dan koneksi server.';
        }
    }
    if (!$loggedIn) {
        unset($_SESSION['admin'], $_SESSION['sso_token_hash'], $_SESSION['sso_last_activity']);
    }
    return [$loggedIn, $ssoError, $identity, $profilePhotoUrl, $sessionRemaining];
}

// Pertahankan redirect tanpa loop ketika SSO mengembalikan pengguna tanpa cookie.
function sso_prepare_login(bool $loggedIn, string &$ssoError): ?string
{
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && sso_login_pending()) {
        sso_mark_login_returned();
    }
    try {
        $ssoLoginUrl = sso_logout_url();
        if (sso_should_start_login($_SERVER['REQUEST_METHOD'], $loggedIn, $ssoError !== '')) {
            sso_mark_login_pending();
            header('Location: ' . $ssoLoginUrl, true, 302);
            exit();
        }
        return $ssoLoginUrl;
    } catch (Throwable $e) {
        error_log('SAIMED SSO: ' . $e->getMessage());
        $ssoError = 'SSO belum tersedia. Periksa konfigurasi dan ekstensi OpenSSL.';
        return null;
    }
}

function sso_handle_login_action(): void
{
    try {
        $loginUrl = sso_logout_url();
        sso_mark_login_pending();
        header('Location: ' . $loginUrl, true, 303);
        exit();
    } catch (Throwable $e) {
        error_log('SAIMED SSO: ' . $e->getMessage());
        $_SESSION['notice'] = 'SSO belum tersedia. Periksa konfigurasi dan ekstensi OpenSSL.';
        redirect_admin();
    }
}

function sso_handle_logout_action(): void
{
    try {
        $logoutUrl = sso_logout_url();
    } catch (Throwable $e) {
        error_log('SAIMED SSO: ' . $e->getMessage());
        $_SESSION['notice'] = 'Logout SSO belum tersedia. Periksa konfigurasi SSO.';
        redirect_admin();
    }
    $_SESSION = [];
    session_destroy();
    header('Location: ' . $logoutUrl, true, 303);
    exit();
}
