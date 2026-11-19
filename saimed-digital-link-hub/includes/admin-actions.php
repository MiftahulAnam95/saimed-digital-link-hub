<?php

declare(strict_types=1);

// Memproses form login dan tindakan administrator.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'login') {
        $password = getenv('ADMIN_PASSWORD');
        $attempts = $_SESSION['login_attempts'] ?? [];
        $attempts = array_values(
            array_filter($attempts, fn($time) => $time > time() - 900),
        );
        if (count($attempts) >= 10) {
            $_SESSION['notice'] =
                'Terlalu banyak percobaan. Coba lagi dalam 15 menit.';
        } elseif (
            is_string($password) &&
            $password !== '' &&
            hash_equals($password, (string) ($_POST['password'] ?? ''))
        ) {
            session_regenerate_id(true);
            $_SESSION['admin'] = true;
            $_SESSION['login_attempts'] = [];
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        } else {
            $attempts[] = time();
            $_SESSION['login_attempts'] = $attempts;
            $_SESSION['notice'] =
                'Kata sandi administrator tidak sesuai atau belum dikonfigurasi.';
        }
        redirect_admin();
    }
    if (!$loggedIn) {
        http_response_code(403);
        exit('Akses ditolak');
    }
    if ($action === 'logout') {
        $_SESSION = [];
        session_destroy();
        redirect_admin();
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
                $stmt = $db->prepare(
                    'INSERT INTO links (key, title, url, icon, sort_order)
                     VALUES (:key, :title, :url, :icon,
                         (SELECT COALESCE(MAX(sort_order), 0) + 1 FROM links))',
                );
                $stmt->execute([
                    'key' =>
                        substr($key, 0, 38) . '-' . bin2hex(random_bytes(3)),
                    'title' => $title,
                    'url' => $url,
                    'icon' => $icon,
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
                     SET title = :title, url = :url, icon = :icon, sort_order = :sort_order,
                         is_active = CAST(:active AS boolean), featured = CAST(:featured AS boolean),
                         updated_at = NOW()
                     WHERE id = :id',
                );
                $stmt->execute([
                    'title' => $title,
                    'url' => $url,
                    'icon' => $icon,
                    'sort_order' => (int) ($_POST['sort_order'] ?? 0),
                    'active' => isset($_POST['is_active']) ? 'true' : 'false',
                    'featured' => isset($_POST['featured']) ? 'true' : 'false',
                    'id' => $id,
                ]);
                if (!$stmt->rowCount()) {
                    throw new InvalidArgumentException(
                        'Tautan tidak ditemukan.',
                    );
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
            $stmt = $db->prepare('DELETE FROM links WHERE id=:id');
            $stmt->execute(['id' => $id]);
            $_SESSION['notice'] = 'Tautan berhasil dihapus.';
        } elseif ($action === 'demo') {
            $db->beginTransaction();
            $db->exec('DELETE FROM analytics_events WHERE is_demo = true');
            $all = $db
                ->query('SELECT id,key,title FROM links ORDER BY sort_order,id')
                ->fetchAll();
            $stmt = $db->prepare(
                "INSERT INTO analytics_events
                 (event_type, link_id, link_key, target_label, visitor_id, path, device, browser, os, is_demo, created_at)
                 VALUES (:event, :id, :key, :label, :visitor, '/', :device, :browser, :os, true, :created)",
            );
            $count = 0;
            for ($day = 29; $day >= 0 && count($all) > 0; $day--) {
                for ($i = 0; $i < 14 + (($day * 7) % 21); $i++) {
                    $date =
                        gmdate('Y-m-d', strtotime("-$day days")) .
                        sprintf(
                            ' %02d:%02d:00+00',
                            8 + (($i * 3) % 12),
                            ($i * 17) % 60,
                        );
                    $data = [
                        'event' => 'view',
                        'id' => null,
                        'key' => null,
                        'label' => 'Halaman publik',
                        'visitor' => "demo-$day-$i",
                        'device' => $i % 3 ? 'Mobile' : 'Desktop',
                        'browser' => $i % 4 ? 'Chrome' : 'Safari',
                        'os' => $i % 3 ? 'Android' : 'Windows',
                        'created' => $date,
                    ];
                    $stmt->execute($data);
                    $count++;
                    if ($i % 3 === 0) {
                        $link = $all[($day + $i) % count($all)];
                        $data['event'] = 'link_click';
                        $data['id'] = $link['id'];
                        $data['key'] = $link['key'];
                        $data['label'] = $link['title'];
                        $stmt->execute($data);
                        $count++;
                    }
                }
            }
            $db->commit();
            $_SESSION['notice'] = "$count event data contoh berhasil dimuat.";
        } else {
            throw new InvalidArgumentException('Tindakan tidak dikenal.');
        }
    } catch (Throwable $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
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
