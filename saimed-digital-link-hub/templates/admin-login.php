<?php page_start('Admin Analytics | SAIMED'); ?>

<main class="login-shell">
    <div class="login-card">
        <div class="login-heading">
            <?php avatar(true); ?>
            <div>
                <p class="small-label">SAIMED Digital Link Hub</p>
                <h1>Admin Analytics</h1>
            </div>
        </div>

        <div class="badge">✓ Area khusus administrator</div>

        <?php if ($notice): ?>
            <p class="notice" role="alert"><?= h($notice) ?></p>
        <?php endif; ?>

        <form method="post">
            <?php token_input(); ?>
            <input type="hidden" name="action" value="login">
            <label>
                Kata sandi
                <input type="password" name="password" placeholder="Masukkan kata sandi admin" required autofocus>
            </label>
            <button class="button primary" type="submit">Masuk ke Dashboard</button>
        </form>

        <a class="back" href="./">← Kembali ke halaman publik</a>
    </div>
</main>

<?php page_end(); ?>
