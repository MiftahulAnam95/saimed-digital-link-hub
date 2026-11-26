<?php

declare(strict_types=1);

// Simulasi MySQLi: menguji transaksi, urutan, validasi dan siklus file tanpa MySQL asli.
require __DIR__ . '/../includes/link-order.php';
if (!defined('MYSQLI_ASSOC')) define('MYSQLI_ASSOC', 1);
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
    public function __construct(private array $rows) {}
    public function fetch_assoc(): ?array { return $this->rows[0] ?? null; }
    public function fetch_all(int $mode): array { return $this->rows; }
}
class AdminFakeStatement {
    public function __construct(private AdminFakeDb $db, private string $sql) {}
    public function execute(array $params): bool {
        expect_admin(substr_count($this->sql, '?') === count($params), 'Jumlah parameter salah');
        $this->db->calls[] = [$this->sql, $params];
        if ($this->db->failWrites || ($this->db->failAt && count($this->db->calls) === $this->db->failAt)) throw new RuntimeException('Simulasi gagal');
        if (str_starts_with($this->sql, 'INSERT')) {
            $this->db->rows[] = ['id' => 10, 'icon' => $params[3], 'sort_order' => $params[4], 'title' => $params[1]];
        } elseif (str_starts_with($this->sql, 'DELETE')) {
            $this->db->rows = array_values(array_filter($this->db->rows, fn($row) => $row['id'] !== $params[0]));
        } elseif (str_starts_with($this->sql, 'UPDATE links SET sort_order')) {
            foreach ($this->db->rows as &$row) if ($row['id'] === $params[1]) $row['sort_order'] = $params[0];
        } elseif (str_starts_with($this->sql, 'UPDATE links SET title')) {
            foreach ($this->db->rows as &$row) if ($row['id'] === $params[5]) { $row['title'] = $params[0]; $row['icon'] = $params[2]; }
        } else throw new RuntimeException('SQL tidak diharapkan: ' . $this->sql);
        return true;
    }
}
class AdminFakeDb {
    public array $calls = [];
    public array $rows;
    public array $before = [];
    public bool $failWrites = false;
    public int $failAt = 0;
    public bool $lockAvailable = true;
    public bool $released = false;
    public int $rollbacks = 0;
    public function __construct() {
        $this->rows = [
            ['id' => 1, 'icon' => 'first', 'sort_order' => 2, 'title' => 'Pertama'],
            ['id' => 7, 'icon' => 'old', 'sort_order' => 2, 'title' => 'Kedua'],
            ['id' => 8, 'icon' => 'third', 'sort_order' => 8, 'title' => 'Ketiga'],
        ];
    }
    public function query(string $sql): AdminFakeResult {
        if (str_contains($sql, 'GET_LOCK')) return new AdminFakeResult([['acquired' => $this->lockAvailable ? 1 : 0]]);
        if (str_contains($sql, 'RELEASE_LOCK')) { $this->released = true; return new AdminFakeResult([['released' => 1]]); }
        if (str_contains($sql, 'FOR UPDATE')) { $rows = $this->rows; usort($rows, fn($a, $b) => [$a['sort_order'], $a['id']] <=> [$b['sort_order'], $b['id']]); return new AdminFakeResult($rows); }
        throw new RuntimeException('SQL tidak diharapkan: ' . $sql);
    }
    public function prepare(string $sql): AdminFakeStatement { return new AdminFakeStatement($this, $sql); }
    public function begin_transaction(): bool { $this->before = $this->rows; return true; }
    public function commit(): bool { return true; }
    public function rollback(): bool { $this->rows = $this->before; $this->rollbacks++; return true; }
}
function db(): AdminFakeDb { global $fakeDb; return $fakeDb; }
function run_admin_action(array $input, AdminFakeDb $database, ?string $icon = null): void {
    global $fakeDb, $removedIcons;
    $fakeDb = $database; $removedIcons = [];
    $_SERVER['REQUEST_METHOD'] = 'POST'; $_SESSION = []; $_POST = $input;
    $_FILES = $icon === null ? [] : ['icon_file' => ['name' => $icon]];
    $loggedIn = true; $tab = 'links';
    try { require __DIR__ . '/../includes/admin-actions.php'; throw new RuntimeException('Tidak redirect'); }
    catch (LogicException $e) { expect_admin($e->getMessage() === 'redirect:links', 'Redirect salah'); }
}
function save_input(AdminFakeDb $db, int $id, $position): array {
    return ['action' => 'save', 'id' => (string) $id, 'title' => 'Toko', 'url' => 'https://example.test', 'sort_order' => $position, 'order_version' => link_order_version(link_order_rows($db))];
}
function ordered_ids(AdminFakeDb $db): array { return array_column(link_order_rows($db), 'id'); }
function ordered_positions(AdminFakeDb $db): array { return array_column(link_order_rows($db), 'sort_order'); }
$new = 'uaaaaaaaaaaaaaaaaaaaaaaaa.png';
$db = new AdminFakeDb();
run_admin_action(['action' => 'add', 'title' => 'Toko Baru', 'url' => 'https://example.test'], $db, $new);
expect_admin(ordered_ids($db) === [1,7,8,10] && ordered_positions($db) === [1,2,3,4], 'Tambah harus merapikan urutan lama dan menambahkan ke akhir');
expect_admin($_SESSION['notice_type'] === 'success' && $db->released && !$removedIcons, 'Tambah harus sukses dan melepas lock');
$db = new AdminFakeDb(); $db->rows = [];
run_admin_action(['action' => 'add', 'title' => 'Pertama', 'url' => 'https://example.test'], $db, $new);
expect_admin(ordered_ids($db) === [10] && ordered_positions($db) === [1], 'Tabel kosong harus memulai posisi 1');
$db = new AdminFakeDb();
run_admin_action(save_input($db, 8, '1'), $db);
expect_admin(ordered_ids($db) === [8,1,7] && ordered_positions($db) === [1,2,3], 'Pindah ke atas harus menggeser tautan lain');
expect_admin(!$removedIcons && $db->released, 'Logo lama harus dipertahankan');
run_admin_action(save_input($db, 8, '3'), $db, $new);
expect_admin(ordered_ids($db) === [1,7,8] && ordered_positions($db) === [1,2,3] && $removedIcons === ['third'], 'Pindah ke bawah dan ganti logo gagal');
run_admin_action(save_input($db, 7, '2'), $db);
expect_admin(ordered_ids($db) === [1,7,8], 'Posisi yang sama tidak boleh berubah');
$db = new AdminFakeDb();
run_admin_action(['action' => 'delete', 'id' => '7'], $db);
expect_admin(ordered_ids($db) === [1,8] && ordered_positions($db) === [1,2] && $removedIcons === ['old'], 'Hapus harus menutup celah dan membersihkan logo');
foreach (['0', '-1', '4', '2.5', 'abc', '', ['2']] as $bad) {
    $db = new AdminFakeDb(); $before = $db->rows;
    run_admin_action(save_input($db, 7, $bad), $db, $new);
    expect_admin($_SESSION['notice_type'] === 'error' && $db->rows === $before, 'Posisi tidak valid harus ditolak');
}
$db = new AdminFakeDb(); $stale = save_input($db, 7, '1');
run_admin_action(['action' => 'delete', 'id' => '1'], $db);
$before = $db->rows;
run_admin_action($stale, $db, $new);
expect_admin(str_contains($_SESSION['notice'], 'telah berubah') && $db->rows === $before && $removedIcons === [$new], 'Pilihan basi harus ditolak dan logo baru dibersihkan');
$db = new AdminFakeDb(); $input = save_input($db, 7, '1'); unset($input['order_version']);
run_admin_action($input, $db);
expect_admin($_SESSION['notice_type'] === 'error', 'Snapshot urutan harus diwajibkan');
$db = new AdminFakeDb(); $before = $db->rows; $db->failWrites = true;
run_admin_action(save_input($db, 7, '1'), $db, $new);
expect_admin($db->rollbacks === 1 && $db->rows === $before && $removedIcons === [$new] && $db->released, 'Gagal query harus rollback, menghapus upload dan melepas lock');
$db = new AdminFakeDb(); $before = $db->rows; $db->failAt = 2;
run_admin_action(save_input($db, 7, '1'), $db, $new);
expect_admin($db->rollbacks === 1 && $db->rows === $before && $removedIcons === [$new], 'Kegagalan setelah perubahan metadata harus rollback seluruh perubahan');
$db = new AdminFakeDb(); $db->lockAvailable = false;
run_admin_action(['action' => 'add', 'title' => 'Toko', 'url' => 'https://example.test'], $db, $new);
expect_admin($_SESSION['notice_type'] === 'error' && $removedIcons === [$new] && !$db->calls, 'Gagal lock tidak boleh menulis');
$db = new AdminFakeDb();
run_admin_action(save_input($db, 99, '1'), $db, $new);
expect_admin($_SESSION['notice'] === 'Tautan tidak ditemukan.' && $removedIcons === [$new], 'ID yang hilang harus ditolak');
$db = new AdminFakeDb();
run_admin_action(['action' => 'add', 'title' => 'Toko', 'url' => 'javascript:alert(1)'], $db, $new);
expect_admin(!$db->calls && str_contains($_SESSION['notice'], 'valid wajib diisi'), 'URL berbahaya harus ditolak');
echo "Tes tindakan admin MySQLi berhasil.\n";
