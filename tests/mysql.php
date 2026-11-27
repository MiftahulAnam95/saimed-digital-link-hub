<?php

declare(strict_types=1);

require __DIR__ . '/../app.php';

// Gunakan database pengujian dengan schema.sql; semua perubahan dibatalkan.
$database = db();
$database->begin_transaction();
try {
    $key = 'test-' . bin2hex(random_bytes(8));
    $insert = $database->prepare(
        'INSERT INTO links (`key`, title, url, icon, sort_order, is_active, featured)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $insert->execute([$key, 'Tautan Uji', 'https://example.com', 'link', 9999, 1, 0]);
    $id = (int) $database->insert_id;

    $update = $database->prepare('UPDATE links SET is_active = ?, featured = ? WHERE id = ?');
    $update->execute([0, 1, $id]);
    $found = $database->prepare('SELECT is_active, featured FROM links WHERE id = ?');
    $found->execute([$id]);
    $row = $found->get_result()->fetch_assoc();
    if (!$row || boolval_db($row['is_active']) || !boolval_db($row['featured'])) {
        throw new RuntimeException('Perubahan status tautan gagal.');
    }

    $event = $database->prepare(
        'INSERT INTO analytics_events (event_type, link_id, link_key, visitor_id, ip_address, created_at)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $event->execute(['link_click', $id, $key, 'test-visitor', '2001:db8::1', gmdate('Y-m-d H:i:s')]);
    $check = $database->prepare('SELECT COUNT(*) AS total FROM analytics_events WHERE link_id = ? AND created_at >= ?');
    $check->execute([$id, gmdate('Y-m-d 00:00:00')]);
    if ((int) $check->get_result()->fetch_assoc()['total'] !== 1) {
        throw new RuntimeException('Pencatatan analytics gagal.');
    }

    $delete = $database->prepare('DELETE FROM links WHERE id = ?');
    $delete->execute([$id]);
    $check = $database->prepare('SELECT link_id, ip_address FROM analytics_events WHERE link_key = ?');
    $check->execute([$key]);
    $row = $check->get_result()->fetch_assoc();
    if (!$row || $row['link_id'] !== null || $row['ip_address'] !== '2001:db8::1') {
        throw new RuntimeException('Relasi ON DELETE SET NULL atau penyimpanan alamat IP gagal.');
    }
    echo "Pengujian MySQL berhasil.\n";
} finally {
    $database->rollback();
}
