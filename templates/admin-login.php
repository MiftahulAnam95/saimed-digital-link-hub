<?php
$page_title = 'Admin Analytics | SAIMED';
$admin_page = true;
require __DIR__ . '/../includes/header.php';
?>

<main class="login-shell">
    <div class="login-container">
        <a class="back login-back" href="./">← Kembali ke halaman publik</a>

        <header class="login-profile">
            <?php avatar(); ?>
            <p class="small-label">SENTRA ALKES INDONESIA</p>
            <h1>Admin Analytics</h1>
            <p>Kelola tautan dan pantau aktivitas digital SAIMED.</p>
        </header>

        <div class="login-card">
            <div class="login-card-heading">
                <div>
                    <p class="small-label">SAIMED DIGITAL LINK HUB</p>
                    <h2>Tidak dapat masuk</h2>
                </div>
                <span class="badge">Area admin</span>
            </div>
            <p class="login-hint">Akses dashboard membutuhkan akun Super Admin yang memiliki akses melalui SSO.</p>
            <?php if ($notice): ?>
                <p class="notice" role="alert"><?= h($notice) ?></p>
            <?php elseif ($ssoError): ?>
                <p class="notice" role="alert"><?= h($ssoError) ?></p>
            <?php elseif (!empty($token)): ?>
                <p class="notice" role="alert">Sesi SSO tidak valid atau akun tidak memiliki akses. Periksa log server sebelum mencoba lagi.</p>
            <?php else: ?>
                <p class="notice" role="alert">Cookie SSO tidak ditemukan. Periksa pengaturan cookie SSO sebelum mencoba lagi.</p>
            <?php endif; ?>
            <?php if ($ssoLoginUrl !== null): ?>
                <form method="post" action="?page=admin">
                    <?php token_input(); ?>
                    <input type="hidden" name="action" value="sso-login">
                    <button class="button primary" type="submit">Coba lagi melalui SSO <span aria-hidden="true">↗</span></button>
                </form>
            <?php endif; ?>
        </div>

        <footer class="admin-footer"><span>© SAIMED <?= date('Y') ?></span><span>All Rights Reserved</span></footer>
    </div>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
