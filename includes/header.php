<?php
/** Layout header bersama. Set $pageTitle sebelum menyertakan file ini. */
$judulHalaman = $pageTitle ?? 'FIXIT';
$pengguna = current_user();
?><!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($judulHalaman) ?> - FIXIT</title>
    <meta name="description" content="FIXIT membantu mengenali kemungkinan penyebab kerusakan laptop dari gejala yang Anda alami.">
    <link rel="stylesheet" href="<?= asset('/assets/css/style.css') ?>">
</head>
<body>
<a class="skip-link" href="#konten">Lewati ke konten</a>

<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="<?= url('/index.php') ?>" aria-label="FIXIT, beranda">
            <span class="brand-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L4 17v3h3l5.3-5.3a4 4 0 0 0 5.4-5.4l-2.3 2.3-2.7-.7-.7-2.7 2.7-2.7z"/>
                </svg>
            </span>
            <span class="brand-text">FIXIT<small>Asisten perangkat</small></span>
        </a>

        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav" data-nav-toggle>
            <span class="sr-only">Buka menu</span>
            <span></span><span></span><span></span>
        </button>

        <nav class="site-nav" id="site-nav" aria-label="Navigasi utama">
            <a class="nav-link<?= nav_active('index.php') ?>" href="<?= url('/index.php') ?>">Beranda</a>
            <a class="nav-link<?= nav_active('diagnosis.php') ?>" href="<?= url('/pages/diagnosis.php') ?>">Diagnosis</a>
            <a class="nav-link<?= nav_active('chat.php') ?>" href="<?= url('/pages/chat.php') ?>">Chatbot</a>
            <a class="nav-link<?= nav_active('artikel.php', 'artikel_detail.php') ?>" href="<?= url('/pages/artikel.php') ?>">Artikel</a>
            <?php if ($pengguna): ?>
                <a class="nav-link<?= nav_active('riwayat.php') ?>" href="<?= url('/pages/riwayat.php') ?>">Riwayat</a>
            <?php endif; ?>
            <?php if (is_admin()): ?>
                <a class="nav-link nav-link-admin" href="<?= url('/admin/index.php') ?>">Admin</a>
            <?php endif; ?>
        </nav>

        <div class="header-actions">
            <?php if ($pengguna): ?>
                <span class="user-chip" title="Masuk sebagai <?= e($pengguna['nama']) ?>">
                    <span class="user-avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($pengguna['nama'], 0, 1))) ?></span>
                    <span class="user-name"><?= e($pengguna['nama']) ?></span>
                </span>
                <form method="post" action="<?= url('/pages/logout.php') ?>" class="inline-form">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-ghost btn-sm">Keluar</button>
                </form>
            <?php else: ?>
                <a class="btn btn-ghost btn-sm" href="<?= url('/pages/login.php') ?>">Masuk</a>
                <a class="btn btn-primary btn-sm" href="<?= url('/pages/daftar.php') ?>">Daftar</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<?php $flash = flash_get(); ?>
<?php if ($flash): ?>
    <div class="container flash-wrap">
        <div class="flash flash-<?= e($flash['type']) ?>" role="status" data-flash>
            <?= e($flash['message']) ?>
            <button type="button" class="flash-close" data-flash-close aria-label="Tutup">&times;</button>
        </div>
    </div>
<?php endif; ?>

<main id="konten" class="site-main">
