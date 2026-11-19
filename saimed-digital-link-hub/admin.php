<?php
$tab = in_array($_GET['tab'] ?? 'overview', ['overview','links','activity'], true) ? ($_GET['tab'] ?? 'overview') : 'overview';
$loggedIn = !empty($_SESSION['admin']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'login') {
        $password = getenv('ADMIN_PASSWORD');
        $attempts = $_SESSION['login_attempts'] ?? [];
        $attempts = array_values(array_filter($attempts, fn($time) => $time > time() - 900));
        if (count($attempts) >= 10) { $_SESSION['notice'] = 'Terlalu banyak percobaan. Coba lagi dalam 15 menit.'; }
        elseif (is_string($password) && $password !== '' && hash_equals($password, (string)($_POST['password'] ?? ''))) {
            session_regenerate_id(true); $_SESSION['admin'] = true; $_SESSION['login_attempts'] = []; $_SESSION['csrf'] = bin2hex(random_bytes(32));
        } else { $attempts[] = time(); $_SESSION['login_attempts'] = $attempts; $_SESSION['notice'] = 'Kata sandi administrator tidak sesuai atau belum dikonfigurasi.'; }
        redirect_admin();
    }
    if (!$loggedIn) { http_response_code(403); exit('Akses ditolak'); }
    if ($action === 'logout') { $_SESSION = []; session_destroy(); redirect_admin(); }
    try {
        $db = db();
        if ($action === 'save' || $action === 'add') {
            $title = substr(trim((string)($_POST['title'] ?? '')), 0, 80);
            $url = safe_url((string)($_POST['url'] ?? ''));
            $icon = in_array($_POST['icon'] ?? '', ICONS, true) ? $_POST['icon'] : 'link';
            if (!$title || !$url) throw new InvalidArgumentException('Nama dan URL http/https atau tautan internal yang valid wajib diisi.');
            if ($action === 'add') {
                $key = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($title)), '-') ?: 'tautan';
                $stmt = $db->prepare('INSERT INTO links (key,title,url,icon,sort_order) VALUES (:key,:title,:url,:icon,(SELECT COALESCE(MAX(sort_order),0)+1 FROM links))');
                $stmt->execute(['key'=>substr($key,0,38).'-'.bin2hex(random_bytes(3)),'title'=>$title,'url'=>$url,'icon'=>$icon]);
            } else {
                $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
                if (!$id) throw new InvalidArgumentException('ID tidak valid.');
                $stmt = $db->prepare('UPDATE links SET title=:title,url=:url,icon=:icon,sort_order=:sort_order,is_active=CAST(:active AS boolean),featured=CAST(:featured AS boolean),updated_at=NOW() WHERE id=:id');
                $stmt->execute(['title'=>$title,'url'=>$url,'icon'=>$icon,'sort_order'=>(int)($_POST['sort_order'] ?? 0),'active'=>isset($_POST['is_active']) ? 'true' : 'false','featured'=>isset($_POST['featured']) ? 'true' : 'false','id'=>$id]);
                if (!$stmt->rowCount()) throw new InvalidArgumentException('Tautan tidak ditemukan.');
            }
            $_SESSION['notice'] = 'Tautan berhasil disimpan.';
        } elseif ($action === 'delete') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
            if (!$id) throw new InvalidArgumentException('ID tidak valid.');
            $stmt = $db->prepare('DELETE FROM links WHERE id=:id'); $stmt->execute(['id'=>$id]);
            $_SESSION['notice'] = 'Tautan berhasil dihapus.';
        } elseif ($action === 'demo') {
            $db->beginTransaction();
            $db->exec('DELETE FROM analytics_events WHERE is_demo = true');
            $all = $db->query('SELECT id,key,title FROM links ORDER BY sort_order,id')->fetchAll();
            $stmt = $db->prepare('INSERT INTO analytics_events (event_type,link_id,link_key,target_label,visitor_id,path,device,browser,os,is_demo,created_at) VALUES (:event,:id,:key,:label,:visitor,\'/\',:device,:browser,:os,true,:created)');
            $count = 0;
            for ($day=29; $day>=0 && count($all)>0; $day--) for ($i=0; $i<14+($day*7)%21; $i++) {
                $date = gmdate('Y-m-d', strtotime("-$day days")).sprintf(' %02d:%02d:00+00',8+($i*3)%12,($i*17)%60);
                $data = ['event'=>'view','id'=>null,'key'=>null,'label'=>'Halaman publik','visitor'=>"demo-$day-$i",'device'=>$i%3 ? 'Mobile' : 'Desktop','browser'=>$i%4 ? 'Chrome' : 'Safari','os'=>$i%3 ? 'Android' : 'Windows','created'=>$date];
                $stmt->execute($data); $count++;
                if ($i%3===0) { $link=$all[($day+$i)%count($all)]; $data['event']='link_click'; $data['id']=$link['id']; $data['key']=$link['key']; $data['label']=$link['title']; $stmt->execute($data); $count++; }
            }
            $db->commit(); $_SESSION['notice'] = "$count event data contoh berhasil dimuat.";
        } else { throw new InvalidArgumentException('Tindakan tidak dikenal.'); }
    } catch (Throwable $e) {
        if (isset($db) && $db->inTransaction()) $db->rollBack();
        error_log('SAIMED admin: '.$e->getMessage());
        $_SESSION['notice'] = $e instanceof InvalidArgumentException ? $e->getMessage() : 'Gagal menyimpan data. Periksa koneksi dan skema database.';
    }
    redirect_admin(in_array($action, ['save','add','delete'], true) ? 'links' : $tab);
}
$notice = $_SESSION['notice'] ?? ''; unset($_SESSION['notice']);
page_start('Admin Analytics | SAIMED');
if (!$loggedIn): ?>
<main class="login-shell"><div class="login-card"><div class="login-heading"><?php avatar(true); ?><div><p class="small-label">SAIMED Digital Link Hub</p><h1>Admin Analytics</h1></div></div><div class="badge">✓ Area khusus administrator</div><?php if ($notice): ?><p class="notice" role="alert"><?= h($notice) ?></p><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?= h(csrf()) ?>"><input type="hidden" name="action" value="login"><label>Kata sandi<input type="password" name="password" placeholder="Masukkan kata sandi admin" required autofocus></label><button class="button primary" type="submit">Masuk ke Dashboard</button></form><a class="back" href="./">← Kembali ke halaman publik</a></div></main><?php page_end(); return; endif;
$days = in_array((int)($_GET['days'] ?? 7), [7,14,30], true) ? (int)($_GET['days'] ?? 7) : 7;
$allLinks = links(); $events = []; $dbError = false;
try { $stmt = db()->prepare("SELECT * FROM analytics_events WHERE created_at >= :start ORDER BY created_at DESC"); $stmt->execute(['start'=>gmdate('Y-m-d 00:00:00+00', strtotime('-'.($days-1).' days'))]); $events = $stmt->fetchAll(); } catch (Throwable $e) { $dbError = true; error_log('SAIMED analytics: '.$e->getMessage()); }
$views = 0; $clicks = 0; $visitors = []; $devices = []; $browsers = []; $linkClicks = []; $daily = []; $demoCount = 0;
for ($i=$days-1; $i>=0; $i--) $daily[gmdate('Y-m-d', strtotime("-$i days"))] = ['views'=>0,'clicks'=>0];
foreach ($events as $event) {
    $date = gmdate('Y-m-d', strtotime($event['created_at'])); $isView = $event['event_type'] === 'view'; $isClick = in_array($event['event_type'], ['link_click','social_click'], true);
    if ($isView) $views++; if ($isClick) $clicks++;
    if (isset($daily[$date])) { if ($isView) $daily[$date]['views']++; if ($isClick) $daily[$date]['clicks']++; }
    if ($isView || $isClick) { $visitors[$event['visitor_id']] = true; $devices[$event['device']] = ($devices[$event['device']] ?? 0)+1; $browsers[$event['browser']] = ($browsers[$event['browser']] ?? 0)+1; }
    if ($event['event_type'] === 'link_click' && $event['link_key']) $linkClicks[$event['link_key']] = ($linkClicks[$event['link_key']] ?? 0)+1;
    if (boolval_db($event['is_demo'])) $demoCount++;
}
arsort($devices); arsort($browsers);
function token_input(): void { echo '<input type="hidden" name="csrf" value="'.h(csrf()).'">'; }
function icon_options(string $selected = 'link'): void { foreach (ICONS as $i) echo '<option value="'.h($i).'"'.($i === $selected ? ' selected' : '').'>'.h(ucfirst($i)).'</option>'; }
?>
<div class="dashboard"><header class="dash-header"><div class="dash-brand"><?php avatar(true); ?><div><p class="small-label">SAIMED DIGITAL LINK HUB</p><h1>Admin Analytics</h1></div></div><div class="dash-actions"><a href="./" target="_blank" rel="noopener">Lihat halaman ↗</a><form method="post"><?php token_input(); ?><input type="hidden" name="action" value="logout"><button class="button">Keluar</button></form></div></header><main class="dash-main"><div class="dash-title"><div><p class="small-label">DASHBOARD</p><h2>Selamat datang kembali 👋</h2><p>Pantau performa dan kelola tautan digital SAIMED.</p></div><form method="get"><input type="hidden" name="page" value="admin"><input type="hidden" name="tab" value="<?= h($tab) ?>"><label>Periode <select name="days" onchange="this.form.submit()"><?php foreach ([7,14,30] as $d): ?><option value="<?= $d ?>" <?= $d===$days?'selected':'' ?>><?= $d ?> hari terakhir</option><?php endforeach; ?></select></label><noscript><button>Ubah</button></noscript></form></div>
<nav class="tabs" aria-label="Navigasi dashboard"><?php foreach (['overview'=>'Ringkasan','links'=>'Kelola Tautan','activity'=>'Aktivitas'] as $key=>$label): ?><a class="<?= $tab===$key?'active':'' ?>" href="?page=admin&amp;tab=<?= h($key) ?>&amp;days=<?= $days ?>"><?= h($label) ?></a><?php endforeach; ?></nav>
<?php if ($notice): ?><p class="notice" role="status"><?= h($notice) ?></p><?php endif; if ($dbError): ?><p class="notice error">Database tidak tersedia. Konfigurasikan DATABASE_URL dan jalankan schema.sql.</p><?php endif; ?>
<?php if ($tab === 'overview'): ?>
<div class="kpis"><?php foreach (['Total Kunjungan'=>$views,'Total Klik'=>$clicks,'Pengunjung Unik'=>count($visitors),'Click-through Rate'=>($views ? round($clicks/$views*100,1) : 0).'%'] as $label=>$value): ?><div class="panel kpi"><span><?= h($label) ?></span><strong><?= h($value) ?></strong><small><?= $days ?> hari terakhir</small></div><?php endforeach; ?></div>
<div class="dash-grid"><section class="panel"><h3>Aktivitas Harian</h3><p>Kunjungan dan klik selama <?= $days ?> hari terakhir</p><div class="daily-list"><?php foreach ($daily as $date=>$point): ?><div><span><?= h(date('d M', strtotime($date))) ?></span><span><?= $point['views'] ?> kunjungan</span><span><?= $point['clicks'] ?> klik</span></div><?php endforeach; ?></div></section><section class="panel"><h3>Perangkat Pengunjung</h3><?php if (!$devices): ?><p>Belum ada data.</p><?php endif; foreach ($devices as $name=>$count): ?><div class="stat-row"><span><?= h($name) ?></span><strong><?= $count ?></strong></div><?php endforeach; ?><h3>Browser teratas</h3><?php foreach ($browsers as $name=>$count): ?><span class="badge"><?= h($name) ?> · <?= $count ?></span> <?php endforeach; ?></section></div>
<div class="dash-grid"><section class="panel"><h3>Performa Tautan</h3><?php foreach ($allLinks as $link): ?><div class="stat-row"><?= icon($link['icon']) ?><span><?= h($link['title']) ?></span><strong><?= $linkClicks[$link['key']] ?? 0 ?> klik</strong></div><?php endforeach; ?></section><section class="panel"><h3>Aktivitas Terbaru</h3><?php foreach (array_slice($events,0,6) as $event): ?><div class="stat-row"><span><?= h($event['target_label'] ?? 'Halaman publik') ?><small><?= h($event['device']) ?> · <?= h($event['event_type']) ?></small></span><small><?= h(date('d M H:i', strtotime($event['created_at']))) ?> UTC</small></div><?php endforeach; if (!$events): ?><p>Belum ada aktivitas.</p><?php endif; ?></section></div>
<?php elseif ($tab === 'links'): ?>
<section class="panel"><h3>Tambah Tautan Baru</h3><form method="post" class="add-form"><?php token_input(); ?><input type="hidden" name="action" value="add"><input name="title" placeholder="Nama tautan" maxlength="80" required><input name="url" placeholder="https://contoh.com" required><select name="icon"><?php icon_options(); ?></select><button class="button primary">Tambah</button></form></section><section class="panel"><h3>Tautan Publik</h3><?php foreach ($allLinks as $link): ?><div class="editor"><form method="post" class="edit-form"><?php token_input(); ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= h($link['id']) ?>"><label>Nama<input name="title" value="<?= h($link['title']) ?>" maxlength="80" required></label><label>URL<input name="url" value="<?= h($link['url']) ?>" required></label><label>Ikon<select name="icon"><?php icon_options($link['icon']); ?></select></label><label>Urutan<input name="sort_order" type="number" value="<?= h($link['sort_order']) ?>"></label><label class="check"><input type="checkbox" name="is_active" <?= boolval_db($link['is_active'])?'checked':'' ?>> Aktif</label><label class="check"><input type="checkbox" name="featured" <?= boolval_db($link['featured'])?'checked':'' ?>> Unggulan</label><button class="button primary">Simpan</button></form><form method="post" onsubmit="return confirm('Hapus tautan ini?')"><?php token_input(); ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= h($link['id']) ?>"><button class="button danger">Hapus</button></form></div><?php endforeach; ?></section>
<?php else: ?><section class="panel"><h3>Log Aktivitas Pengunjung</h3><p>50 event terbaru dalam <?= $days ?> hari terakhir</p><div class="table-wrap"><table><thead><tr><th>Waktu (UTC)</th><th>Aktivitas</th><th>Label</th><th>Perangkat</th><th>Pengunjung</th></tr></thead><tbody><?php foreach (array_slice($events,0,50) as $event): ?><tr><td><?= h(date('d M Y H:i',strtotime($event['created_at']))) ?></td><td><?= h($event['event_type']) ?><?= boolval_db($event['is_demo']) ? ' (contoh)' : '' ?></td><td><?= h($event['target_label'] ?? 'Halaman publik') ?></td><td><?= h($event['device']) ?></td><td><?= h(substr($event['visitor_id'],0,20)) ?></td></tr><?php endforeach; ?></tbody></table><?php if (!$events): ?><p>Belum ada aktivitas.</p><?php endif; ?></div></section><?php endif; ?>
<div class="panel demo"><div><strong>Data analytics tersimpan di PostgreSQL</strong><p><?= $demoCount ? h($demoCount).' event data contoh aktif pada periode ini.' : 'Semua angka berasal dari kunjungan dan klik nyata.' ?></p></div><form method="post"><?php token_input(); ?><input type="hidden" name="action" value="demo"><button class="button"><?= $demoCount ? 'Perbarui' : 'Muat' ?> Data Contoh</button></form></div></main></div><?php page_end();
