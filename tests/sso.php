<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../includes/sso.php';

use Firebase\JWT\JWT;

function expect_sso(bool $value, string $message): void
{
    if (!$value) {
        throw new RuntimeException($message);
    }
}

// Simulasi database: tidak memerlukan atau mengubah database pengguna SSO asli.
class SsoTestDatabase extends PDO
{
    public array $row = [];
    public array $parameters = [];
    public function __construct() {}
    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        expect_sso(str_contains($query, ':token') && str_contains($query, ':website'), 'Query harus menggunakan parameter');
        return new SsoTestStatement($this);
    }
}
class SsoTestStatement extends PDOStatement
{
    private SsoTestDatabase $database;
    public function __construct(SsoTestDatabase $database) { $this->database = $database; }
    public function execute(?array $params = null): bool
    {
        $this->database->parameters = $params ?? [];
        return true;
    }
    public function fetch(int $mode = PDO::FETCH_DEFAULT, int $cursorOrientation = PDO::FETCH_ORI_NEXT, int $cursorOffset = 0): mixed
    {
        return $this->database->row ?: false;
    }
}

$db = new SsoTestDatabase();
$key = 'test-key-not-for-production';
$token = JWT::encode(['id_user' => 12, 'exp' => time() + 300], $key, 'HS256');
$db->row = [
    'key_token' => $key,
    'expired_token_time' => (new DateTimeImmutable('+5 minutes', new DateTimeZone('Asia/Jakarta')))->format('Y-m-d H:i:s'),
    'id_user_status' => 1, 'status_perangkat' => 'Online', 'id_user_akses' => 1,
    'is_blocked' => 0, 'id_user' => 12, 'nama_user' => 'Admin', 'nama_role' => 'Super Admin',
];
$website = 'http://localhost/saimed-digital-link-hub';
expect_sso(sso_authenticate($db, $token, $website)['id'] === '12', 'Token valid ditolak');
expect_sso($db->parameters === ['token' => $token, 'website' => $website], 'Parameter query tidak sesuai');
$original = $db->row;
foreach ([['nama_role', 'User'], ['status_perangkat', 'Offline'], ['is_blocked', 1], ['id_user', 99], ['key_token', 'wrong'], ['expired_token_time', '2020-01-01 00:00:00'], ['expired_token_time', '']] as [$field, $value]) {
    $db->row = $original;
    $db->row[$field] = $value;
    expect_sso(sso_authenticate($db, $token, $website) === null, "Data tidak valid diterima: $field");
}
$db->row = $original;
expect_sso(sso_authenticate($db, JWT::encode(['id_user' => 12, 'exp' => time() - 1], $key, 'HS256'), $website) === null, 'JWT kedaluwarsa diterima');
expect_sso(sso_authenticate($db, 'bukan.jwt.valid', $website) === null, 'JWT rusak diterima');
$db->row = [];
expect_sso(sso_authenticate($db, $token, $website) === null, 'Token tanpa akses website diterima');
echo "Tes SSO berhasil.\n";
