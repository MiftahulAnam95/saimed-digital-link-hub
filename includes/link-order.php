<?php

declare(strict_types=1);

// Semua perubahan urutan memakai lock yang sama, termasuk saat tabel masih kosong.
function link_order_transaction($db, callable $work)
{
    $lockNameSql = "CONCAT('saimed-order-', LEFT(SHA2(DATABASE(), 256), 40))";
    $locked = false;
    $started = false;
    try {
        $lock = $db->query("SELECT GET_LOCK($lockNameSql, 10) AS acquired")->fetch_assoc();
        if ((int) ($lock['acquired'] ?? 0) !== 1) {
            throw new RuntimeException('Tidak dapat memperoleh kunci urutan tautan.');
        }
        $locked = true;
        $db->begin_transaction();
        $started = true;
        $result = $work();
        $db->commit();
        $started = false;
        return $result;
    } catch (Throwable $e) {
        if ($started) {
            try { $db->rollback(); } catch (Throwable $rollbackError) { error_log('SAIMED: rollback urutan gagal.'); }
        }
        throw $e;
    } finally {
        if ($locked) {
            try { $db->query("SELECT RELEASE_LOCK($lockNameSql) AS released"); }
            catch (Throwable $releaseError) { error_log('SAIMED: pelepasan kunci urutan gagal.'); }
        }
    }
}

// Membaca seluruh tautan, termasuk nonaktif, dalam urutan lama yang stabil.
function link_order_rows($db): array
{
    return $db->query('SELECT id, icon, sort_order FROM links ORDER BY sort_order, id FOR UPDATE')->fetch_all(MYSQLI_ASSOC);
}

// Snapshot digunakan untuk menolak pilihan posisi dari halaman yang sudah basi.
function link_order_version(array $rows): string
{
    $snapshot = [];
    foreach ($rows as $row) $snapshot[] = [(int) $row['id'], (int) $row['sort_order']];
    return hash('sha256', json_encode($snapshot));
}

// Merapikan data lama yang duplikat atau berlubang tanpa mengubah urutan relatifnya.
function link_order_write($db, array $rows): void
{
    $stmt = $db->prepare('UPDATE links SET sort_order = ? WHERE id = ?');
    foreach (array_values($rows) as $index => $row) {
        $position = $index + 1;
        if ((int) $row['sort_order'] !== $position) {
            $stmt->execute([$position, (int) $row['id']]);
        }
    }
}

function link_order_find(array $rows, int $id): int
{
    foreach ($rows as $index => $row) if ((int) $row['id'] === $id) return $index;
    throw new InvalidArgumentException('Tautan tidak ditemukan.');
}

function link_order_move(array $rows, int $id, int $position): array
{
    $index = link_order_find($rows, $id);
    if ($position < 1 || $position > count($rows)) {
        throw new InvalidArgumentException('Posisi tidak tersedia. Perbarui halaman dan pilih kembali.');
    }
    $item = array_splice($rows, $index, 1);
    array_splice($rows, $position - 1, 0, $item);
    return $rows;
}
