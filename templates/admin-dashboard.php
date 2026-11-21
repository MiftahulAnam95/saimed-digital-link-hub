<?php
$page_title = 'Admin Analytics | SAIMED';
$admin_page = true;
require __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/dashboard-chart.php';
require_once __DIR__ . '/../includes/dashboard-data.php';
$days = dashboard_period($days)['days'];
$periodLabel = $periodLabel ?? ($days === 'all' ? 'Semua waktu' : $days . ' hari terakhir');
$chartDays = $chartDays ?? ($days === 'all' ? 30 : (int) $days);
$previousViews = $previousViews ?? 0;
$previousClicks = $previousClicks ?? 0;
if (!isset($performance)) {
    $performance = [];
    foreach ($allLinks as $link) $performance[$link['key']] = ['title' => $link['title'], 'icon' => $link['icon'], 'count' => $linkClicks[$link['key']] ?? 0];
}
$topLink = $topLink ?? null;
$chart = dashboard_chart($daily);
$adminName = trim((string) ($identity['name'] ?? '')) ?: 'Super Admin';
$sessionRemaining = max(0, (int) ($sessionRemaining ?? 0));
$profilePhotoUrl = $profilePhotoUrl ?? null;
$dashboardScript = __DIR__ . '/../assets/dashboard-session.js';
$dashboardScriptVersion = is_readable($dashboardScript) ? substr(hash_file('sha256', $dashboardScript), 0, 12) : '';
$deviceTotal = array_sum($devices);
$mobileCount = (int) ($devices['Mobile'] ?? 0);
$mobilePercent = $deviceTotal > 0 ? (int) round($mobileCount * 100 / $deviceTotal) : 0;
$browserTotal = array_sum($browsers);

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
    ['label' => 'Total Kunjungan', 'value' => $views, 'icon' => 'eye', 'trend' => $days === 'all' || $dbError ? null : dashboard_trend($views, $previousViews)],
    ['label' => 'Total Klik', 'value' => $clicks, 'icon' => 'click', 'trend' => $days === 'all' || $dbError ? null : dashboard_trend($clicks, $previousClicks)],
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
            <div class="sidebar-status">SAIMED DIGITAL</div>
            <p>Semua tautan dan aktivitas<br>di satu tempat.</p>
        </div>
    </aside>

    <div class="dash-workspace">
        <header class="dash-header">
            <div class="dash-breadcrumb">
                <span>SAIMED DIGITAL LINK HUB</span>
                <strong>Dashboard <span aria-hidden="true">/</span> <?= h($tabs[$tab]) ?></strong>
            </div>
            <div class="dash-account" aria-label="Informasi akun">
                <div class="session-info" title="Perkiraan berdasarkan aktivitas pada tab ini dan masa berlaku token SSO">
                    <span>Sisa sesi</span>
                    <strong class="session-timer" data-remaining="<?= $sessionRemaining ?>"><?= sprintf('%02d:%02d', intdiv($sessionRemaining, 60), $sessionRemaining % 60) ?></strong>
                    <a class="session-expired" href="?page=admin&amp;tab=<?= h($tab) ?>&amp;days=<?= $days ?>" hidden>Periksa sesi</a>
                </div>
                <div class="account-profile">
                    <div class="account-avatar" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><circle cx="12" cy="8" r="3.5"/><path d="M5 20a7 7 0 0 1 14 0"/></svg>
                        <?php if ($profilePhotoUrl): ?><img src="<?= h($profilePhotoUrl) ?>" alt="" referrerpolicy="no-referrer"><?php endif; ?>
                    </div>
                    <span class="account-copy"><strong title="<?= h($adminName) ?>"><?= h($adminName) ?></strong><small>Super Admin</small></span>
                </div>
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
                    <p class="dash-eyebrow">ADMIN ANALYTICS <span aria-hidden="true">/</span> <?= h(strtoupper($tabs[$tab])) ?></p>
                    <h1><?= $tab === 'overview' ? 'Selamat datang kembali' : h($tabs[$tab]) ?></h1>
                    <p><?= h($tabDescriptions[$tab]) ?></p>
                </div>
                <form method="get" class="period-form">
                    <input type="hidden" name="page" value="admin">
                    <input type="hidden" name="tab" value="<?= h($tab) ?>">
                    <label for="dashboard-period">Periode data</label>
                    <select id="dashboard-period" name="days" onchange="this.form.submit()">
                        <?php foreach (['7', '14', '30', '90', 'all'] as $period): ?>
                            <option value="<?= $period ?>" <?= $period === $days ? 'selected' : '' ?>>
                                <?= $period === 'all' ? 'Semua waktu' : $period . ' hari terakhir' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <noscript><button class="button" type="submit">Terapkan</button></noscript>
                </form>
                <div class="report-actions">
                    <a class="button" href="?page=admin&amp;tab=<?= h($tab) ?>&amp;days=<?= h($days) ?>">Perbarui</a>
                    <a class="button primary" href="?page=admin&amp;export=csv&amp;days=<?= h($days) ?>">Ekspor CSV</a>
                </div>
            </div>

        <?php if ($notice): ?>
            <p class="notice" role="status"><?= h($notice) ?></p>
        <?php endif; ?>
        <?php if ($dbError): ?>
            <p class="notice error">Database tidak tersedia. Periksa konfigurasi MySQL dan jalankan schema.sql.</p>
        <?php endif; ?>

        <?php if ($demoCount > 0): ?><p class="analytics-demo-note">Periode ini mencakup <?= h($demoCount) ?> aktivitas contoh.</p><?php endif; ?>
        <?php if ($tab === 'overview'): ?>
            <div class="overview-top">
                <div class="kpis" aria-label="Ringkasan statistik">
                    <?php foreach ($metrics as $metric): ?>
                        <div class="panel kpi">
                            <div class="kpi-top">
                                <span class="kpi-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><?= $metricIcons[$metric['icon']] ?></svg>
                                </span>
                                <span class="kpi-period"><?= $days === 'all' ? 'Semua waktu' : $days . ' hari' ?></span>
                            </div>
                            <span class="kpi-label"><?= h($metric['label']) ?></span>
                            <strong><?= h($metric['value']) ?></strong>
                            <small><?= h($metric['trend'] ?? $periodLabel) ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>

                <section class="panel chart-panel" aria-labelledby="activity-chart-title">
                    <div class="panel-heading">
                        <div>
                            <p class="panel-eyebrow">PERFORMA</p>
                            <h2 id="activity-chart-title">Aktivitas Harian</h2>
                            <p>Kunjungan dan klik selama <?= $chartDays ?> hari terakhir<?= $days === 'all' ? ' (ringkasan mencakup semua waktu)' : '' ?></p>
                        </div>
                        <span class="period-badge"><?= $chartDays ?> hari</span>
                    </div>
                    <div class="chart-legend" aria-label="Legenda grafik"><span><i class="legend-views"></i>Kunjungan</span><span><i class="legend-clicks"></i>Klik</span></div>
                    <div class="activity-chart" role="img" aria-label="Grafik kunjungan dan klik harian selama <?= $chartDays ?> hari. Rincian tersedia pada tabel di bawah grafik.">
                        <div class="chart-scale" aria-hidden="true"><?php foreach ($chart['scale'] as $tick): ?><span><?= h($tick) ?></span><?php endforeach; ?></div>
                        <div class="chart-plot">
                            <div class="chart-lines" aria-hidden="true"></div>
                            <svg viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true" focusable="false">
                                <defs><linearGradient id="activity-fill" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#58b8f5" stop-opacity=".28"/><stop offset="100%" stop-color="#58b8f5" stop-opacity=".02"/></linearGradient></defs>
                                <?php if ($chart['area'] !== ''): ?><polygon points="<?= h($chart['area']) ?>" fill="url(#activity-fill)"/><?php endif; ?>
                                <?php if ($chart['views'] !== ''): ?><polyline points="<?= h($chart['views']) ?>" fill="none" stroke="#1995ed" stroke-width="2.5" vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round"/><?php endif; ?>
                                <?php if ($chart['clicks'] !== ''): ?><polyline points="<?= h($chart['clicks']) ?>" fill="none" stroke="#62bbad" stroke-width="2" vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-dasharray="5 4"/><?php endif; ?>
                            </svg>
                            <?php if (!$chart['has_data']): ?><span class="chart-empty">Belum ada aktivitas pada rentang grafik.</span><?php endif; ?>
                        </div>
                    </div>
                    <div class="chart-labels" aria-hidden="true"><?php foreach ($chart['labels'] as $dateLabel): ?><span style="left: <?= h($dateLabel['position']) ?>%"><?= h($dateLabel['text']) ?></span><?php endforeach; ?></div>
                    <details class="chart-details">
                        <summary>Lihat rincian harian</summary>
                        <div class="table-wrap"><table><thead><tr><th>Tanggal (UTC)</th><th>Kunjungan</th><th>Klik</th></tr></thead><tbody>
                            <?php foreach ($daily as $date => $point): ?><tr><td><?= h(date('d M Y', strtotime($date))) ?></td><td><?= h($point['views']) ?></td><td><?= h($point['clicks']) ?></td></tr><?php endforeach; ?>
                        </tbody></table></div>
                    </details>
                </section>
            </div>

            <div class="overview-bottom">
                <section class="panel device-panel">
                    <p class="panel-eyebrow">AUDIENS</p>
                    <h2>Perangkat Pengunjung</h2>
                    <p>Berdasarkan interaksi yang tercatat</p>
                    <div class="device-ring" style="--device-percent: <?= $mobilePercent ?>%;" role="img" aria-label="<?= $deviceTotal ? $mobilePercent . '% interaksi menggunakan perangkat mobile' : 'Belum ada data perangkat' ?>">
                        <div><strong><?= $deviceTotal ? $mobilePercent . '%' : '—' ?></strong><span>Mobile</span></div>
                    </div>
                    <?php if (!$devices): ?><p class="empty-message">Belum ada data perangkat.</p><?php endif; ?>
                    <div class="device-list">
                        <?php foreach ($devices as $name => $count): ?>
                            <div><span><i class="<?= $name === 'Mobile' ? 'dot-mobile' : 'dot-other' ?>"></i><?= h($name) ?></span><strong><?= h($count) ?></strong></div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="panel performance-panel">
                    <p class="panel-eyebrow">TAUTAN</p>
                    <h2>Performa Tautan</h2>
                    <p>Jumlah dan kontribusi dari seluruh klik</p>
                    <?php foreach ($performance as $item): ?>
                        <?php $contribution = $clicks > 0 ? round($item['count'] * 100 / $clicks, 1) : 0; ?>
                        <div class="performance-row">
                            <div class="performance-row-top">
                                <?= icon($item['icon']) ?>
                                <span class="performance-name"><?= h($item['title']) ?></span>
                                <strong><?= h($item['count']) ?> <small>klik</small></strong>
                            </div>
                            <div class="performance-detail"><span><?= h(number_format($contribution, 1, ',', '.')) ?>% kontribusi</span><div class="browser-track" aria-hidden="true"><span style="width: <?= h($contribution) ?>%"></span></div></div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$performance): ?><p class="empty-message">Belum ada tautan.</p><?php endif; ?>
                    <a class="panel-link" href="?page=admin&amp;tab=links&amp;days=<?= $days ?>">Kelola tautan <span aria-hidden="true">→</span></a>
                </section>

                <div class="overview-secondary">
                    <section class="panel recent-panel">
                        <div class="panel-heading"><div><p class="panel-eyebrow">AKTIVITAS</p><h2>Aktivitas Terbaru</h2></div><a class="panel-link" href="?page=admin&amp;tab=activity&amp;days=<?= $days ?>">Lihat semua <span aria-hidden="true">→</span></a></div>
                        <?php foreach (array_slice($events, 0, 4) as $event): ?>
                            <div class="stat-row"><span><?= h($event['target_label'] ?? 'Halaman publik') ?><small><?= h($event['device']) ?> · <?= h($event['event_type']) ?></small></span><small><?= h(date('d M H:i', strtotime($event['created_at']))) ?> UTC</small></div>
                        <?php endforeach; ?>
                        <?php if (!$events): ?><p class="empty-message">Belum ada aktivitas.</p><?php endif; ?>
                    </section>
                    <section class="panel insight-panel">
                        <p class="panel-eyebrow">INSIGHT</p>
                        <?php if ($topLink): ?>
                            <h2><?= h($topLink['title']) ?> paling diminati</h2>
                            <p>Tautan ini memperoleh <?= h($topLink['count']) ?> klik pada periode yang dipilih.</p>
                        <?php else: ?>
                            <h2>Kenali minat pengunjung</h2>
                            <p>Insight akan muncul setelah ada klik yang tercatat.</p>
                        <?php endif; ?>
                    </section>
                    <section class="panel browser-panel">
                        <p class="panel-eyebrow">AUDIENS</p>
                        <h2>Browser Teratas</h2>
                        <?php foreach (array_slice($browsers, 0, 4, true) as $name => $count): ?>
                            <?php $browserPercent = $browserTotal > 0 ? (int) round($count * 100 / $browserTotal) : 0; ?>
                            <div class="browser-row"><div><span><?= h($name) ?></span><strong><?= h($count) ?></strong></div><div class="browser-track" aria-hidden="true"><span style="width: <?= $browserPercent ?>%"></span></div></div>
                        <?php endforeach; ?>
                        <?php if (!$browsers): ?><p class="empty-message">Belum ada data browser.</p><?php endif; ?>
                    </section>
                </div>
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
                <p>50 event terbaru · <?= h($periodLabel) ?></p>
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

        <footer class="admin-footer"><span>© SAIMED <?= date('Y') ?></span><span>All Rights Reserved</span></footer>
        </main>
    </div>
</div>

<script src="assets/dashboard-session.js?v=<?= h($dashboardScriptVersion) ?>" defer></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
