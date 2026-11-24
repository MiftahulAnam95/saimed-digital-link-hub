<?php

declare(strict_types=1);

// Memproses form login dan tindakan administrator.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'sso-login' && !$loggedIn) {
        sso_handle_login_action();
    }
    if (!$loggedIn) {
        http_response_code(403);
        exit('Akses ditolak');
    }
    if ($action === 'logout') {
        sso_handle_logout_action();
    }
    if (!in_array($action, ['save', 'add', 'delete'], true)) {
        $_SESSION['notice'] = 'Tindakan tidak dikenal.';
        redirect_admin($tab);
    }
    try {
        $db = db();
        if ($action === 'save' || $action === 'add') {
            $title = substr(trim((string) ($_POST['title'] ?? '')), 0, 80);
            $url = safe_url((string) ($_POST['url'] ?? ''));
            $icon = in_array($_POST['icon'] ?? '', ICONS, true)
                ? $_POST['icon']
                : 'link';
            if (!$title || !$url) {
                throw new InvalidArgumentException(
                    'Nama dan URL http/https atau tautan internal yang valid wajib diisi.',
                );
            }
            if ($action === 'add') {
                $key = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($title)), '-');
                $key = $key ?: 'tautan';
                $sortOrder = (int) $db->query('SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_order FROM links')->fetch_assoc()['next_order'];
                $stmt = $db->prepare(
                    'INSERT INTO links (`key`, title, url, icon, sort_order)
                     VALUES (?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    substr($key, 0, 38) . '-' . bin2hex(random_bytes(3)),
                    $title, $url, $icon, $sortOrder,
                ]);
            } else {
                $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, [
                    'options' => ['min_range' => 1],
                ]);
                if (!$id) {
                    throw new InvalidArgumentException('ID tidak valid.');
                }
                $stmt = $db->prepare(
                    'UPDATE links
                     SET title = ?, url = ?, icon = ?, sort_order = ?,
                         is_active = ?, featured = ?, updated_at = NOW()
                     WHERE id = ?'
                );
                $stmt->execute([
                    $title, $url, $icon, (int) ($_POST['sort_order'] ?? 0),
                    isset($_POST['is_active']) ? 1 : 0,
                    isset($_POST['featured']) ? 1 : 0,
                    $id,
                ]);
                // MySQL dapat mengembalikan 0 jika nilai tidak berubah.
                if (!$stmt->affected_rows) {
                    $check = $db->prepare('SELECT id FROM links WHERE id = ?');
                    $check->execute([$id]);
                    if (!$check->get_result()->fetch_assoc()) {
                        throw new InvalidArgumentException('Tautan tidak ditemukan.');
                    }
                }
            }
            $_SESSION['notice'] = 'Tautan berhasil disimpan.';
        } elseif ($action === 'delete') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1],
            ]);
            if (!$id) {
                throw new InvalidArgumentException('ID tidak valid.');
            }
            $stmt = $db->prepare('DELETE FROM links WHERE id = ?');
            $stmt->execute([$id]);
            $_SESSION['notice'] = 'Tautan berhasil dihapus.';
        } else {
            throw new InvalidArgumentException('Tindakan tidak dikenal.');
        }
    } catch (Throwable $e) {
        error_log('SAIMED admin: ' . $e->getMessage());
        $_SESSION['notice'] =
            $e instanceof InvalidArgumentException
                ? $e->getMessage()
                : 'Gagal menyimpan data. Periksa koneksi dan skema database.';
    }
    redirect_admin(
        in_array($action, ['save', 'add', 'delete'], true) ? 'links' : $tab,
    );
}
