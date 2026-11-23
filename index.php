<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$kategori = $repo->categories();
$gejala = $repo->symptomsByCategory(null, true);
$artikel = array_slice($repo->articles(), 0, 3);
$statistik = $repo->stats();

$jumlahGejala = [];
foreach ($gejala as $item) {
    $cid = (int) $item['category_id'];
    $jumlahGejala[$cid] = ($jumlahGejala[$cid] ?? 0) + 1;
}

$pageTitle = 'Kenali gejala, temukan penyebab';
require __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div class="container hero-grid">
        <div class="hero-copy">
            <span class="eyebrow">Asisten pemecahan masalah perangkat</span>
            <h1 class="hero-title">Laptop bermasalah?<br><span class="accent">Kenali gejalanya dulu.</span></h1>
            <p class="hero-sub">
                FIXIT memandu Anda dari gejala menuju kemungkinan penyebab dan langkah awal yang aman.
                Pilih gejala yang dialami, atau ceritakan lewat chatbot seperti berbicara dengan teknisi.
            </p>
            <div class="hero-actions">
                <a class="btn btn-primary btn-lg" href="<?= url('/pages/diagnosis.php') ?>">Mulai diagnosis</a>
                <a class="btn btn-outline btn-lg" href="<?= url('/pages/chat.php') ?>">Tanya chatbot</a>
            </div>
            <ul class="hero-meta">
                <li>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
                    Tanpa perlu mendaftar
                </li>
                <li>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
                    <?= count($gejala) ?> gejala dalam <?= count($kategori) ?> kategori
                </li>
                <li>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
                    Solusi bertahap dan aman
                </li>
            </ul>
        </div>

        <div class="hero-visual" aria-hidden="true">
            <div class="preview-card">
                <span class="preview-badge">Contoh hasil</span>
                <h3>Layar hitam, kipas berputar, lampu menyala</h3>

                <div class="preview-row">
                    <div class="preview-row-top">
                        <strong>Kerusakan panel atau kabel layar</strong>
                        <span>80%</span>
                    </div>
                    <div class="score-track"><div class="score-fill" style="width:80%"></div></div>
                </div>
                <div class="preview-row">
                    <div class="preview-row-top">
                        <strong>RAM longgar atau bermasalah</strong>
                        <span>47%</span>
                    </div>
                    <div class="score-track"><div class="score-fill" style="width:47%"></div></div>
                </div>
                <div class="preview-row">
                    <div class="preview-row-top">
                        <strong>RAM atau VGA bermasalah</strong>
                        <span>40%</span>
                    </div>
                    <div class="score-track"><div class="score-fill" style="width:40%"></div></div>
                </div>

                <div class="preview-foot">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>
                    Perkiraan awal, bukan diagnosis pasti
                </div>
            </div>
            <div class="preview-float">
                <span class="dot"></span>
                Solusi awal tersedia
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Mulai dari mana</span>
            <h2>Pilih kategori kerusakan</h2>
            <p>Setiap kategori berisi gejala yang paling sering muncul. Tidak yakin? Ceritakan saja lewat chatbot.</p>
        </div>

        <div class="card-grid cols-4">
            <?php foreach ($kategori as $item): ?>
                <a class="category-card" href="<?= url('/pages/diagnosis.php?kategori=' . (int) $item['id']) ?>">
                    <span class="category-icon"><?= category_icon($item['ikon']) ?></span>
                    <h3><?= e($item['nama_kategori']) ?></h3>
                    <p><?= e($jumlahGejala[(int) $item['id']] ?? 0) ?> gejala yang bisa diperiksa</p>
                    <span class="category-count">Periksa sekarang &rarr;</span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section-alt">
    <div class="container">
        <div class="section-head center">
            <span class="eyebrow">Cara kerja</span>
            <h2>Tiga langkah menuju solusi awal</h2>
            <p>FIXIT menghitung kecocokan gejala dengan basis aturan, lalu mengurutkan kemungkinan penyebab memakai skor kepastian.</p>
        </div>

        <div class="steps">
            <div class="step">
                <span class="step-num">1</span>
                <h3>Pilih gejala</h3>
                <p>Tentukan kategori, lalu centang gejala yang benar-benar Anda alami. Semakin lengkap, semakin akurat hasilnya.</p>
            </div>
            <div class="step">
                <span class="step-num">2</span>
                <h3>Lihat kemungkinan penyebab</h3>
                <p>Sistem menampilkan tiga penyebab teratas beserta skor kepastian dan alasan di baliknya.</p>
            </div>
            <div class="step">
                <span class="step-num">3</span>
                <h3>Ikuti solusi awal</h3>
                <p>Setiap penyebab dilengkapi langkah bertahap. Langkah berisiko ditandai agar Anda berhenti dan menghubungi teknisi.</p>
            </div>
        </div>
    </div>
</section>

<?php if ($artikel): ?>
<section class="section">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Bacaan singkat</span>
            <h2>Panduan perbaikan</h2>
            <p>Penjelasan sederhana tentang masalah umum dan cara menanganinya dengan aman.</p>
        </div>

        <div class="card-grid cols-3">
            <?php foreach ($artikel as $item): ?>
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
                        <p><?= e(excerpt($item['isi'], 110)) ?></p>
                        <span class="article-more">Baca panduan &rarr;</span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="section-tight">
    <div class="container">
        <div class="cta-band">
            <div>
                <h2>Belum yakin dengan gejalanya?</h2>
                <p>Ceritakan saja dengan bahasa sehari-hari. Chatbot akan menanyakan beberapa hal tambahan sebelum memberi kemungkinan penyebab.</p>
            </div>
            <a class="btn btn-primary btn-lg" href="<?= url('/pages/chat.php') ?>">Mulai konsultasi</a>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
