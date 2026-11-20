<?php
$page_title = 'Admin Analytics | SAIMED';
$admin_page = true;
require __DIR__ . '/../includes/header.php';

$tabs = [
    'overview' => 'Ringkasan',
    'links' => 'Kelola Tautan',
    'activity' => 'Aktivitas',
];
$tabDescriptions = [
    'overview' => 'Pantau performa dan aktivitas digital SAIMED dalam satu tempat.',
    'links' => 'Atur tautan yang tampil di halaman publik SAIMED.',
    'activity' => 'Lihat riwayat kunjungan dan interaksi pengunjung.',
];
$tabIcons = [
    'overview' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
    'links' => '<path d="M10 13a5 5 0 0 0 7.1 0l2.1-2.1a5 5 0 0 0-7.1-7.1L11 5"/><path d="M14 11a5 5 0 0 0-7.1 0l-2.1 2.1a5 5 0 0 0 7.1 7.1L13 19"/>',
    'activity' => '<path d="M3 12h4l3-7 4 14 3-7h4"/>',
];
$metrics = [
    ['label' => 'Total Kunjungan', 'value' => $views, 'icon' => 'eye'],
    ['label' => 'Total Klik', 'value' => $clicks, 'icon' => 'click'],
    ['label' => 'Pengunjung Unik', 'value' => count($visitors), 'icon' => 'users'],
    ['label' => 'Click-through Rate', 'value' => ($views ? round($clicks / $views * 100, 1) : 0) . '%', 'icon' => 'chart'],
];
$metricIcons = [
    'eye' => '<path d="M2 12s3.6-6 10-6 10 6 10 6-3.6 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/>',
    'click' => '<path d="m8 4 1 3M4 8l3 1m6-5-1 3M4 13l3-1m2-2 4 10 1.5-4.5L19 14 9 10Z"/>',
    'users' => '<circle cx="9" cy="8" r="3"/><path d="M3 19v-1a6 6 0 0 1 12 0v1M16 5a3 3 0 0 1 0 6m1 3a5 5 0 0 1 4 5"/>',
    'chart' => '<path d="M4 19h16M6 16v-4m5 4V8m5 8V5"/>',
];
?>

<div class="dashboard">
    <a class="dash-skip-link" href="#dashboard-content">Lewati ke konten utama</a>
    <aside class="dash-sidebar" aria-label="Sidebar dashboard">
        <div class="sidebar-brand">
            <?php avatar(true); ?>
            <div class="sidebar-brand-copy">
                <strong>SAIMED</strong>
                <span>Digital Link Hub</span>
            </div>
        </div>

        <div class="sidebar-section">
            <p class="sidebar-label">MENU UTAMA</p>
            <nav class="sidebar-nav" aria-label="Navigasi dashboard">
                <?php foreach ($tabs as $key => $label): ?>
                    <a class="sidebar-link <?= $tab === $key ? 'active' : '' ?>"
                       <?= $tab === $key ? 'aria-current="page"' : '' ?>
                       href="?page=admin&amp;tab=<?= h($key) ?>&amp;days=<?= $days ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $tabIcons[$key] ?></svg>
                        <span><?= h($label) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
        <div class="sidebar-bottom">
            <div class="sidebar-status"><span class="sidebar-status-dot" aria-hidden="true"></span> Admin workspace</div>
            <p>Kelola kehadiran digital<br>SAIMED dalam satu tempat.</p>
        </div>
    </aside>

    <div class="dash-workspace">
        <header class="dash-header">
            <div class="dash-breadcrumb">
                <span>SAIMED DIGITAL LINK HUB</span>
                <strong>Dashboard <span aria-hidden="true">/</span> <?= h($tabs[$tab]) ?></strong>
            </div>
            <div class="dash-actions">
                <a class="view-site" href="./" target="_blank" rel="noopener" aria-label="Lihat halaman publik (terbuka di tab baru)">
                    <span>Lihat halaman</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M13 5h6v6M19 5l-9 9"/><path d="M19 14v4a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h4"/></svg>
                </a>
                <form method="post">
                    <?php token_input(); ?>
                    <input type="hidden" name="action" value="logout">
                    <button class="button logout-button" type="submit">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 17v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v2M14 16l4-4-4-4M8 12h10"/></svg>
                        Keluar
                    </button>
                </form>
            </div>
        </header>

        <main class="dash-main" id="dashboard-content" tabindex="-1">
            <div class="dash-title">
                <div>
                    <p class="dash-eyebrow"><span></span> ADMIN ANALYTICS</p>
                    <h1><?= $tab === 'overview' ? 'Selamat datang kembali' : h($tabs[$tab]) ?></h1>
                    <p><?= h($tabDescriptions[$tab]) ?></p>
                </div>
                <form method="get" class="period-form">
                    <input type="hidden" name="page" value="admin">
                    <input type="hidden" name="tab" value="<?= h($tab) ?>">
                    <label for="dashboard-period">Periode data</label>
                    <select id="dashboard-period" name="days" onchange="this.form.submit()">
                        <?php foreach ([7, 14, 30] as $period): ?>
                            <option value="<?= $period ?>" <?= $period === $days ? 'selected' : '' ?>>
                                <?= $period ?> hari terakhir
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <noscript><button class="button" type="submit">Terapkan</button></noscript>
                </form>
            </div>

        <?php if ($notice): ?>
            <p class="notice" role="status"><?= h($notice) ?></p>
        <?php endif; ?>
        <?php if ($dbError): ?>
            <p class="notice error">Database tidak tersedia. Periksa konfigurasi MySQL dan jalankan schema.sql.</p>
        <?php endif; ?>

        <?php if ($tab === 'overview'): ?>
            <div class="kpis">
                <?php foreach ($metrics as $metric): ?>
                    <div class="panel kpi">
                        <div class="kpi-top">
                            <span class="kpi-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><?= $metricIcons[$metric['icon']] ?></svg>
                            </span>
                            <span class="kpi-period"><?= $days ?> hari</span>
                        </div>
                        <span class="kpi-label"><?= h($metric['label']) ?></span>
                        <strong><?= h($metric['value']) ?></strong>
                        <small>Dalam <?= $days ?> hari terakhir</small>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="dash-grid">
                <section class="panel">
                    <h2>Aktivitas Harian</h2>
                    <p>Kunjungan dan klik selama <?= $days ?> hari terakhir</p>
                    <div class="daily-list">
                        <?php foreach ($daily as $date => $point): ?>
                            <div>
                                <span><?= h(date('d M', strtotime($date))) ?></span>
                                <span><?= $point['views'] ?> kunjungan</span>
                                <span><?= $point['clicks'] ?> klik</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <section class="panel">
                    <h2>Perangkat Pengunjung</h2>
                    <?php if (!$devices): ?>
                        <p>Belum ada data.</p>
                    <?php endif; ?>
                    <?php foreach ($devices as $name => $count): ?>
                        <div class="stat-row"><span><?= h($name) ?></span><strong><?= $count ?></strong></div>
                    <?php endforeach; ?>
                    <h2>Browser teratas</h2>
                    <?php if (!$browsers): ?><p>Belum ada data.</p><?php endif; ?>
                    <?php foreach ($browsers as $name => $count): ?>
                        <span class="badge"><?= h($name) ?> · <?= $count ?></span>
                    <?php endforeach; ?>
                </section>
            </div>

            <div class="dash-grid">
                <section class="panel">
                    <h2>Performa Tautan</h2>
                    <?php foreach ($allLinks as $link): ?>
                        <div class="stat-row">
                            <?= icon($link['icon']) ?>
                            <span><?= h($link['title']) ?></span>
                            <strong><?= $linkClicks[$link['key']] ?? 0 ?> klik</strong>
                        </div>
                    <?php endforeach; ?>
                </section>
                <section class="panel">
                    <h2>Aktivitas Terbaru</h2>
                    <?php foreach (array_slice($events, 0, 6) as $event): ?>
                        <div class="stat-row">
                            <span>
                                <?= h($event['target_label'] ?? 'Halaman publik') ?>
                                <small><?= h($event['device']) ?> · <?= h($event['event_type']) ?></small>
                            </span>
                            <small><?= h(date('d M H:i', strtotime($event['created_at']))) ?> UTC</small>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$events): ?><p>Belum ada aktivitas.</p><?php endif; ?>
                </section>
            </div>

        <?php elseif ($tab === 'links'): ?>
            <section class="panel">
                <h2>Tambah Tautan Baru</h2>
                <form method="post" class="add-form">
                    <?php token_input(); ?>
                    <input type="hidden" name="action" value="add">
                    <input name="title" aria-label="Nama tautan" placeholder="Nama tautan" maxlength="80" required>
                    <input name="url" aria-label="URL tautan" placeholder="https://contoh.com" required>
                    <select name="icon" aria-label="Ikon tautan"><?php icon_options(); ?></select>
                    <button class="button primary">Tambah</button>
                </form>
            </section>

            <section class="panel">
                <h2>Tautan Publik</h2>
                <?php foreach ($allLinks as $link): ?>
                    <div class="editor">
                        <form method="post" class="edit-form">
                            <?php token_input(); ?>
                            <input type="hidden" name="action" value="save">
                            <input type="hidden" name="id" value="<?= h($link['id']) ?>">
                            <label>Nama
                                <input name="title" value="<?= h($link['title']) ?>" maxlength="80" required>
                            </label>
                            <label>URL
                                <input name="url" value="<?= h($link['url']) ?>" required>
                            </label>
                            <label>Ikon
                                <select name="icon"><?php icon_options($link['icon']); ?></select>
                            </label>
                            <label>Urutan
                                <input name="sort_order" type="number" value="<?= h($link['sort_order']) ?>">
                            </label>
                            <label class="check">
                                <input type="checkbox" name="is_active" <?= boolval_db($link['is_active']) ? 'checked' : '' ?>> Aktif
                            </label>
                            <label class="check">
                                <input type="checkbox" name="featured" <?= boolval_db($link['featured']) ? 'checked' : '' ?>> Unggulan
                            </label>
                            <button class="button primary">Simpan</button>
                        </form>
                        <form method="post" onsubmit="return confirm('Hapus tautan ini?')">
                            <?php token_input(); ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= h($link['id']) ?>">
                            <button class="button danger">Hapus</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </section>

        <?php else: ?>
            <section class="panel">
                <h2>Log Aktivitas Pengunjung</h2>
                <p>50 event terbaru dalam <?= $days ?> hari terakhir</p>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Waktu (UTC)</th><th>Aktivitas</th><th>Label</th><th>Perangkat</th><th>Pengunjung</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($events, 0, 50) as $event): ?>
                                <tr>
                                    <td><?= h(date('d M Y H:i', strtotime($event['created_at']))) ?></td>
                                    <td><?= h($event['event_type']) ?><?= boolval_db($event['is_demo']) ? ' (contoh)' : '' ?></td>
                                    <td><?= h($event['target_label'] ?? 'Halaman publik') ?></td>
                                    <td><?= h($event['device']) ?></td>
                                    <td><?= h(substr($event['visitor_id'], 0, 20)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if (!$events): ?><p>Belum ada aktivitas.</p><?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

        <div class="panel demo">
            <div>
                <strong>Data analytics tersimpan di MySQL</strong>
                <p><?= $demoCount ? h($demoCount) . ' event data contoh aktif pada periode ini.' : 'Semua angka berasal dari kunjungan dan klik nyata.' ?></p>
            </div>
            <form method="post">
                <?php token_input(); ?>
                <input type="hidden" name="action" value="demo">
                <button class="button"><?= $demoCount ? 'Perbarui' : 'Muat' ?> Data Contoh</button>
            </form>
        </div>
        <footer class="admin-footer"><span>© SAIMED <?= date('Y') ?></span><span>All Rights Reserved</span></footer>
        </main>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
