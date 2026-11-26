<?php

declare(strict_types=1);

// Memproses form login dan tindakan administrator.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'sso-login' && !$loggedIn) sso_handle_login_action();
    if (!$loggedIn) {
        http_response_code(403);
        exit('Akses ditolak');
    }
    if ($action === 'logout') sso_handle_logout_action();
    if (!in_array($action, ['save', 'add', 'delete'], true)) {
        $_SESSION['notice'] = 'Tindakan tidak dikenal.';
        $_SESSION['notice_type'] = 'error';
        redirect_admin($tab);
    }
    $newIcon = null;
    try {
        $db = db();
        if ($action === 'save' || $action === 'add') {
            $title = substr(trim((string) ($_POST['title'] ?? '')), 0, 80);
            $url = safe_url((string) ($_POST['url'] ?? ''));
            if (!$title || !$url) {
                throw new InvalidArgumentException('Nama dan URL http/https atau tautan internal yang valid wajib diisi.');
            }
            $id = null;
            $position = null;
            if ($action === 'save') {
                $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                if (!$id) throw new InvalidArgumentException('ID tidak valid.');
                $position = filter_var($_POST['sort_order'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                if (!$position) throw new InvalidArgumentException('Pilih posisi urutan yang valid.');
            }
            $newIcon = upload_link_icon($_FILES['icon_file'] ?? null, $action === 'add');
            $oldIcon = link_order_transaction($db, function () use ($db, $action, $title, $url, $newIcon, $id, $position) {
                $rows = link_order_rows($db);
                if ($action === 'add') {
                    link_order_write($db, $rows);
                    $key = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($title)), '-') ?: 'tautan';
                    $stmt = $db->prepare('INSERT INTO links (`key`, title, url, icon, sort_order) VALUES (?, ?, ?, ?, ?)');
                    $stmt->execute([substr($key, 0, 38) . '-' . bin2hex(random_bytes(3)), $title, $url, $newIcon, count($rows) + 1]);
                    return null;
                }
                $index = link_order_find($rows, $id);
                $version = $_POST['order_version'] ?? null;
                if (!is_string($version) || !hash_equals(link_order_version($rows), $version)) {
                    throw new InvalidArgumentException('Urutan tautan telah berubah. Perbarui halaman dan pilih kembali.');
                }
                $existingIcon = (string) $rows[$index]['icon'];
                $reordered = link_order_move($rows, $id, $position);
                $stmt = $db->prepare('UPDATE links SET title = ?, url = ?, icon = ?, is_active = ?, featured = ?, updated_at = NOW() WHERE id = ?');
                $stmt->execute([$title, $url, $newIcon ?? $existingIcon, isset($_POST['is_active']) ? 1 : 0, isset($_POST['featured']) ? 1 : 0, $id]);
                link_order_write($db, $reordered);
                return $existingIcon;
            });
            if ($newIcon !== null) {
                $newIcon = null; // File baru kini tercatat dalam database.
                if ($oldIcon !== null) delete_uploaded_icon($oldIcon);
            }
            $_SESSION['notice'] = 'Tautan berhasil disimpan.';
            $_SESSION['notice_type'] = 'success';
        } elseif ($action === 'delete') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if (!$id) throw new InvalidArgumentException('ID tidak valid.');
            $oldIcon = link_order_transaction($db, function () use ($db, $id) {
                $rows = link_order_rows($db);
                $index = link_order_find($rows, $id);
                $oldIcon = (string) $rows[$index]['icon'];
                array_splice($rows, $index, 1);
                $stmt = $db->prepare('DELETE FROM links WHERE id = ?');
                $stmt->execute([$id]);
                link_order_write($db, $rows);
                return $oldIcon;
            });
            delete_uploaded_icon($oldIcon);
            $_SESSION['notice'] = 'Tautan berhasil dihapus.';
            $_SESSION['notice_type'] = 'success';
        }
    } catch (Throwable $e) {
        if ($newIcon !== null) delete_uploaded_icon($newIcon);
        error_log('SAIMED admin: ' . $e->getMessage());
        $_SESSION['notice_type'] = 'error';
        $_SESSION['notice'] = $e instanceof InvalidArgumentException ? $e->getMessage() : 'Gagal menyimpan data. Periksa koneksi dan skema database.';
    }
    redirect_admin(in_array($action, ['save', 'add', 'delete'], true) ? 'links' : $tab);
}
