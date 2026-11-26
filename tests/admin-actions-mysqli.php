<?php

declare(strict_types=1);

// Simulasi MySQLi dan unggahan untuk memeriksa alur tindakan tanpa database asli.
function check_csrf(): void {}
function safe_url(string $url): ?string { return str_starts_with($url, 'https://') ? $url : null; }
function redirect_admin(string $tab = 'overview'): void { throw new LogicException('redirect:' . $tab); }
function expect_admin(bool $condition, string $message): void { if (!$condition) throw new RuntimeException($message); }
function upload_link_icon(?array $file, bool $required): ?string {
    if (!$file && $required) throw new InvalidArgumentException('Pilih gambar logo/icon terlebih dahulu.');
    return $file['name'] ?? null;
}
function delete_uploaded_icon(string $name): void { global $removedIcons; $removedIcons[] = $name; }
class AdminFakeResult {
    public function __construct(private ?array $row) {}
    public function fetch_assoc(): ?array { return $this->row; }
}
class AdminFakeStatement {
    public int $affected_rows = 1;
    public function __construct(private AdminFakeDb $database, private string $sql) {}
    public function execute(array $params): bool {
        expect_admin(substr_count($this->sql, '?') === count($params), 'Jumlah parameter tidak cocok');
        $this->database->calls[] = [$this->sql, $params];
        if ($this->database->fail && str_starts_with($this->sql, 'INSERT')) throw new RuntimeException('Simulasi gagal');
        return true;
    }
    public function get_result(): AdminFakeResult { return new AdminFakeResult($this->database->row); }
}
class AdminFakeDb {
    public array $calls = [];
    public bool $fail = false;
    public ?array $row = ['icon' => 'shopee'];
    public function query(string $sql): AdminFakeResult { return new AdminFakeResult(['next_order' => 4]); }
    public function prepare(string $sql): AdminFakeStatement { return new AdminFakeStatement($this, $sql); }
}
function db(): AdminFakeDb { global $fakeDb; return $fakeDb; }
function run_admin_action(array $input, AdminFakeDb $database, ?string $icon = null): void {
    global $fakeDb, $removedIcons;
    $fakeDb = $database;
    $removedIcons = [];
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SESSION = [];
    $_POST = $input;
    $_FILES = $icon === null ? [] : ['icon_file' => ['name' => $icon]];
    $loggedIn = true;
    $tab = 'links';
    try {
        require __DIR__ . '/../includes/admin-actions.php';
        throw new RuntimeException('Tindakan harus berakhir dengan redirect');
    } catch (LogicException $e) {
        expect_admin($e->getMessage() === 'redirect:links', 'Tujuan redirect salah');
    }
}
$new = 'u' . str_repeat('a', 24) . '.png';
$old = 'u' . str_repeat('b', 24) . '.jpg';
$db = new AdminFakeDb();
run_admin_action(['action' => 'add', 'title' => 'Toko Baru', 'url' => 'https://example.test'], $db, $new);
expect_admin(str_contains($db->calls[0][0], 'INSERT INTO links'), 'Aksi tambah harus memakai INSERT');
expect_admin(count($db->calls[0][1]) === 5 && $db->calls[0][1][1] === 'Toko Baru' && $db->calls[0][1][2] === 'https://example.test' && $db->calls[0][1][3] === $new && $db->calls[0][1][4] === 4, 'Parameter tambah salah');
expect_admin($_SESSION['notice'] === 'Tautan berhasil disimpan.' && !$removedIcons, 'Notifikasi tambah salah');
$db = new AdminFakeDb();
run_admin_action(['action' => 'add', 'title' => 'Toko', 'url' => 'https://example.test'], $db);
expect_admin(!$db->calls && str_contains($_SESSION['notice'], 'Pilih gambar'), 'Logo harus diwajibkan');
$db = new AdminFakeDb(); $db->fail = true;
run_admin_action(['action' => 'add', 'title' => 'Toko', 'url' => 'https://example.test'], $db, $new);
expect_admin($removedIcons === [$new], 'Logo baru harus dibersihkan jika query gagal');
$db = new AdminFakeDb(); $db->row = ['icon' => $old];
run_admin_action(['action' => 'save', 'id' => '7', 'title' => 'Toko', 'url' => 'https://example.test', 'sort_order' => '2', 'is_active' => '1'], $db);
expect_admin($db->calls[1][1] === ['Toko', 'https://example.test', $old, 2, 1, 0, 7] && !$removedIcons, 'Simpan tanpa unggah harus mempertahankan logo');
$db = new AdminFakeDb(); $db->row = ['icon' => $old];
run_admin_action(['action' => 'save', 'id' => '7', 'title' => 'Toko', 'url' => 'https://example.test'], $db, $new);
expect_admin($db->calls[1][1][2] === $new && $removedIcons === [$old], 'Logo pengganti harus disimpan dan logo lama dibersihkan');
$db = new AdminFakeDb(); $db->row = null;
run_admin_action(['action' => 'save', 'id' => '7', 'title' => 'Toko', 'url' => 'https://example.test'], $db, $new);
expect_admin($_SESSION['notice'] === 'Tautan tidak ditemukan.' && !$removedIcons, 'ID yang tidak ada harus ditolak sebelum upload');
$db = new AdminFakeDb(); $db->row = ['icon' => $old];
run_admin_action(['action' => 'delete', 'id' => '7'], $db);
expect_admin(str_contains($db->calls[1][0], 'DELETE FROM links') && $db->calls[1][1] === [7] && $removedIcons === [$old], 'Hapus harus membersihkan logo terkait');
$db = new AdminFakeDb();
run_admin_action(['action' => 'add', 'title' => 'Toko', 'url' => 'javascript:alert(1)'], $db, $new);
expect_admin(!$db->calls && str_contains($_SESSION['notice'], 'valid wajib diisi'), 'URL berbahaya harus ditolak sebelum query');
echo "Tes tindakan admin MySQLi berhasil.\n";
