<?php

declare(strict_types=1);

function fallback_links(): array
{
    return [
        [
            'id' => -1,
            'key' => 'shopee',
            'title' => 'Shopee',
            'url' =>
                'https://shopee.co.id/search?keyword=Sentra%20Alkes%20Indonesia',
            'icon' => 'shopee',
            'featured' => false,
            'is_active' => true,
            'sort_order' => 1,
        ],
        [
            'id' => -2,
            'key' => 'tokopedia',
            'title' => 'Tokopedia',
            'url' => 'https://www.tokopedia.com/sentraalkes',
            'icon' => 'tokopedia',
            'featured' => false,
            'is_active' => true,
            'sort_order' => 2,
        ],
        [
            'id' => -3,
            'key' => 'whatsapp',
            'title' => 'WhatsApp',
            'url' => WHATSAPP,
            'icon' => 'whatsapp',
            'featured' => false,
            'is_active' => true,
            'sort_order' => 3,
        ],
        [
            'id' => -4,
            'key' => 'website-resmi',
            'title' => 'Website Resmi',
            'url' => '?page=official',
            'icon' => 'website',
            'featured' => true,
            'is_active' => true,
            'sort_order' => 4,
        ],
    ];
}
function boolval_db($value): bool
{
    return $value === true || $value === 1 || $value === '1' || $value === 't';
}
function links(bool $active = false): array
{
    try {
        return db()
            ->query(
                'SELECT * FROM links ' .
                    ($active ? 'WHERE is_active = true ' : '') .
                    'ORDER BY sort_order, id',
            )
            ->fetch_all(MYSQLI_ASSOC);
    } catch (Throwable $e) {
        error_log('SAIMED database: ' . $e->getMessage());
        return fallback_links();
    }
}
function safe_url(string $url): ?string
{
    $url = trim($url);
    if (preg_match('/[\x00-\x20\x7f\\\\]/', $url)) {
        return null;
    }
    if (
        (str_starts_with($url, '/') && !str_starts_with($url, '//')) ||
        preg_match('/^\?page=official$/', $url)
    ) {
        return $url;
    }
    if (
        filter_var($url, FILTER_VALIDATE_URL) &&
        in_array(
            strtolower((string) parse_url($url, PHP_URL_SCHEME)),
            ['http', 'https'],
            true,
        )
    ) {
        return $url;
    }
    return null;
}
