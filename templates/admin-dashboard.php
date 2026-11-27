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
$dashboardScript = __DIR__ . '/../assets/js/dashboard-session.js';
$dashboardScriptVersion = is_readable($dashboardScript) ? substr(hash_file('sha256', $dashboardScript), 0, 12) : '';
$chartScript = __DIR__ . '/../assets/js/dashboard-chart.js';
$chartScriptVersion = is_readable($chartScript) ? substr(hash_file('sha256', $chartScript), 0, 12) : '';
$linksScript = __DIR__ . '/../assets/js/dashboard-links.js';
$linksScriptVersion = is_readable($linksScript) ? substr(hash_file('sha256', $linksScript), 0, 12) : '';
$activityScript = __DIR__ . '/../assets/js/dashboard-activity.js';
$activityScriptVersion = is_readable($activityScript) ? substr(hash_file('sha256', $activityScript), 0, 12) : '';
$deviceTotal = array_sum($devices);
$mobileCount = (int) ($devices['Mobile'] ?? 0);
$mobilePercent = $deviceTotal > 0 ? (int) round($mobileCount * 100 / $deviceTotal) : 0;
$desktopCount = (int) ($devices['Desktop'] ?? 0);
$tabletCount = (int) ($devices['Tablet'] ?? 0);
$mobileEnd = $deviceTotal > 0 ? number_format($mobileCount * 100 / $deviceTotal, 4, '.', '') : '0';
$desktopEnd = $deviceTotal > 0 ? number_format(($mobileCount + $desktopCount) * 100 / $deviceTotal, 4, '.', '') : '0';
$tabletEnd = $deviceTotal > 0 ? number_format(($mobileCount + $desktopCount + $tabletCount) * 100 / $deviceTotal, 4, '.', '') : '0';
$deviceClasses = ['Mobile' => 'dot-mobile', 'Desktop' => 'dot-desktop', 'Tablet' => 'dot-tablet'];
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
                <div class="dash-controls">
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
                        <a class="button" href="?page=admin&amp;export=csv&amp;days=<?= h($days) ?>">Ekspor CSV</a>
                        <a class="button primary" href="?page=admin&amp;export=xlsx&amp;days=<?= h($days) ?>" title="Lebar kolom sudah diatur untuk Excel">Ekspor Excel</a>
                    </div>
                </div>
            </div>

        <?php if ($notice): ?>
            <p class="notice" role="status" id="dashboard-notice" data-notice-type="<?= h(in_array($noticeType ?? '', ['success', 'error'], true) ? $noticeType : '') ?>"><?= h($notice) ?></p>
        <?php endif; ?>
        <?php if ($dbError): ?>
            <p class="notice error">Database tidak tersedia. Periksa konfigurasi MySQL dan jalankan schema.sql.</p>
        <?php endif; ?>

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
                    <p class="chart-help">Arahkan kursor atau sentuh grafik untuk melihat rincian (WIB). Untuk laporan dengan lebar kolom yang sudah diatur, pilih Ekspor Excel; CSV hanya menyimpan data.</p>
                    <div class="activity-chart" role="group" aria-label="Grafik kunjungan dan klik harian selama <?= $chartDays ?> hari berdasarkan WIB. Rincian tersedia pada tabel di bawah grafik.">
                        <div class="chart-scale" aria-hidden="true"><?php foreach ($chart['scale'] as $tick): ?><span><?= h($tick) ?></span><?php endforeach; ?></div>
                        <div class="chart-plot"<?php if ($chart['points']): ?> role="slider" tabindex="0" aria-label="Data aktivitas harian dalam WIB. Gunakan tombol panah untuk memilih tanggal" aria-valuemin="1" aria-valuemax="<?= count($chart['points']) ?>" aria-valuenow="1" aria-valuetext="<?= h($chart['points'][0]['date']) ?> WIB: <?= h($chart['points'][0]['views']) ?> kunjungan, <?= h($chart['points'][0]['clicks']) ?> klik" data-points="<?= h(json_encode($chart['points'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE)) ?>"<?php endif; ?>>
                            <div class="chart-lines" aria-hidden="true"></div>
                            <svg viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true" focusable="false">
                                <defs><linearGradient id="activity-fill" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#58b8f5" stop-opacity=".28"/><stop offset="100%" stop-color="#58b8f5" stop-opacity=".02"/></linearGradient></defs>
                                <?php if ($chart['area_path'] !== ''): ?><path d="<?= h($chart['area_path']) ?>" fill="url(#activity-fill)"/><?php endif; ?>
                                <?php if ($chart['view_path'] !== ''): ?><path d="<?= h($chart['view_path']) ?>" fill="none" stroke="#1995ed" stroke-width="2.5" vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round"/><?php endif; ?>
                                <?php if ($chart['click_path'] !== ''): ?><path d="<?= h($chart['click_path']) ?>" fill="none" stroke="#62bbad" stroke-width="2" vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-dasharray="5 4"/><?php endif; ?>
                            </svg>
                            <?php if (!$chart['has_data']): ?><span class="chart-empty">Belum ada aktivitas pada rentang grafik.</span><?php endif; ?>
                            <?php if ($chart['points']): ?>
                                <div class="chart-crosshair" hidden aria-hidden="true"></div>
                                <div class="chart-point chart-point-views" hidden aria-hidden="true"></div>
                                <div class="chart-point chart-point-clicks" hidden aria-hidden="true"></div>
                                <div class="chart-tooltip" hidden aria-hidden="true"><strong class="chart-tooltip-date"></strong><span><i class="legend-views"></i>Kunjungan <b class="chart-tooltip-views"></b></span><span><i class="legend-clicks"></i>Klik <b class="chart-tooltip-clicks"></b></span></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="chart-labels" aria-hidden="true"><?php foreach ($chart['labels'] as $dateLabel): ?><span style="left: <?= h($dateLabel['position']) ?>%"><?= h($dateLabel['text']) ?></span><?php endforeach; ?></div>
                    <details class="chart-details">
                        <summary>Lihat rincian harian</summary>
                        <div class="table-wrap"><table><thead><tr><th>Tanggal (WIB)</th><th>Kunjungan</th><th>Klik</th></tr></thead><tbody>
                            <?php foreach ($daily as $date => $point): ?><tr><td><?= h(gmdate('d M Y', strtotime($date . ' UTC'))) ?></td><td><?= h($point['views']) ?></td><td><?= h($point['clicks']) ?></td></tr><?php endforeach; ?>
                        </tbody></table></div>
                    </details>
                </section>
            </div>

            <div class="overview-bottom">
                <section class="panel device-panel">
                    <p class="panel-eyebrow">AUDIENS</p>
                    <h2>Perangkat Pengunjung</h2>
                    <p>Berdasarkan interaksi yang tercatat</p>
                    <div class="device-ring<?= $deviceTotal ? '' : ' device-ring--empty' ?>" style="--mobile-end: <?= h($mobileEnd) ?>%; --desktop-end: <?= h($desktopEnd) ?>%; --tablet-end: <?= h($tabletEnd) ?>%;" role="img" aria-label="<?= $deviceTotal ? h($mobilePercent . '% interaksi menggunakan perangkat mobile') : 'Belum ada data perangkat' ?>">
                        <div><strong><?= $deviceTotal ? $mobilePercent . '%' : '—' ?></strong><span>Mobile</span></div>
                    </div>
                    <?php if (!$devices): ?><p class="empty-message">Belum ada data perangkat.</p><?php endif; ?>
                    <div class="device-list">
                        <?php foreach ($devices as $name => $count): ?>
                            <div><span><i class="<?= h($deviceClasses[$name] ?? 'dot-other') ?>" aria-hidden="true"></i><?= h($name) ?></span><strong><?= h($count) ?></strong></div>
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
                            <div class="stat-row"><span><?= h($event['target_label'] ?? 'Halaman publik') ?><small><?= h($event['device']) ?> · <?= h($event['event_type']) ?></small></span><small><?= h(dashboard_wib($event['created_at'], 'd M H:i')) ?> WIB</small></div>
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
            <section class="panel links-panel">
                <div class="links-heading">
                    <div>
                        <p class="links-eyebrow">DAFTAR TAUTAN</p>
                        <h2>Tautan Publik</h2>
                        <p>Kelola tautan yang tampil di halaman publik SAIMED.</p>
                    </div>
                    <button class="button primary add-link-trigger" type="button" id="open-add-link" aria-haspopup="dialog" aria-controls="add-link-dialog" hidden>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg> Tambah Tautan
                    </button>
                </div>

                <dialog class="add-link-dialog" id="add-link-dialog" aria-labelledby="add-link-title" aria-describedby="add-link-description">
                    <div class="add-link-dialog-heading">
                        <div>
                            <p class="add-link-eyebrow">KELOLA TAUTAN</p>
                            <h2 id="add-link-title">Tambah Tautan Baru</h2>
                            <p id="add-link-description">Tambahkan tautan untuk ditampilkan di halaman publik.</p>
                        </div>
                        <button class="add-link-close" type="button" aria-label="Tutup dialog">&times;</button>
                    </div>
                    <form method="post" enctype="multipart/form-data" class="add-link-form">
                        <?php token_input(); ?>
                        <input type="hidden" name="action" value="add">
                        <label for="new-link-title">Nama</label>
                        <input id="new-link-title" name="title" placeholder="Contoh: Toko Shopee" maxlength="80" required autofocus>
                        <label for="new-link-url">URL</label>
                        <input id="new-link-url" name="url" placeholder="https://contoh.com" required>
                        <label for="new-link-icon">Logo/Icon</label>
                        <input id="new-link-icon" name="icon_file" type="file" accept="image/png,image/jpeg" required>
                        <p class="add-link-hint">PNG atau JPG, maksimal 2 MB. Logo ditampilkan dengan ukuran seragam.</p>
                        <div class="add-link-actions">
                            <button class="button add-link-cancel" type="button">Batal</button>
                            <button class="button primary" type="submit">Tambah Tautan</button>
                        </div>
                    </form>
                </dialog>
                <noscript>
                    <details class="add-link-fallback">
                        <summary>Tambah Tautan Baru</summary>
                        <form method="post" enctype="multipart/form-data" class="add-link-form">
                            <?php token_input(); ?>
                            <input type="hidden" name="action" value="add">
                            <label for="fallback-link-title">Nama</label>
                            <input id="fallback-link-title" name="title" placeholder="Contoh: Toko Shopee" maxlength="80" required>
                            <label for="fallback-link-url">URL</label>
                            <input id="fallback-link-url" name="url" placeholder="https://contoh.com" required>
                            <label for="fallback-link-icon">Logo/Icon</label>
                            <input id="fallback-link-icon" name="icon_file" type="file" accept="image/png,image/jpeg" required>
                            <p class="add-link-hint">PNG atau JPG, maksimal 2 MB.</p>
                            <div class="add-link-actions"><button class="button primary" type="submit">Tambah Tautan</button></div>
                        </form>
                    </details>
                </noscript>
                <?php $orderVersion = link_order_version($allLinks); ?>
                <p class="add-link-hint">Urutan menghitung semua tautan, termasuk yang nonaktif. Di halaman publik, hanya tautan aktif yang ditampilkan sesuai urutan ini.</p>
                <p class="links-scroll-hint">Geser tabel ke samping untuk melihat semua kolom.</p>
                <div class="links-table-wrap" tabindex="0" role="region" aria-label="Tabel tautan publik; geser ke samping untuk melihat seluruh kolom">
                    <table id="links-table" class="display links-table">
                        <thead><tr><th>Nama Tautan</th><th>URL Tujuan</th><th>Urutan</th><th>Status</th><th>Unggulan</th><th>Aksi</th></tr></thead>
                        <tbody>
                        <?php foreach ($allLinks as $linkIndex => $link): ?>
                            <?php $displayPosition = $linkIndex + 1; ?>
                            <tr>
                                <td><span class="links-name"><?= icon($link['icon']) ?><strong><?= h($link['title']) ?></strong></span></td>
                                <td><span class="links-url" title="<?= h($link['url']) ?>"><?= h($link['url']) ?></span></td>
                                <td><?= h($displayPosition) ?></td>
                                <td><span class="link-status <?= boolval_db($link['is_active']) ? 'is-active' : 'is-inactive' ?>"><?= boolval_db($link['is_active']) ? 'Aktif' : 'Nonaktif' ?></span></td>
                                <td><span class="link-featured <?= boolval_db($link['featured']) ? 'is-featured' : '' ?>"><?= boolval_db($link['featured']) ? 'Ya' : 'Tidak' ?></span></td>
                                <td>
                                <?php if ((int) $link['id'] > 0): ?>
                                    <div class="link-row-actions">
                                        <button type="button" class="button edit-link-trigger" aria-haspopup="dialog" aria-controls="edit-link-dialog" hidden
                                            data-id="<?= h($link['id']) ?>" data-title="<?= h($link['title']) ?>" data-url="<?= h($link['url']) ?>"
                                            data-order="<?= h($displayPosition) ?>" data-active="<?= boolval_db($link['is_active']) ? '1' : '0' ?>" data-featured="<?= boolval_db($link['featured']) ? '1' : '0' ?>" aria-label="Edit <?= h($link['title']) ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg> Edit</button>
                                        <form method="post" class="delete-link-form" onsubmit="return confirm('Hapus tautan ini?')">
                                            <?php token_input(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= h($link['id']) ?>">
                                            <button class="button danger" type="submit" aria-label="Hapus <?= h($link['title']) ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6m4-6v6M5 7l1 13h12l1-13M9 7V4h6v3"/></svg> Hapus</button>
                                        </form>
                                    </div>
                                    <noscript>
                                        <details class="edit-link-fallback"><summary>Edit</summary>
                                            <form method="post" enctype="multipart/form-data" class="add-link-form">
                                                <?php token_input(); ?>
                                                <input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= h($link['id']) ?>"><input type="hidden" name="order_version" value="<?= h($orderVersion) ?>">
                                                <label>Nama<input name="title" value="<?= h($link['title']) ?>" maxlength="80" required></label>
                                                <label>URL<input name="url" value="<?= h($link['url']) ?>" required></label>
                                                <label>Logo/Icon (opsional: ganti)<input name="icon_file" type="file" accept="image/png,image/jpeg"></label>
                                                <label>Urutan
                                                    <select name="sort_order" required>
                                                        <?php foreach ($allLinks as $optionIndex => $optionLink): ?>
                                                            <option value="<?= $optionIndex + 1 ?>" <?= $optionIndex === $linkIndex ? 'selected' : '' ?>>Posisi <?= $optionIndex + 1 ?> · <?= $optionIndex === $linkIndex ? 'posisi saat ini' : 'saat ini: ' . h($optionLink['title']) ?><?= !boolval_db($optionLink['is_active']) ? ' (nonaktif)' : '' ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </label>
                                                <p class="add-link-hint">Posisi 1 paling atas. Tautan lain bergeser; tautan nonaktif ikut dihitung.</p>
                                                <label class="check"><input type="checkbox" name="is_active" <?= boolval_db($link['is_active']) ? 'checked' : '' ?>> Aktif</label>
                                                <label class="check"><input type="checkbox" name="featured" <?= boolval_db($link['featured']) ? 'checked' : '' ?>> Unggulan</label>
                                                <button class="button primary" type="submit">Simpan</button>
                                            </form>
                                        </details>
                                    </noscript>
                                <?php else: ?>
                                    <span class="links-unavailable">Tidak tersedia</span>
                                <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <dialog class="add-link-dialog" id="edit-link-dialog" aria-labelledby="edit-link-title" aria-describedby="edit-link-description">
                    <div class="add-link-dialog-heading">
                        <div><p class="add-link-eyebrow">KELOLA TAUTAN</p><h2 id="edit-link-title">Edit Tautan</h2><p id="edit-link-description">Perbarui informasi tautan publik.</p></div>
                        <button class="edit-link-close add-link-close" type="button" aria-label="Tutup dialog">&times;</button>
                    </div>
                    <form method="post" enctype="multipart/form-data" class="add-link-form" id="edit-link-form">
                        <?php token_input(); ?>
                        <input type="hidden" name="action" value="save"><input type="hidden" name="id"><input type="hidden" name="order_version" value="<?= h($orderVersion) ?>">
                        <label for="edit-link-name">Nama</label><input id="edit-link-name" name="title" maxlength="80" required>
                        <label for="edit-link-url">URL</label><input id="edit-link-url" name="url" required>
                        <label for="edit-link-icon">Logo/Icon (opsional: ganti)</label>
                        <span class="edit-link-icon"><span id="edit-current-icon"></span><span>Logo saat ini</span></span>
                        <input id="edit-link-icon" name="icon_file" type="file" accept="image/png,image/jpeg">
                        <p class="add-link-hint">Kosongkan untuk mempertahankan logo. PNG atau JPG, maksimal 2 MB.</p>
                        <label for="edit-link-order">Urutan tampilan</label>
                        <select id="edit-link-order" name="sort_order" aria-describedby="edit-order-hint" required>
                            <?php foreach ($allLinks as $optionIndex => $optionLink): ?>
                                <option value="<?= $optionIndex + 1 ?>" data-link-id="<?= h($optionLink['id']) ?>" data-title="<?= h($optionLink['title']) ?>" data-active="<?= boolval_db($optionLink['is_active']) ? '1' : '0' ?>">Posisi <?= $optionIndex + 1 ?> · saat ini: <?= h($optionLink['title']) ?><?= !boolval_db($optionLink['is_active']) ? ' (nonaktif)' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="add-link-hint" id="edit-order-hint">Posisi 1 paling atas. Tautan lain otomatis bergeser; tautan nonaktif ikut dihitung.</p>
                        <div class="edit-link-checks">
                            <label><input type="checkbox" name="is_active"> Aktif</label>
                            <label><input type="checkbox" name="featured"> Unggulan</label>
                        </div>
                        <div class="add-link-actions"><button class="button edit-link-cancel" type="button">Batal</button><button class="button primary" type="submit">Simpan Perubahan</button></div>
                    </form>
                </dialog>
            </section>

        <?php else: ?>
            <section class="panel activity-panel">
                <div class="activity-heading">
                    <p class="activity-eyebrow">AKTIVITAS PENGUNJUNG</p>
                    <h2>Log Aktivitas Pengunjung</h2>
                    <p>50 event terbaru · <?= h($periodLabel) ?></p>
                </div>
                <p class="activity-scroll-hint">Geser tabel ke samping untuk melihat semua kolom.</p>
                <div class="activity-table-wrap" tabindex="0" role="region" aria-label="Tabel log aktivitas pengunjung; geser ke samping untuk melihat seluruh kolom">
                    <table id="activity-table" class="display activity-table">
                        <thead>
                            <tr><th>Waktu (WIB)</th><th>Aktivitas</th><th>Label</th><th>Perangkat</th><th>Pengunjung</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($events, 0, 50) as $event): ?>
                                <tr>
                                    <td data-order="<?= h($event['created_at'] ?? '') ?>"><?= h(dashboard_wib($event['created_at'] ?? null, 'd M Y H:i')) ?></td>
                                    <td><?= h($event['event_type']) ?></td>
                                    <td><?= h($event['target_label'] ?? 'Halaman publik') ?></td>
                                    <td><?= h($event['device']) ?></td>
                                    <td><?= h(substr($event['visitor_id'], 0, 20)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if (!$events): ?><p class="activity-empty" id="activity-empty">Belum ada aktivitas.</p><?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

        <footer class="admin-footer"><span>© SAIMED <?= date('Y') ?></span><span>All Rights Reserved</span></footer>
        </main>
    </div>
</div>

<?php if ($tab === 'overview'): ?><script src="assets/js/dashboard-chart.js?v=<?= h($chartScriptVersion) ?>" defer></script><?php endif; ?>
<?php if (in_array($tab, ['links', 'activity'], true)): ?>
<script src="assets/vendor/jquery/jquery.min.js" defer></script>
<script src="assets/vendor/datatables/dataTables.min.js" defer></script>
<?php endif; ?>
<?php if ($tab === 'links'): ?>
<script src="assets/vendor/sweetalert2/sweetalert2.min.js" defer></script>
<script src="assets/js/dashboard-links.js?v=<?= h($linksScriptVersion) ?>" defer></script>
<?php endif; ?>
<?php if ($tab === 'activity'): ?><script src="assets/js/dashboard-activity.js?v=<?= h($activityScriptVersion) ?>" defer></script><?php endif; ?>
<script src="assets/js/dashboard-session.js?v=<?= h($dashboardScriptVersion) ?>" defer></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
