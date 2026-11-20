<?php
$page_title = 'SAIMED Digital Link Hub | sentraalkesindonesia';
$admin_page = false;
require __DIR__ . '/../includes/header.php';
?>

<main class="public-shell">
    <div class="public-content">
        <header class="profile">
            <div class="avatar-shell"><?php avatar(); ?></div>
            <h1 class="profile-username">sentraalkesindonesia</h1>
            <p class="profile-eyebrow">SENTRA ALKES INDONESIA</p>
            <p class="profile-description">
                SAIMED – Sentra Alkes Indonesia, hadir sebagai mitra terpercaya dalam
                memenuhi kebutuhan alat kesehatan dengan mengutamakan kualitas,
                keandalan, dan pelayanan terbaik.
            </p>

            <div class="social-row" aria-label="Media sosial SAIMED">
                <a class="social-button track-social" href="<?= h(INSTAGRAM) ?>"
                   target="_blank" rel="noopener noreferrer" aria-label="Buka Instagram"
                   data-key="instagram" data-title="Instagram">
                    <?= icon('instagram') ?>
                </a>
                <a class="social-button track-social" href="<?= h(WHATSAPP) ?>"
                   target="_blank" rel="noopener noreferrer" aria-label="Buka WhatsApp"
                   data-key="whatsapp-social" data-title="WhatsApp Profil">
                    <?= icon('whatsapp') ?>
                </a>
            </div>
        </header>

        <section class="links" aria-label="Tautan resmi SAIMED">
            <?php foreach ($publicLinks as $link): ?>
                <?php $url = safe_url($link['url']) ?? '#'; ?>
                <article class="link-card<?= boolval_db($link['featured']) ? ' link-card--featured' : '' ?>"
                         data-key="<?= h($link['key']) ?>" data-title="<?= h($link['title']) ?>"
                         data-url="<?= h($url) ?>">
                    <a class="card-link" href="<?= h($url) ?>" target="_blank" rel="noopener noreferrer">
                        <span class="card-icon"><?= icon($link['icon']) ?></span>
                        <span class="card-label"><?= h($link['title']) ?></span>
                        <span class="card-arrow">↗</span>
                    </a>
                    <button type="button" class="card-menu" aria-label="Opsi untuk <?= h($link['title']) ?>"
                            aria-expanded="false">⋮</button>
                    <div class="card-dropdown" role="menu" hidden>
                        <button type="button" data-action="open">↗ &nbsp; Buka di tab baru</button>
                        <button type="button" data-action="copy">⧉ &nbsp; Salin tautan</button>
                        <button type="button" data-action="share">↗ &nbsp; Bagikan tautan</button>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    </div>

    <footer class="public-footer">
        <a href="?page=admin" class="footer-admin" aria-label="Buka halaman admin analytics">© SAIMED <?= date('Y') ?></a>
        <span>All Rights Reserved</span>
    </footer>
    <div class="public-toast" role="status"></div>
</main>
<script src="assets/public.js" defer></script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
