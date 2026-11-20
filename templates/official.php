<?php
$page_title = 'Website Resmi SAIMED | Sentra Alkes Indonesia';
$admin_page = false;
require __DIR__ . '/../includes/header.php';
?>

<main class="official">
    <a class="back" href="./">← Kembali ke Link Hub</a>
    <?php avatar(); ?>
    <p class="profile-eyebrow">SENTRA ALKES INDONESIA</p>
    <h1>Website Resmi SAIMED</h1>
    <p>
        SAIMED hadir sebagai mitra terpercaya dalam memenuhi kebutuhan alat kesehatan
        dengan mengutamakan kualitas, keandalan, dan pelayanan terbaik untuk rumah sakit,
        klinik, laboratorium, serta kebutuhan perawatan di rumah.
    </p>
    <span class="badge">✓ Kanal digital resmi SAIMED</span>

    <section class="official-card">
        <h2>Temukan kami di</h2>
        <p>Pilih kanal resmi untuk katalog dan konsultasi.</p>
        <?php foreach ($officialLinks as $link): ?>
            <a href="<?= h(safe_url($link['url']) ?? '#') ?>" target="_blank" rel="noopener noreferrer">
                <?= icon($link['icon']) ?> <?= h($link['title']) ?> <span>↗</span>
            </a>
        <?php endforeach; ?>
        <a href="<?= h(INSTAGRAM) ?>" target="_blank" rel="noopener noreferrer">
            <?= icon('instagram') ?> Instagram <span>↗</span>
        </a>
    </section>
    <p class="copyright">© SAIMED <?= date('Y') ?> · All Rights Reserved</p>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
