<?php
$page_title = 'Website dalam Pengembangan | SAIMED';
$admin_page = false;
require __DIR__ . '/../includes/header.php';
?>

<main class="maintenance">
    <div class="maintenance-shell">
        <header class="maintenance-header">
            <a class="maintenance-brand" href="./" aria-label="SAIMED, kembali ke Link Hub">
                <?php avatar(true); ?>
                <span><strong>SAIMED</strong><small>Sentra Alkes Indonesia</small></span>
            </a>
            <span class="maintenance-header-label">WEBSITE RESMI</span>
        </header>

        <section class="maintenance-card" aria-labelledby="maintenance-title">
            <div class="maintenance-copy">
                <div class="maintenance-status"><span aria-hidden="true"></span> SEDANG DALAM PENGEMBANGAN</div>
                <h1 id="maintenance-title">Sesuatu yang baik <em>sedang kami siapkan.</em></h1>
                <p>Website resmi SAIMED masih dalam tahap pengembangan. Kami sedang menyiapkan pengalaman yang lebih baik untuk Anda.</p>
                <div class="maintenance-actions">
                    <a class="maintenance-primary" href="<?= h(WHATSAPP) ?>" target="_blank" rel="noopener noreferrer">
                        Hubungi via WhatsApp <span aria-hidden="true">↗</span>
                    </a>
                    <a class="maintenance-secondary" href="./"><span aria-hidden="true">←</span> Kembali ke Link Hub</a>
                </div>
            </div>

            <div class="maintenance-art" aria-hidden="true">
                <div class="maintenance-orbit maintenance-orbit--outer"></div>
                <div class="maintenance-orbit maintenance-orbit--inner"></div>
                <span class="maintenance-spark maintenance-spark--one">✦</span>
                <span class="maintenance-spark maintenance-spark--two">✳</span>
                <div class="maintenance-art-card">
                    <div class="maintenance-art-top"><span></span><span></span><span></span><i></i></div>
                    <div class="maintenance-art-body">
                        <div class="maintenance-art-icon">
                            <svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M33 13a16 16 0 0 0-18 21L8 41a5 5 0 1 0 7 7l7-7a16 16 0 0 0 21-18l-9 8-7-2-2-7 8-9Z" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M42 12v8M38 16h8M49 37v6M46 40h6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <div class="maintenance-art-line maintenance-art-line--long"></div>
                        <div class="maintenance-art-line"></div>
                        <div class="maintenance-art-pill"></div>
                    </div>
                </div>
                <div class="maintenance-art-note"><span>✦</span> Dibuat dengan sepenuh hati</div>
            </div>
        </section>

        <footer class="maintenance-footer">
            <span>© SAIMED <?= date('Y') ?> · Sentra Alkes Indonesia</span>
            <span>Terima kasih telah menunggu.</span>
        </footer>
    </div>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
