<?php

declare(strict_types=1);

$officialLinks = array_filter(links(true), function (array $link): bool {
    return !boolval_db($link['featured']);
});

require __DIR__ . '/templates/official.php';
