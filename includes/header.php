<?php
// Header dipakai bersama oleh halaman publik dan administrator.
$page_title = $page_title ?? 'SAIMED Digital Link Hub';
$admin_page = $admin_page ?? false;
$style_css_path = __DIR__ . '/../assets/css/style.css';
$style_css_version = is_readable($style_css_path) ? substr(hash_file('sha256', $style_css_path), 0, 12) : '';
$admin_css_path = __DIR__ . '/../assets/css/admin.css';
$admin_css_version = $admin_page && is_readable($admin_css_path) ? substr(hash_file('sha256', $admin_css_path), 0, 12) : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#ffffff">
    <meta name="description" content="Tautan resmi SAIMED – Sentra Alkes Indonesia.">
    <title><?= h($page_title) ?></title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= h($style_css_version) ?>">
    <?php if ($admin_page): ?>
        <link rel="stylesheet" href="assets/css/admin.css?v=<?= h($admin_css_version) ?>">
    <?php endif; ?>
</head>
<body>
