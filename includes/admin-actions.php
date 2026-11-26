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
            if ($action === 'add') {
                $newIcon = upload_link_icon($_FILES['icon_file'] ?? null, true);
                $key = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($title)), '-') ?: 'tautan';
                $sortOrder = (int) $db->query('SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_order FROM links')->fetch_assoc()['next_order'];
                $stmt = $db->prepare('INSERT INTO links (`key`, title, url, icon, sort_order) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([substr($key, 0, 38) . '-' . bin2hex(random_bytes(3)), $title, $url, $newIcon, $sortOrder]);
                $newIcon = null; // Sudah tercatat; jangan hapus pada penanganan kesalahan.
            } else {
                $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                if (!$id) throw new InvalidArgumentException('ID tidak valid.');
                $check = $db->prepare('SELECT icon FROM links WHERE id = ?');
                $check->execute([$id]);
                $existing = $check->get_result()->fetch_assoc();
                if (!$existing) throw new InvalidArgumentException('Tautan tidak ditemukan.');
                $newIcon = upload_link_icon($_FILES['icon_file'] ?? null, false);
                $stmt = $db->prepare('UPDATE links SET title = ?, url = ?, icon = ?, sort_order = ?, is_active = ?, featured = ?, updated_at = NOW() WHERE id = ?');
                $stmt->execute([$title, $url, $newIcon ?? $existing['icon'], (int) ($_POST['sort_order'] ?? 0), isset($_POST['is_active']) ? 1 : 0, isset($_POST['featured']) ? 1 : 0, $id]);
                if ($newIcon !== null) {
                    delete_uploaded_icon((string) $existing['icon']);
                    $newIcon = null;
                }
            }
            $_SESSION['notice'] = 'Tautan berhasil disimpan.';
        } elseif ($action === 'delete') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if (!$id) throw new InvalidArgumentException('ID tidak valid.');
            $check = $db->prepare('SELECT icon FROM links WHERE id = ?');
            $check->execute([$id]);
            $existing = $check->get_result()->fetch_assoc();
            $stmt = $db->prepare('DELETE FROM links WHERE id = ?');
            $stmt->execute([$id]);
            if ($existing) delete_uploaded_icon((string) $existing['icon']);
            $_SESSION['notice'] = 'Tautan berhasil dihapus.';
        }
    } catch (Throwable $e) {
        if ($newIcon !== null) delete_uploaded_icon($newIcon);
        error_log('SAIMED admin: ' . $e->getMessage());
        $_SESSION['notice'] = $e instanceof InvalidArgumentException ? $e->getMessage() : 'Gagal menyimpan data. Periksa koneksi dan skema database.';
    }
    redirect_admin(in_array($action, ['save', 'add', 'delete'], true) ? 'links' : $tab);
}
