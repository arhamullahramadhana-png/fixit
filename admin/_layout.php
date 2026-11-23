<?php

declare(strict_types=1);

/** Ikon sederhana untuk sidebar admin. */
function admin_icon(string $name): string
{
    $paths = [
        'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
        'kategori' => '<path d="M4 5h16M4 12h16M4 19h10"/>',
        'gejala' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3-3"/>',
        'penyebab' => '<path d="M12 9v4m0 4h.01"/><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/>',
        'aturan' => '<path d="M8 6h12M8 12h12M8 18h12"/><path d="M4 6h.01M4 12h.01M4 18h.01"/>',
        'solusi' => '<path d="m9 11 3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
        'artikel' => '<path d="M4 5h11a3 3 0 0 1 3 3v11H7a3 3 0 0 0-3 3V5z"/><path d="M4 19h14"/>',
        'log' => '<path d="M21 15a2 2 0 0 1-2 2H8l-4 4V5a2 2 0 0 1 2-2h13a2 2 0 0 1 2 2z"/>',
        'umpan_balik' => '<path d="M21 15a2 2 0 0 1-2 2H8l-4 4V5a2 2 0 0 1 2-2h13a2 2 0 0 1 2 2z"/><path d="m9 10 2 2 4-4"/>',
        'situs' => '<path d="M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18"/><circle cx="12" cy="12" r="9"/>',
        'keluar' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/>',
        'tambah' => '<path d="M12 5v14M5 12h14"/>',
    ];
    $path = $paths[$name] ?? '<circle cx="12" cy="12" r="8"/>';
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
}

function admin_header(string $title, string $active, string $subtitle = '', string $actions = ''): void
{
    $pengguna = current_user();
    $menu = [
        ['index.php', 'dashboard', 'Ringkasan'],
        ['kategori.php', 'kategori', 'Kategori'],
        ['gejala.php', 'gejala', 'Gejala'],
        ['penyebab.php', 'penyebab', 'Penyebab'],
        ['aturan.php', 'aturan', 'Aturan'],
        ['solusi.php', 'solusi', 'Solusi'],
        ['artikel.php', 'artikel', 'Artikel'],
        ['log_chat.php', 'log', 'Log chatbot'],
        ['umpan_balik.php', 'umpan_balik', 'Umpan balik'],
    ];
    ?><!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> - Admin FIXIT</title>
    <link rel="stylesheet" href="<?= asset('/assets/css/style.css') ?>">
</head>
<body>
<div class="admin-shell">
    <aside class="admin-sidebar">
        <a class="brand" href="<?= url('/admin/index.php') ?>">
            <span class="brand-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L4 17v3h3l5.3-5.3a4 4 0 0 0 5.4-5.4l-2.3 2.3-2.7-.7-.7-2.7 2.7-2.7z"/>
                </svg>
            </span>
            <span class="brand-text">FIXIT<small>Panel admin</small></span>
        </a>

        <span class="sidebar-label">Basis pengetahuan</span>
        <nav class="sidebar-nav" aria-label="Navigasi admin">
            <?php foreach ($menu as [$file, $ikon, $label]): ?>
                <a class="sidebar-link<?= $active === $file ? ' is-active' : '' ?>" href="<?= url('/admin/' . $file) ?>">
                    <?= admin_icon($ikon) ?>
                    <span><?= e($label) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="sidebar-foot">
            <a class="sidebar-link" href="<?= url('/index.php') ?>"><?= admin_icon('situs') ?><span>Lihat situs</span></a>
            <form method="post" action="<?= url('/pages/logout.php') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="sidebar-link" style="width:100%;border:0;background:none;cursor:pointer;text-align:left">
                    <?= admin_icon('keluar') ?><span>Keluar</span>
                </button>
            </form>
        </div>
    </aside>

    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <h1><?= e($title) ?></h1>
                <?php if ($subtitle !== ''): ?>
                    <p><?= e($subtitle) ?></p>
                <?php endif; ?>
            </div>
            <?= $actions ?>
            <span class="user-chip" style="margin-left:auto">
                <span class="user-avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($pengguna['nama'] ?? 'A', 0, 1))) ?></span>
                <span class="user-name"><?= e($pengguna['nama'] ?? 'Admin') ?></span>
            </span>
        </div>

        <?php $flash = flash_get(); ?>
        <?php if ($flash): ?>
            <div class="flash flash-<?= e($flash['type']) ?>" style="margin-bottom:1.2rem" data-flash>
                <?= e($flash['message']) ?>
                <button type="button" class="flash-close" data-flash-close aria-label="Tutup">&times;</button>
            </div>
        <?php endif; ?>
<?php
}

function admin_footer(): void
{
    ?>
    </main>
</div>
<script src="<?= asset('/assets/js/main.js') ?>" defer></script>
</body>
</html>
<?php
}
