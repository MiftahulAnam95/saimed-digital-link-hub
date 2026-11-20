<?php
// Header dipakai bersama oleh halaman publik dan administrator.
$page_title = $page_title ?? 'SAIMED Digital Link Hub';
$admin_page = $admin_page ?? false;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#ffffff">
    <meta name="description" content="Tautan resmi SAIMED – Sentra Alkes Indonesia.">
    <title><?= h($page_title) ?></title>
    <link rel="stylesheet" href="assets/style.css">
    <?php if ($admin_page): ?>
        <link rel="stylesheet" href="assets/admin.css">
    <?php endif; ?>
</head>
<body>
