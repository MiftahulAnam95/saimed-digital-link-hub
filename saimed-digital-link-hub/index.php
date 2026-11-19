<?php
declare(strict_types=1);
require __DIR__.'/app.php';
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (($_GET['api'] ?? '') === 'track' || $requestPath === '/api/track') track();
if (($_GET['api'] ?? '') === 'health' || $requestPath === '/api/health') {
    try { db()->query('SELECT 1'); json_response(['ok'=>true]); }
    catch (Throwable $e) { json_response(['ok'=>false], 500); }
}
if (isset($_GET['api'])) json_response(['error'=>'Tidak ditemukan'], 404);
ini_set('session.use_strict_mode', '1');
session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
session_start();
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$page = $_GET['page'] ?? (str_ends_with((string)$path, '/admin') ? 'admin' : (str_ends_with((string)$path, '/official') ? 'official' : 'home'));
if ($page === 'admin') { require __DIR__.'/admin.php'; exit; }
if ($page === 'official') { require __DIR__.'/official.php'; exit; }
if ($page !== 'home') { http_response_code(404); exit('Halaman tidak ditemukan'); }
$publicLinks = links(true);
page_start('SAIMED Digital Link Hub | sentraalkesindonesia');
?>
<main class="public-shell"><div class="public-content"><header class="profile"><div class="avatar-shell"><?php avatar(); ?></div>
<h1 class="profile-username">sentraalkesindonesia</h1><p class="profile-eyebrow">SENTRA ALKES INDONESIA</p><p class="profile-description">SAIMED – Sentra Alkes Indonesia, hadir sebagai mitra terpercaya dalam memenuhi kebutuhan alat kesehatan dengan mengutamakan kualitas, keandalan, dan pelayanan terbaik.</p>
<div class="social-row" aria-label="Media sosial SAIMED"><a class="social-button track-social" href="<?= h(INSTAGRAM) ?>" target="_blank" rel="noopener noreferrer" aria-label="Buka Instagram" data-key="instagram" data-title="Instagram"><?= icon('instagram') ?></a><a class="social-button track-social" href="<?= h(WHATSAPP) ?>" target="_blank" rel="noopener noreferrer" aria-label="Buka WhatsApp" data-key="whatsapp-social" data-title="WhatsApp Profil"><?= icon('whatsapp') ?></a></div></header>
<section class="links" aria-label="Tautan resmi SAIMED"><?php foreach ($publicLinks as $link): $url = safe_url($link['url']) ?? '#'; ?>
<article class="link-card<?= boolval_db($link['featured']) ? ' link-card--featured' : '' ?>" data-key="<?= h($link['key']) ?>" data-title="<?= h($link['title']) ?>" data-url="<?= h($url) ?>"><a class="card-link" href="<?= h($url) ?>" target="_blank" rel="noopener noreferrer"><span class="card-icon"><?= icon($link['icon']) ?></span><span class="card-label"><?= h($link['title']) ?></span><span class="card-arrow">↗</span></a><button type="button" class="card-menu" aria-label="Opsi untuk <?= h($link['title']) ?>" aria-expanded="false">⋮</button><div class="card-dropdown" role="menu" hidden><button type="button" data-action="open">↗ &nbsp; Buka di tab baru</button><button type="button" data-action="copy">⧉ &nbsp; Salin tautan</button><button type="button" data-action="share">↗ &nbsp; Bagikan tautan</button></div></article>
<?php endforeach; ?></section></div><footer class="public-footer"><a href="?page=admin" class="footer-admin" aria-label="Buka halaman admin analytics">© SAIMED <?= date('Y') ?></a><span>All Rights Reserved</span></footer><div class="public-toast" role="status"></div></main><script src="assets/public.js" defer></script><?php page_end();
