<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
require_once __DIR__ . '/_layout.php';

$statistik = $repo->feedbackStats();
$daftar = $repo->feedbackList(200);
$penyebabNegatif = $repo->causesWithNegativeFeedback(5);

$maksNegatif = 1;
foreach ($penyebabNegatif as $item) {
    $maksNegatif = max($maksNegatif, (int) $item['jumlah']);
}

admin_header(
    'Umpan balik',
    'umpan_balik.php',
    'Penilaian pengguna terhadap hasil diagnosis, berguna untuk menyetel ulang bobot aturan.'
);
?>

<div class="stat-grid">
    <div class="stat-card">
        <span>Total umpan balik</span>
        <strong><?= (int) $statistik['total'] ?></strong>
    </div>
    <div class="stat-card">
        <span>Dinilai tepat</span>
        <strong><?= (int) $statistik['tepat'] ?></strong>
    </div>
    <div class="stat-card">
        <span>Dinilai kurang tepat</span>
        <strong><?= (int) $statistik['tidak_tepat'] ?></strong>
    </div>
    <div class="stat-card">
        <span>Persentase tepat</span>
        <strong><?= (int) $statistik['persen_tepat'] ?>%</strong>
    </div>
</div>

<div class="admin-grid">
    <section class="panel">
        <div class="panel-head">
            <h2>Penyebab paling sering dinilai kurang tepat</h2>
        </div>
        <div class="panel-body">
            <?php if (!$penyebabNegatif): ?>
                <p class="muted mb-0 small">Belum ada umpan balik "kurang tepat".</p>
            <?php else: ?>
                <div class="bar-list">
                    <?php foreach ($penyebabNegatif as $item): ?>
                        <div class="bar-row">
                            <div class="bar-row-top">
                                <strong><?= e($item['nama_penyebab']) ?></strong>
                                <span><?= (int) $item['jumlah'] ?>x</span>
                            </div>
                            <div class="bar-track">
                                <div class="bar-fill" style="width:<?= (int) round(((int) $item['jumlah'] / $maksNegatif) * 100) ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="panel">
        <div class="panel-head">
            <h2>Cara memakai umpan balik</h2>
        </div>
        <div class="panel-body">
            <ul class="small" style="margin:0;padding-left:1.1rem;color:var(--muted)">
                <li>Bandingkan catatan pengguna dengan gejala yang mereka pilih.</li>
                <li>Bila satu penyebab sering dinilai kurang tepat, tinjau kombinasi gejala pada <a href="<?= url('/admin/aturan.php') ?>">aturan diagnosis</a>.</li>
                <li>Turunkan bobot aturan yang terlalu luas, atau tambahkan gejala pembeda yang lebih spesifik.</li>
                <li>Umpan balik tidak mengubah skor secara otomatis; keputusan tetap di tangan admin.</li>
            </ul>
        </div>
    </section>
</div>

<div class="table-wrap" style="margin-top:1.2rem">
    <?php if (!$daftar): ?>
        <div class="table-empty">Belum ada umpan balik diagnosis.</div>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Diagnosis</th>
                    <th>Pengguna</th>
                    <th>Penilaian</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($daftar as $item): ?>
                    <tr>
                        <td class="small nowrap"><?= e(format_tanggal($item['waktu'], true)) ?></td>
                        <td>
                            <strong class="small"><?= e($item['hasil_teratas'] ?: 'Diagnosis') ?></strong>
                            <?php if ($item['nama_penyebab']): ?>
                                <span class="muted small">(<?= e($item['nama_penyebab']) ?>)</span>
                            <?php endif; ?>
                        </td>
                        <td class="small"><?= e($item['nama'] ?: 'Anonim') ?></td>
                        <td>
                            <span class="feedback-badge <?= $item['penilaian'] === 'tepat' ? 'is-tepat' : 'is-kurang' ?>">
                                <?= $item['penilaian'] === 'tepat' ? 'Tepat' : 'Kurang tepat' ?>
                            </span>
                        </td>
                        <td class="small"><?= e($item['catatan'] ?: '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php admin_footer(); ?>
