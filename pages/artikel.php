<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$kategoriId = isset($_GET['kategori']) ? (int) $_GET['kategori'] : 0;
$kategori = $repo->categories();
$daftar = $repo->articles($kategoriId > 0 ? $kategoriId : null);

$pageTitle = 'Artikel panduan';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <nav class="breadcrumb" aria-label="Remah roti">
            <a href="<?= url('/index.php') ?>">Beranda</a>
            <span>/</span>
            <span>Artikel</span>
        </nav>
        <h1>Artikel panduan perbaikan</h1>
        <p>Penjelasan sederhana tentang masalah umum pada laptop, beserta langkah yang aman untuk dicoba lebih dulu.</p>
    </div>
</div>

<div class="container" style="padding-top:2rem;padding-bottom:4rem">
    <div class="category-tabs" role="group" aria-label="Filter kategori artikel">
        <a class="cat-tab<?= $kategoriId === 0 ? ' is-active' : '' ?>" href="<?= url('/pages/artikel.php') ?>">Semua artikel</a>
        <?php foreach ($kategori as $item): ?>
            <a class="cat-tab<?= $kategoriId === (int) $item['id'] ? ' is-active' : '' ?>"
               href="<?= url('/pages/artikel.php?kategori=' . (int) $item['id']) ?>">
                <?= category_icon($item['ikon']) ?>
                <?= e($item['nama_kategori']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (!$daftar): ?>
        <div class="empty-state">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                <path d="M4 5h11a3 3 0 0 1 3 3v11H7a3 3 0 0 0-3 3V5z"/><path d="M4 19h14"/>
            </svg>
            <h2>Belum ada artikel</h2>
            <p>Artikel untuk kategori ini belum tersedia. Silakan pilih kategori lain.</p>
            <a class="btn btn-primary" href="<?= url('/pages/artikel.php') ?>">Lihat semua artikel</a>
        </div>
    <?php else: ?>
        <div class="card-grid cols-3">
            <?php foreach ($daftar as $item): ?>
                <a class="article-card" href="<?= url('/pages/artikel_detail.php?slug=' . urlencode($item['slug'])) ?>">
                    <div class="article-thumb">
                        <img src="<?= e(article_image_url($item['gambar'])) ?>" alt="" loading="lazy">
                    </div>
                    <div class="article-body">
                        <div class="article-meta">
                            <?php if ($item['nama_kategori']): ?>
                                <span class="tag"><?= e($item['nama_kategori']) ?></span>
                            <?php endif; ?>
                            <span><?= e(format_tanggal($item['dibuat_pada'])) ?></span>
                        </div>
                        <h3><?= e($item['judul']) ?></h3>
                        <p><?= e(excerpt($item['isi'], 130)) ?></p>
                        <span class="article-more">Baca panduan &rarr;</span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
