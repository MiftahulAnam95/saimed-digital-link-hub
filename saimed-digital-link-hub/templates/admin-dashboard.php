<?php
page_start('Admin Analytics | SAIMED');

$tabs = [
    'overview' => 'Ringkasan',
    'links' => 'Kelola Tautan',
    'activity' => 'Aktivitas',
];
$metrics = [
    'Total Kunjungan' => $views,
    'Total Klik' => $clicks,
    'Pengunjung Unik' => count($visitors),
    'Click-through Rate' => ($views ? round($clicks / $views * 100, 1) : 0) . '%',
];
?>

<div class="dashboard">
    <header class="dash-header">
        <div class="dash-brand">
            <?php avatar(true); ?>
            <div>
                <p class="small-label">SAIMED DIGITAL LINK HUB</p>
                <h1>Admin Analytics</h1>
            </div>
        </div>
        <div class="dash-actions">
            <a href="./" target="_blank" rel="noopener">Lihat halaman ↗</a>
            <form method="post">
                <?php token_input(); ?>
                <input type="hidden" name="action" value="logout">
                <button class="button">Keluar</button>
            </form>
        </div>
    </header>

    <main class="dash-main">
        <div class="dash-title">
            <div>
                <p class="small-label">DASHBOARD</p>
                <h2>Selamat datang kembali 👋</h2>
                <p>Pantau performa dan kelola tautan digital SAIMED.</p>
            </div>
            <form method="get">
                <input type="hidden" name="page" value="admin">
                <input type="hidden" name="tab" value="<?= h($tab) ?>">
                <label>Periode
                    <select name="days" onchange="this.form.submit()">
                        <?php foreach ([7, 14, 30] as $period): ?>
                            <option value="<?= $period ?>" <?= $period === $days ? 'selected' : '' ?>>
                                <?= $period ?> hari terakhir
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <noscript><button>Ubah</button></noscript>
            </form>
        </div>

        <nav class="tabs" aria-label="Navigasi dashboard">
            <?php foreach ($tabs as $key => $label): ?>
                <a class="<?= $tab === $key ? 'active' : '' ?>"
                   href="?page=admin&amp;tab=<?= h($key) ?>&amp;days=<?= $days ?>"><?= h($label) ?></a>
            <?php endforeach; ?>
        </nav>

        <?php if ($notice): ?>
            <p class="notice" role="status"><?= h($notice) ?></p>
        <?php endif; ?>
        <?php if ($dbError): ?>
            <p class="notice error">Database tidak tersedia. Periksa konfigurasi MySQL dan jalankan schema.sql.</p>
        <?php endif; ?>

        <?php if ($tab === 'overview'): ?>
            <div class="kpis">
                <?php foreach ($metrics as $label => $value): ?>
                    <div class="panel kpi">
                        <span><?= h($label) ?></span>
                        <strong><?= h($value) ?></strong>
                        <small><?= $days ?> hari terakhir</small>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="dash-grid">
                <section class="panel">
                    <h3>Aktivitas Harian</h3>
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
                    <h3>Perangkat Pengunjung</h3>
                    <?php if (!$devices): ?>
                        <p>Belum ada data.</p>
                    <?php endif; ?>
                    <?php foreach ($devices as $name => $count): ?>
                        <div class="stat-row"><span><?= h($name) ?></span><strong><?= $count ?></strong></div>
                    <?php endforeach; ?>
                    <h3>Browser teratas</h3>
                    <?php foreach ($browsers as $name => $count): ?>
                        <span class="badge"><?= h($name) ?> · <?= $count ?></span>
                    <?php endforeach; ?>
                </section>
            </div>

            <div class="dash-grid">
                <section class="panel">
                    <h3>Performa Tautan</h3>
                    <?php foreach ($allLinks as $link): ?>
                        <div class="stat-row">
                            <?= icon($link['icon']) ?>
                            <span><?= h($link['title']) ?></span>
                            <strong><?= $linkClicks[$link['key']] ?? 0 ?> klik</strong>
                        </div>
                    <?php endforeach; ?>
                </section>
                <section class="panel">
                    <h3>Aktivitas Terbaru</h3>
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
                <h3>Tambah Tautan Baru</h3>
                <form method="post" class="add-form">
                    <?php token_input(); ?>
                    <input type="hidden" name="action" value="add">
                    <input name="title" placeholder="Nama tautan" maxlength="80" required>
                    <input name="url" placeholder="https://contoh.com" required>
                    <select name="icon"><?php icon_options(); ?></select>
                    <button class="button primary">Tambah</button>
                </form>
            </section>

            <section class="panel">
                <h3>Tautan Publik</h3>
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
                <h3>Log Aktivitas Pengunjung</h3>
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
    </main>
</div>

<?php page_end(); ?>
