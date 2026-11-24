<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../akses.php';

use Firebase\JWT\JWT;

function expect_sso(bool $value, string $message): void
{
    if (!$value) {
        throw new RuntimeException($message);
    }
}

// Simulasi database: tidak memerlukan atau mengubah database pengguna SSO asli.
class SsoTestDatabase
{
    public array $row = [];
    public array $parameters = [];
    public function prepare(string $query): SsoTestStatement
    {
        expect_sso(substr_count($query, '?') === 3, 'Query harus menggunakan tiga parameter');
        expect_sso(str_contains($query, 'ut.id_user = u.id_user'), 'Pemilik token harus cocok dengan pemilik akses');
        expect_sso(str_contains($query, 'BINARY ut.token = BINARY ?'), 'Token harus cocok secara case-sensitive');
        return new SsoTestStatement($this);
    }
}
class SsoTestStatement
{
    public function __construct(private SsoTestDatabase $database) {}
    public function execute(array $params): bool
    {
        $this->database->parameters = $params;
        return true;
    }
    public function get_result(): self { return $this; }
    public function fetch_assoc(): mixed { return $this->database->row ?: null; }
}

$db = new SsoTestDatabase();
$key = 'test-jwt-key-not-for-production';
putenv('SSO_JWT_SECRET_KEY=' . $key);
// Format dari test-sso: tidak ada id_user, dan key_token di DB berbeda dari JWT_SECRET_KEY.
$payload = ['iss' => 'http://localhost/saimed-digital-link-hub', 'iat' => time(), 'exp' => time() + 300, 'jti' => 'test-jti'];
$token = JWT::encode($payload, $key, 'HS256');
$db->row = [
    'key_token' => 'random-not-the-signing-key',
    'expired_token_time' => (new DateTimeImmutable('+5 minutes', new DateTimeZone('Asia/Jakarta')))->format('Y-m-d H:i:s'),
    'id_user_status' => 1, 'status_perangkat' => 'Online', 'id_user_akses' => 1,
    'is_blocked' => 0, 'id_user' => 12, 'nama_user' => 'Admin', 'nama_role' => 'Super Admin',
];
$website = 'http://localhost/saimed-digital-link-hub';
$identity = sso_authenticate($db, $token, $website);
expect_sso($identity['id'] === '12' && $identity['name'] === 'Admin', 'Identitas SSO tidak sesuai');
expect_sso($db->parameters === [$token, $token, $website], 'Parameter query tidak sesuai');
expect_sso($identity['expires_at'] === $payload['exp'], 'Waktu kedaluwarsa harus memakai batas terdekat');
$earlierJwt = JWT::encode(['exp' => time() + 60], $key, 'HS256');
expect_sso(sso_authenticate($db, $earlierJwt, $website)['expires_at'] <= time() + 60, 'Kedaluwarsa JWT harus membatasi sesi');
$longerJwt = JWT::encode(['exp' => time() + 3600], $key, 'HS256');
expect_sso(sso_authenticate($db, $longerJwt, $website)['expires_at'] < time() + 3600, 'Kedaluwarsa database harus membatasi sesi');
expect_sso(sso_authenticate($db, JWT::encode($payload + ['id_user' => 12], $key, 'HS256'), $website)['id'] === '12', 'Token dengan id_user cocok ditolak');
expect_sso(sso_authenticate($db, JWT::encode($payload + ['id_user' => 99], $key, 'HS256'), $website) === null, 'id_user yang salah diterima');
$original = $db->row;
foreach ([['nama_role', 'User'], ['status_perangkat', 'Offline'], ['is_blocked', 1], ['expired_token_time', '2020-01-01 00:00:00'], ['expired_token_time', '']] as [$field, $value]) {
    $db->row = $original;
    $db->row[$field] = $value;
    expect_sso(sso_authenticate($db, $token, $website) === null, "Data tidak valid diterima: $field");
}
$db->row = $original;
expect_sso(sso_authenticate($db, JWT::encode(['exp' => time() - 1], $key, 'HS256'), $website) === null, 'JWT kedaluwarsa diterima');
expect_sso(sso_authenticate($db, JWT::encode($payload, 'wrong-secret', 'HS256'), $website) === null, 'Tanda tangan salah diterima');
expect_sso(sso_authenticate($db, 'bukan.jwt.valid', $website) === null, 'JWT rusak diterima');
$db->row = [];
expect_sso(sso_authenticate($db, $token, $website) === null, 'Token tanpa akses website diterima');
$db->row = $original;
putenv('SSO_JWT_SECRET_KEY');
expect_sso(sso_authenticate($db, $token, $website) === null, 'Key yang belum diatur harus ditolak');
putenv('SSO_JWT_SECRET_KEY=' . $key);
echo "Tes SSO berhasil.\n";
