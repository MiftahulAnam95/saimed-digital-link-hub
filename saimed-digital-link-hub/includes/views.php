<?php

declare(strict_types=1);

function avatar(bool $compact = false): void
{
    $class = $compact ? 'brand-avatar brand-avatar--compact' : 'brand-avatar';
    ?>
    <div class="<?= h($class) ?>">
        <img class="brand-logo" src="assets/saimed-logo.png" alt="Logo SAIMED">
    </div>
    <?php
}

function icon(string $name): string
{
    $allowedIcons = ['instagram', 'whatsapp', 'shopee', 'tokopedia', 'website', 'link'];
    $iconName = in_array($name, $allowedIcons, true) ? $name : 'link';
    $iconPath = 'assets/icons/' . $iconName . '.svg';

    // Setiap SVG dimuat terpisah agar ID gradient dan clipPath tidak bertabrakan.
    return '<span class="platform-icon icon-' . h($iconName) . '" aria-hidden="true">'
        . '<img src="' . h($iconPath) . '" alt="" loading="eager">'
        . '</span>';
}

function page_start(string $title, bool $admin = false): void
{
    ?>
    <!doctype html>
    <html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#ffffff">
        <meta name="description" content="Tautan resmi SAIMED – Sentra Alkes Indonesia.">
        <title><?= h($title) ?></title>
        <link rel="stylesheet" href="assets/style.css">
        <?php if ($admin): ?>
            <link rel="stylesheet" href="assets/admin.css">
        <?php endif; ?>
    </head>
    <body>
    <?php
}

function page_end(): void
{
    echo '</body></html>';
}

function token_input(): void
{
    echo '<input type="hidden" name="csrf" value="' . h(csrf()) . '">';
}

function icon_options(string $selected = 'link'): void
{
    foreach (ICONS as $iconName) {
        $isSelected = $iconName === $selected ? ' selected' : '';
        echo '<option value="' . h($iconName) . '"' . $isSelected . '>'
            . h(ucfirst($iconName)) . '</option>';
    }
}
