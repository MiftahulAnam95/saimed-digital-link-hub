<?php

declare(strict_types=1);

require __DIR__ . '/../includes/sso.php';

function expect_profile(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
class ProfileDatabase extends PDO
{
    public array $row = [];
    public array $params = [];
    public bool $fail = false;
    public function __construct() {}
    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        expect_profile(str_contains($query, 'WHERE id_user = :id_user'), 'Query harus dibatasi user dan menggunakan parameter');
        if ($this->fail) throw new RuntimeException('Database tidak tersedia');
        return new ProfileStatement($this);
    }
}
class ProfileStatement extends PDOStatement
{
    public function __construct(private ProfileDatabase $db) {}
    public function execute(?array $params = null): bool { $this->db->params = $params ?? []; return true; }
    public function fetch(int $mode = PDO::FETCH_DEFAULT, int $cursorOrientation = PDO::FETCH_ORI_NEXT, int $cursorOffset = 0): mixed { return $this->db->row ?: false; }
}
$db = new ProfileDatabase();
putenv('SSO_PROFILE_PHOTO_BASE_URL=https://example.test/profile%20user');
$db->row = ['nama_file' => 'foto saya.png'];
expect_profile(sso_profile_photo_url($db, '12') === 'https://example.test/profile%20user/foto%20saya.png', 'Foto harus dikodekan sebagai nama file');
expect_profile($db->params === ['id_user' => '12'], 'Foto harus berdasarkan ID user autentik');
foreach (['', '../rahasia.png', '..\\rahasia.png', "nama\n.png", '..'] as $invalid) {
    $db->row = ['nama_file' => $invalid];
    expect_profile(sso_profile_photo_url($db, '12') === null, 'Nama file tidak aman harus ditolak');
}
$db->row = [];
expect_profile(sso_profile_photo_url($db, '12') === null, 'Foto kosong harus memakai fallback');
$db->row = ['nama_file' => 'foto.png'];
putenv('SSO_PROFILE_PHOTO_BASE_URL=javascript:alert(1)');
expect_profile(sso_profile_photo_url($db, '12') === null, 'URL tidak aman harus ditolak');
putenv('SSO_PROFILE_PHOTO_BASE_URL');
expect_profile(sso_profile_photo_url($db, '12') === 'https://test-user-account.mandirialkesindo.co.id/profile%20user/foto.png', 'Lokasi bawaan harus sesuai test-system');
$db->fail = true;
expect_profile(sso_profile_photo_url($db, '12') === null, 'Gangguan query tidak boleh menggagalkan login');
putenv('SSO_PROFILE_PHOTO_BASE_URL');
echo "Tes foto profil SSO berhasil.\n";
