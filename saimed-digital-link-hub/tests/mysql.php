<?php

declare(strict_types=1);

require __DIR__ . '/../app.php';

// Gunakan database pengujian dengan schema.sql; semua perubahan dibatalkan.
$database = db();
$database->beginTransaction();

try {
    $key = 'test-' . bin2hex(random_bytes(8));
    $insert = $database->prepare(
        'INSERT INTO links (`key`, title, url, icon, sort_order, is_active, featured)
         VALUES (:key, :title, :url, :icon, :sort_order, :active, :featured)'
    );
    $insert->execute([
        'key' => $key,
        'title' => 'Tautan Uji',
        'url' => 'https://example.com',
        'icon' => 'link',
        'sort_order' => 9999,
        'active' => 1,
        'featured' => 0,
    ]);
    $id = (int) $database->lastInsertId();

    $update = $database->prepare('UPDATE links SET is_active = :active, featured = :featured WHERE id = :id');
    $update->execute(['active' => 0, 'featured' => 1, 'id' => $id]);
    $found = $database->prepare('SELECT is_active, featured FROM links WHERE id = :id');
    $found->execute(['id' => $id]);
    $row = $found->fetch();
    if (!$row || boolval_db($row['is_active']) || !boolval_db($row['featured'])) {
        throw new RuntimeException('Perubahan status tautan gagal.');
    }

    $event = $database->prepare(
        'INSERT INTO analytics_events (event_type, link_id, link_key, visitor_id, created_at)
         VALUES (:type, :link_id, :link_key, :visitor, :created_at)'
    );
    $event->execute([
        'type' => 'link_click',
        'link_id' => $id,
        'link_key' => $key,
        'visitor' => 'test-visitor',
        'created_at' => gmdate('Y-m-d H:i:s'),
    ]);
    $check = $database->prepare('SELECT COUNT(*) FROM analytics_events WHERE link_id = :id AND created_at >= :start');
    $check->execute(['id' => $id, 'start' => gmdate('Y-m-d 00:00:00')]);
    if ((int) $check->fetchColumn() !== 1) {
        throw new RuntimeException('Pencatatan analytics gagal.');
    }

    $delete = $database->prepare('DELETE FROM links WHERE id = :id');
    $delete->execute(['id' => $id]);
    $check = $database->prepare('SELECT link_id FROM analytics_events WHERE link_key = :key');
    $check->execute(['key' => $key]);
    if ($check->fetchColumn() !== null) {
        throw new RuntimeException('Relasi ON DELETE SET NULL gagal.');
    }

    echo "Pengujian MySQL berhasil.\n";
} finally {
    $database->rollBack();
}
