<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (is_post()) {
    csrf_verify();

    $ids = post_ids('symptoms');
    if (!$ids) {
        flash('error', 'Pilih minimal satu gejala terlebih dahulu.');
        redirect('/pages/diagnosis.php');
    }

    $hasil = $engine->diagnose($ids);
    if (!$hasil['hasil']) {
        flash('info', 'Gejala yang dipilih belum cukup untuk menentukan penyebab. Coba tambahkan gejala lain.');
        redirect('/pages/diagnosis.php');
    }

    $items = [];
    foreach ($hasil['hasil'] as $item) {
        $item['solusi'] = $engine->solusi((int) $item['cause_id']);
        $items[] = $item;
    }

    $diagnosisId = null;
    try {
        $diagnosisId = $repo->saveDiagnosis(
            is_logged_in() ? (int) current_user()['id'] : null,
            $ids,
            $items,
            'Diagnosis mandiri dengan ' . count($ids) . ' gejala.'
        );
    } catch (Throwable $e) {
        // Riwayat dan umpan balik bersifat tambahan; jangan gagalkan hasil.
        error_log('Simpan diagnosis gagal: ' . $e->getMessage());
    }

    $_SESSION['last_symptoms'] = $ids;
    $_SESSION['hasil'] = [
        'items'        => $items,
        'gejala_ids'   => $ids,
        'diagnosis_id' => $diagnosisId,
        'waktu'        => date('Y-m-d H:i:s'),
    ];

    redirect('/pages/hasil.php');
}

$data = $_SESSION['hasil'] ?? null;
$gejala = $data ? $repo->symptomsByIds($data['gejala_ids']) : [];
$namaGejala = [];
foreach ($gejala as $item) {
    $namaGejala[(int) $item['id']] = $item['pertanyaan'];
}

$diagnosisId = (int) ($data['diagnosis_id'] ?? 0);
$feedback = $diagnosisId > 0 ? $repo->feedbackForDiagnosis($diagnosisId) : null;
$ringkasanTeks = $data ? diagnosis_summary_text($data['items'], $gejala, $data['waktu'] ?? null) : '';

$pageTitle = 'Hasil diagnosis';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <nav class="breadcrumb" aria-label="Remah roti">
            <a href="<?= url('/index.php') ?>">Beranda</a>
            <span>/</span>
            <a href="<?= url('/pages/diagnosis.php') ?>">Diagnosis</a>
            <span>/</span>
            <span>Hasil</span>
        </nav>
        <h1>Kemungkinan penyebab</h1>
        <p>Diurutkan berdasarkan kecocokan gejala dan bobot aturan. Gunakan sebagai perkiraan awal, bukan diagnosis pasti.</p>
    </div>
</div>

<div class="container" style="padding-top:2rem;padding-bottom:4rem">
    <?php if (!$data): ?>
        <div class="empty-state">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                <circle cx="11" cy="11" r="7"/><path d="m20 20-3.2-3.2"/>
            </svg>
            <h2>Belum ada hasil diagnosis</h2>
            <p>Pilih gejala yang Anda alami terlebih dahulu untuk melihat kemungkinan penyebab.</p>
            <a class="btn btn-primary" href="<?= url('/pages/diagnosis.php') ?>">Mulai diagnosis</a>
        </div>
    <?php else: ?>
        <?php
        $jumlahPenyebab = count($data['items']);
        $teratas = $data['items'][0];
        $adaTeknisi = false;
        foreach ($data['items'] as $item) {
            foreach ($item['solusi'] as $langkah) {
                if ((int) $langkah['butuh_teknisi'] === 1) {
                    $adaTeknisi = true;
                    break 2;
                }
            }
        }
        ?>

        <div class="result-summary">
            <div>
                <h2><?= $jumlahPenyebab ?> kemungkinan penyebab ditemukan</h2>
                <p>
                    Berdasarkan <?= count($gejala) ?> gejala yang dipilih, penyebab paling mungkin adalah
                    <strong><?= e($teratas['nama_penyebab']) ?></strong>
                    dengan skor kepastian <?= score_persen($teratas['skor']) ?>%.
                    <?php if ($data['diagnosis_id'] && is_logged_in()): ?>
                        Hasil ini tersimpan di riwayat Anda.
                    <?php else: ?>
                        Masuk agar hasil ini tersimpan di riwayat.
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
            <?php foreach ($data['items'] as $index => $item): ?>
                <?php $risiko = risk_meta($item['tingkat_risiko']); ?>
                <article class="result-card<?= $index === 0 ? ' is-top' : '' ?>">
                    <div class="result-head">
                        <span class="rank-badge" aria-hidden="true"><?= $index + 1 ?></span>
                        <div class="result-title">
                            <h3><?= e($item['nama_penyebab']) ?></h3>
                            <p><?= e($item['deskripsi']) ?></p>
                            <div class="badges">
                                <span class="badge <?= e($risiko['class']) ?>"><?= e($risiko['label']) ?></span>
                                <span class="badge badge-neutral"><?= (int) $item['jumlah_aturan_cocok'] ?> aturan cocok</span>
                                <?php if ($index === 0): ?>
                                    <span class="badge badge-green">Paling mungkin</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="score-block">
                        <div class="score-line">
                            <span>Skor kepastian</span>
                            <strong><?= score_persen($item['skor']) ?>%</strong>
                        </div>
                        <div class="score-track">
                            <div class="score-fill" style="width:<?= score_persen($item['skor']) ?>%"></div>
                        </div>
                    </div>

                    <div class="solution-block">
                        <h4>Solusi awal</h4>
                        <ol class="solution-list">
                            <?php foreach ($item['solusi'] as $langkah): ?>
                                <li class="<?= (int) $langkah['butuh_teknisi'] === 1 ? 'is-teknisi' : '' ?>">
                                    <?= e($langkah['langkah']) ?>
                                </li>
                            <?php endforeach; ?>
                        </ol>

                        <?php if ($adaTeknisi): ?>
                            <div class="solution-warning">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/>
                                </svg>
                                <span>Sebagian langkah bertanda memerlukan teknisi. Bila perangkat panas berlebih, berbau terbakar, atau baterai menggembung, hentikan pemakaian dan hubungi teknisi.</span>
                            </div>
                        <?php endif; ?>

                        <details class="why">
                            <summary>Mengapa penyebab ini muncul?</summary>
                            <ul>
                                <?php foreach ($item['aturan'] as $aturan): ?>
                                    <li>
                                        <strong><?= e($aturan['kode']) ?></strong> &middot;
                                        kecocokan <?= (int) round($aturan['kecocokan'] * 100) ?>%
                                        &times; bobot <?= number_format($aturan['bobot'], 2, ',', '.') ?>
                                        = skor <?= number_format($aturan['skor'], 3, ',', '.') ?>.
                                        Gejala cocok:
                                        <?php
                                        $namaCocok = [];
                                        foreach ($aturan['gejala_cocok'] as $sid) {
                                            $namaCocok[] = $namaGejala[$sid] ?? ('#' . $sid);
                                        }
                                        echo e(implode(', ', $namaCocok));
                                        ?>.
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </details>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="result-actions">
            <a class="btn btn-outline" href="<?= url('/pages/diagnosis.php?edit=1') ?>">Ubah gejala</a>
            <a class="btn btn-outline" href="<?= url('/pages/diagnosis.php?reset=1') ?>">Diagnosis ulang</a>
            <?php if (!$data['diagnosis_id']): ?>
                <a class="btn btn-outline" href="<?= url('/pages/login.php') ?>">Masuk untuk menyimpan</a>
            <?php elseif (is_logged_in()): ?>
                <a class="btn btn-outline" href="<?= url('/pages/riwayat.php?id=' . (int) $data['diagnosis_id']) ?>">Lihat di riwayat</a>
            <?php else: ?>
                <a class="btn btn-outline" href="<?= url('/pages/login.php') ?>">Masuk untuk menyimpan</a>
            <?php endif; ?>
            <a class="btn btn-ghost" href="<?= url('/pages/chat.php') ?>">Tanya chatbot</a>
            <button type="button" class="btn btn-ghost" onclick="window.print()">Cetak hasil</button>
            <button type="button" class="btn btn-ghost" data-copy-summary data-copy-target="summary-hasil">Salin ringkasan</button>
            <span class="copy-status" data-copy-status role="status" aria-live="polite"></span>
        </div>

        <textarea id="summary-hasil" hidden readonly><?= e($ringkasanTeks) ?></textarea>

        <?php
        $kembali = '/pages/hasil.php';
        require __DIR__ . '/../includes/feedback_form.php';
        ?>

        <div class="disclaimer">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="12" cy="12" r="9"/><path d="M12 8h.01M11 12h1v4h1"/>
            </svg>
            <span>
                FIXIT memberikan perkiraan awal berdasarkan aturan dan bobot yang telah disusun.
                Hasil tidak menggantikan pemeriksaan teknisi, terutama untuk kerusakan fisik seperti baterai menggembung,
                bau terbakar, atau percikan listrik.
            </span>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
