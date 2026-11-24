<?php

declare(strict_types=1);

// Simulasi MySQLi untuk menguji parameter dan alur tindakan tanpa database asli.
const ICONS = ['link', 'website', 'shopee', 'tokopedia', 'whatsapp'];
function check_csrf(): void {}
function safe_url(string $url): ?string { return str_starts_with($url, 'https://') ? $url : null; }
function redirect_admin(string $tab = 'overview'): void { throw new LogicException('redirect:' . $tab); }
function expect_admin(bool $condition, string $message): void { if (!$condition) throw new RuntimeException($message); }
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
        $this->affected_rows = $this->database->affectedRows;
        return true;
    }
    public function get_result(): AdminFakeResult { return new AdminFakeResult($this->database->row); }
}
class AdminFakeDb {
    public array $calls = [];
    public int $affectedRows = 1;
    public ?array $row = ['id' => 7];
    public function query(string $sql): AdminFakeResult { return new AdminFakeResult(['next_order' => 4]); }
    public function prepare(string $sql): AdminFakeStatement { return new AdminFakeStatement($this, $sql); }
}
function db(): AdminFakeDb { global $fakeDb; return $fakeDb; }
function run_admin_action(array $input, AdminFakeDb $database): void {
    global $fakeDb;
    $fakeDb = $database;
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SESSION = [];
    $_POST = $input;
    $loggedIn = true;
    $tab = 'links';
    try {
        require __DIR__ . '/../includes/admin-actions.php';
        throw new RuntimeException('Tindakan harus berakhir dengan redirect');
    } catch (LogicException $e) {
        expect_admin($e->getMessage() === 'redirect:links', 'Tujuan redirect salah');
    }
}
$db = new AdminFakeDb();
run_admin_action(['action' => 'add', 'title' => 'Toko Baru', 'url' => 'https://example.test', 'icon' => 'link'], $db);
expect_admin(str_contains($db->calls[0][0], 'INSERT INTO links'), 'Aksi tambah harus memakai INSERT');
expect_admin(count($db->calls[0][1]) === 5 && $db->calls[0][1][1] === 'Toko Baru' && $db->calls[0][1][4] === 4, 'Parameter tambah salah');
expect_admin($_SESSION['notice'] === 'Tautan berhasil disimpan.', 'Notifikasi tambah salah');

$db = new AdminFakeDb();
$db->affectedRows = 0;
run_admin_action(['action' => 'save', 'id' => '7', 'title' => 'Toko', 'url' => 'https://example.test', 'icon' => 'link', 'sort_order' => '2', 'is_active' => '1'], $db);
expect_admin(str_contains($db->calls[0][0], 'UPDATE links') && $db->calls[0][1] === ['Toko', 'https://example.test', 'link', 2, 1, 0, 7], 'Parameter simpan salah');
expect_admin(str_contains($db->calls[1][0], 'SELECT id') && $db->calls[1][1] === [7], 'Data tidak berubah harus diperiksa');
expect_admin($_SESSION['notice'] === 'Tautan berhasil disimpan.', 'Data tidak berubah harus tetap berhasil');

$db = new AdminFakeDb();
$db->affectedRows = 0;
$db->row = null;
run_admin_action(['action' => 'save', 'id' => '7', 'title' => 'Toko', 'url' => 'https://example.test'], $db);
expect_admin($_SESSION['notice'] === 'Tautan tidak ditemukan.', 'ID yang tidak ada harus ditolak');

$db = new AdminFakeDb();
run_admin_action(['action' => 'delete', 'id' => '7'], $db);
expect_admin(str_contains($db->calls[0][0], 'DELETE FROM links') && $db->calls[0][1] === [7], 'Parameter hapus salah');
expect_admin($_SESSION['notice'] === 'Tautan berhasil dihapus.', 'Notifikasi hapus salah');

$db = new AdminFakeDb();
run_admin_action(['action' => 'add', 'title' => 'Toko', 'url' => 'javascript:alert(1)'], $db);
expect_admin(!$db->calls && str_contains($_SESSION['notice'], 'valid wajib diisi'), 'URL berbahaya harus ditolak sebelum query');
echo "Tes tindakan admin MySQLi berhasil.\n";
