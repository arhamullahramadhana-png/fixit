<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_login();
$pengguna = current_user();

$detail = null;

if (isset($_GET['id'])) {
    $detail = $repo->diagnosis((int) $_GET['id']);
    if (!$detail || (int) $detail['user_id'] !== (int) $pengguna['id']) {
        flash('error', 'Riwayat diagnosis tidak ditemukan.');
        redirect('/pages/riwayat.php');
    }
}

$pageTitle = $detail ? 'Detail riwayat' : 'Riwayat diagnosis';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <nav class="breadcrumb" aria-label="Remah roti">
            <a href="<?= url('/index.php') ?>">Beranda</a>
            <span>/</span>
            <?php if ($detail): ?>
                <a href="<?= url('/pages/riwayat.php') ?>">Riwayat</a>
                <span>/</span>
                <span>Detail</span>
            <?php else: ?>
                <span>Riwayat</span>
            <?php endif; ?>
        </nav>
        <h1><?= $detail ? 'Detail diagnosis' : 'Riwayat diagnosis' ?></h1>
        <p><?= $detail
            ? 'Diagnosis pada ' . e(format_tanggal($detail['waktu'], true)) . '.'
            : 'Semua hasil diagnosis yang pernah Anda simpan, terbaru di atas.' ?></p>
    </div>
</div>

<?php if ($detail): ?>
    <div class="container" style="padding-top:2rem;padding-bottom:4rem">
        <?php
        $hasil = $repo->diagnosisResults((int) $detail['id']);
        $gejala = $repo->diagnosisSymptoms((int) $detail['id']);
        $diagnosisId = (int) $detail['id'];
        $feedback = $repo->feedbackForDiagnosis($diagnosisId);
        $kembali = '/pages/riwayat.php?id=' . $diagnosisId;

        $itemsRingkasan = [];
        foreach ($hasil as $item) {
            $item['solusi'] = $repo->solutionsFor((int) $item['cause_id']);
            $itemsRingkasan[] = $item;
        }
        $ringkasanTeks = diagnosis_summary_text($itemsRingkasan, $gejala, $detail['waktu']);
        ?>

        <div class="result-summary">
            <div>
                <h2><?= e($detail['hasil_teratas'] ?: 'Belum ada hasil') ?></h2>
                <p>
                    <?= e($detail['ringkasan'] ?: 'Diagnosis tersimpan.') ?>
                    <?php if ($hasil): ?>
                        Skor teratas <?= score_persen($hasil[0]['skor']) ?>%.
                    <?php endif; ?>
                </p>
                <div class="symptom-tags">
                    <?php foreach ($gejala as $item): ?>
                        <span class="symptom-tag"><?= e($item['pertanyaan']) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="result-list">
            <?php foreach ($itemsRingkasan as $index => $item): ?>
                <?php
                $risiko = risk_meta($item['tingkat_risiko']);
                $solusi = $item['solusi'];
                ?>
                <article class="result-card<?= $index === 0 ? ' is-top' : '' ?>">
                    <div class="result-head">
                        <span class="rank-badge" aria-hidden="true"><?= (int) $item['urutan'] ?></span>
                        <div class="result-title">
                            <h3><?= e($item['nama_penyebab']) ?></h3>
                            <p><?= e($item['deskripsi']) ?></p>
                            <div class="badges">
                                <span class="badge <?= e($risiko['class']) ?>"><?= e($risiko['label']) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="score-block">
                        <div class="score-line">
                            <span>Skor kepastian saat diagnosis</span>
                            <strong><?= score_persen($item['skor']) ?>%</strong>
                        </div>
                        <div class="score-track">
                            <div class="score-fill" style="width:<?= score_persen($item['skor']) ?>%"></div>
                        </div>
                    </div>
                    <div class="solution-block">
                        <h4>Solusi awal</h4>
                        <ol class="solution-list">
                            <?php foreach ($solusi as $langkah): ?>
                                <li class="<?= (int) $langkah['butuh_teknisi'] === 1 ? 'is-teknisi' : '' ?>">
                                    <?= e($langkah['langkah']) ?>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <?php require __DIR__ . '/../includes/feedback_form.php'; ?>

        <div class="result-actions">
            <a class="btn btn-outline" href="<?= url('/pages/riwayat.php') ?>">Kembali ke riwayat</a>
            <a class="btn btn-primary" href="<?= url('/pages/diagnosis.php?dari=' . (int) $detail['id']) ?>">Diagnosis lagi</a>
            <button type="button" class="btn btn-ghost" onclick="window.print()">Cetak hasil</button>
            <button type="button" class="btn btn-ghost" data-copy-summary data-copy-target="summary-riwayat">Salin ringkasan</button>
            <span class="copy-status" data-copy-status role="status" aria-live="polite"></span>
        </div>

        <textarea id="summary-riwayat" hidden readonly><?= e($ringkasanTeks) ?></textarea>
    </div>
<?php else: ?>
    <?php $daftar = $repo->diagnosesForUser((int) $pengguna['id']); ?>

    <div class="container history-list">
        <?php if (!$daftar): ?>
            <div class="empty-state">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                    <path d="M12 8v5l3 2"/><circle cx="12" cy="12" r="9"/>
                </svg>
                <h2>Belum ada riwayat</h2>
                <p>Hasil diagnosis akan tersimpan di sini setelah Anda melakukan pemeriksaan.</p>
                <a class="btn btn-primary" href="<?= url('/pages/diagnosis.php') ?>">Mulai diagnosis</a>
            </div>
        <?php else: ?>
            <?php foreach ($daftar as $item): ?>
                <a class="history-item" href="<?= url('/pages/riwayat.php?id=' . (int) $item['id']) ?>">
                    <span class="history-date"><?= e(format_tanggal($item['waktu'], true)) ?></span>
                    <span class="history-main">
                        <strong><?= e($item['hasil_teratas'] ?: 'Diagnosis') ?></strong>
                        <span><?= (int) $item['jumlah_gejala'] ?> gejala diperiksa</span>
                    </span>
                    <span class="history-score">
                        <strong><?= $item['skor_teratas'] !== null ? score_persen($item['skor_teratas']) . '%' : '-' ?></strong>
                        <span>skor</span>
                    </span>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
