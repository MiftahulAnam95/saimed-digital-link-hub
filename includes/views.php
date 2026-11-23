<?php

declare(strict_types=1);

function avatar(bool $compact = false): void
{
    $class = $compact ? 'brand-avatar brand-avatar--compact' : 'brand-avatar';
    ?>
    <div class="<?= h($class) ?>">
        <img class="brand-logo" src="assets/images/saimed-logo.png" alt="Logo SAIMED">
    </div>
    <?php
}

function icon(string $name): string
{
    $allowedIcons = ['instagram', 'whatsapp', 'shopee', 'tokopedia', 'website', 'link'];
    $iconName = in_array($name, $allowedIcons, true) ? $name : 'link';
    $iconPath = 'assets/images/icons/' . $iconName . '.svg';

    // Setiap SVG dimuat terpisah agar ID gradient dan clipPath tidak bertabrakan.
    return '<span class="platform-icon icon-' . h($iconName) . '" aria-hidden="true">'
        . '<img src="' . h($iconPath) . '" alt="" loading="eager">'
        . '</span>';
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
