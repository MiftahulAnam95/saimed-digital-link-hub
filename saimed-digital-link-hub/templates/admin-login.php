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
                    <h2>Selamat datang</h2>
                </div>
                <span class="badge">Area admin</span>
            </div>
            <p class="login-hint">Masukkan kata sandi untuk melanjutkan.</p>

            <?php if ($notice): ?>
                <p class="notice" role="alert"><?= h($notice) ?></p>
            <?php endif; ?>

            <form method="post">
                <?php token_input(); ?>
                <input type="hidden" name="action" value="login">
                <label>
                    Kata sandi
                    <input type="password" name="password" placeholder="Masukkan kata sandi admin"
                           autocomplete="current-password" required autofocus>
                </label>
                <button class="button primary" type="submit">Masuk ke Dashboard <span aria-hidden="true">↗</span></button>
            </form>
        </div>

        <footer class="admin-footer"><span>© SAIMED <?= date('Y') ?></span><span>All Rights Reserved</span></footer>
    </div>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
