<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$slug = isset($_GET['slug']) ? trim((string) $_GET['slug']) : '';
$artikel = $slug !== '' ? $repo->articleBySlug($slug) : null;

if (!$artikel) {
    http_response_code(404);
}

$pageTitle = $artikel ? $artikel['judul'] : 'Artikel tidak ditemukan';
require __DIR__ . '/../includes/header.php';
?>

<?php if (!$artikel): ?>
    <div class="container" style="padding-top:3rem;padding-bottom:5rem">
        <div class="empty-state">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                <circle cx="12" cy="12" r="9"/><path d="M12 8h.01M11 12h1v4h1"/>
            </svg>
            <h2>Artikel tidak ditemukan</h2>
            <p>Tautan mungkin salah atau artikel sudah dipindahkan.</p>
            <a class="btn btn-primary" href="<?= url('/pages/artikel.php') ?>">Lihat semua artikel</a>
        </div>
    </div>
<?php else: ?>
    <div class="container">
        <div class="prose">
            <nav class="breadcrumb" aria-label="Remah roti">
                <a href="<?= url('/index.php') ?>">Beranda</a>
                <span>/</span>
                <a href="<?= url('/pages/artikel.php') ?>">Artikel</a>
                <?php if ($artikel['nama_kategori']): ?>
                    <span>/</span>
                    <a href="<?= url('/pages/artikel.php?kategori=' . (int) $artikel['category_id']) ?>"><?= e($artikel['nama_kategori']) ?></a>
                <?php endif; ?>
            </nav>

            <h1><?= e($artikel['judul']) ?></h1>
            <div class="article-meta" style="margin-bottom:1.6rem">
                <?php if ($artikel['nama_kategori']): ?>
                    <span class="tag"><?= e($artikel['nama_kategori']) ?></span>
                <?php endif; ?>
                <span><?= e(format_tanggal($artikel['dibuat_pada'])) ?></span>
            </div>

            <div class="article-hero">
                <img src="<?= e(article_image_url($artikel['gambar'])) ?>" alt="">
            </div>

            <?= $artikel['isi'] ?>

            <div class="result-actions" style="margin-top:2rem">
                <a class="btn btn-outline" href="<?= url('/pages/artikel.php') ?>">&larr; Semua artikel</a>
                <a class="btn btn-primary" href="<?= url('/pages/diagnosis.php') ?>">Mulai diagnosis</a>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
