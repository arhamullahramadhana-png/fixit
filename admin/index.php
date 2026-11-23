<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
require_once __DIR__ . '/_layout.php';

$statistik = $repo->stats();
$penyebabTeratas = $repo->topCauses(5);
$gejalaTeratas = $repo->topSymptoms(5);
$logTerbaru = $repo->unrecognizedLogs(5);
$umpanBalik = $repo->feedbackStats();
$penyebabNegatif = $repo->causesWithNegativeFeedback(5);

$maksPenyebab = 1;
foreach ($penyebabTeratas as $item) {
    $maksPenyebab = max($maksPenyebab, (int) $item['jumlah']);
}
$maksGejala = 1;
foreach ($gejalaTeratas as $item) {
    $maksGejala = max($maksGejala, (int) $item['jumlah']);
}

admin_header(
    'Ringkasan',
    'index.php',
    'Kondisi basis pengetahuan dan aktivitas diagnosis FIXIT.',
    '<a class="btn btn-primary btn-sm" href="' . url('/admin/gejala.php?action=new') . '">' . admin_icon('tambah') . ' Tambah gejala</a>'
);
?>

<div class="stat-grid">
    <div class="stat-card">
        <span>Gejala</span>
        <strong><?= (int) $statistik['gejala'] ?></strong>
    </div>
    <div class="stat-card">
        <span>Penyebab</span>
        <strong><?= (int) $statistik['penyebab'] ?></strong>
    </div>
    <div class="stat-card">
        <span>Aturan diagnosis</span>
        <strong><?= (int) $statistik['aturan'] ?></strong>
    </div>
    <div class="stat-card">
        <span>Diagnosis tersimpan</span>
        <strong><?= (int) $statistik['diagnosis'] ?></strong>
    </div>
    <div class="stat-card">
        <span>Umpan balik</span>
        <strong><?= (int) $umpanBalik['total'] ?></strong>
        <small class="muted"><?= (int) $umpanBalik['persen_tepat'] ?>% dinilai tepat</small>
    </div>
</div>

<div class="admin-grid">
    <section class="panel">
        <div class="panel-head">
            <h2>Penyebab paling sering terdiagnosis</h2>
        </div>
        <div class="panel-body">
            <?php if (!$penyebabTeratas): ?>
                <p class="muted mb-0 small">Belum ada diagnosis tersimpan.</p>
            <?php else: ?>
                <div class="bar-list">
                    <?php foreach ($penyebabTeratas as $item): ?>
                        <div class="bar-row">
                            <div class="bar-row-top">
                                <strong><?= e($item['nama_penyebab']) ?></strong>
                                <span><?= (int) $item['jumlah'] ?>x</span>
                            </div>
                            <div class="bar-track">
                                <div class="bar-fill" style="width:<?= (int) round(((int) $item['jumlah'] / $maksPenyebab) * 100) ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="panel">
        <div class="panel-head">
            <h2>Gejala paling sering dipilih</h2>
        </div>
        <div class="panel-body">
            <?php if (!$gejalaTeratas): ?>
                <p class="muted mb-0 small">Belum ada gejala yang tercatat pada riwayat.</p>
            <?php else: ?>
                <div class="bar-list">
                    <?php foreach ($gejalaTeratas as $item): ?>
                        <div class="bar-row">
                            <div class="bar-row-top">
                                <strong><span class="mono small"><?= e($item['kode']) ?></span> <?= e($item['pertanyaan']) ?></strong>
                                <span><?= (int) $item['jumlah'] ?>x</span>
                            </div>
                            <div class="bar-track">
                                <div class="bar-fill" style="width:<?= (int) round(((int) $item['jumlah'] / $maksGejala) * 100) ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="panel">
        <div class="panel-head">
            <h2>Log chatbot belum dikenali</h2>
            <a class="btn btn-ghost btn-sm" href="<?= url('/admin/log_chat.php') ?>">Lihat semua</a>
        </div>
        <div class="panel-body flush">
            <?php if (!$logTerbaru): ?>
                <div class="table-empty">Tidak ada log yang menunggu tinjauan.</div>
            <?php else: ?>
                <table class="data-table" style="min-width:0">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Pesan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logTerbaru as $log): ?>
                            <tr>
                                <td class="small nowrap"><?= e(format_tanggal($log['waktu'])) ?></td>
                                <td class="small"><?= e(excerpt($log['pesan'], 70)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </section>

    <section class="panel">
        <div class="panel-head">
            <h2>Penyebab dengan umpan balik kurang tepat</h2>
            <a class="btn btn-ghost btn-sm" href="<?= url('/admin/umpan_balik.php') ?>">Lihat umpan balik</a>
        </div>
        <div class="panel-body">
            <?php if (!$penyebabNegatif): ?>
                <p class="muted mb-0 small">Belum ada penyebab yang dinilai kurang tepat.</p>
            <?php else: ?>
                <ul class="small" style="margin:0;padding-left:1.1rem;color:var(--muted)">
                    <?php foreach ($penyebabNegatif as $item): ?>
                        <li><?= e($item['nama_penyebab']) ?> — <?= (int) $item['jumlah'] ?>x kurang tepat</li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </section>

    <section class="panel">
        <div class="panel-head">
            <h2>Pintasan pengelolaan</h2>
        </div>
        <div class="panel-body">
            <ul class="small" style="margin:0;padding-left:1.1rem;color:var(--muted)">
                <li>Periksa <a href="<?= url('/admin/aturan.php') ?>">aturan diagnosis</a> setelah menambah gejala baru.</li>
                <li>Pastikan setiap penyebab memiliki <a href="<?= url('/admin/solusi.php') ?>">solusi bertahap</a>.</li>
                <li>Tinjau <a href="<?= url('/admin/log_chat.php') ?>">log chatbot</a> secara berkala untuk menambah kata kunci.</li>
                <li>Gunakan <a href="<?= url('/admin/umpan_balik.php') ?>">umpan balik diagnosis</a> untuk mengevaluasi bobot aturan.</li>
                <li>Jalankan <span class="mono">php tools/seed_check.php</span> untuk memeriksa konsistensi basis pengetahuan.</li>
            </ul>
            <div class="form-note" style="margin-top:1rem;margin-bottom:0">
                <?= (int) $statistik['log_tidak_dikenali'] ?> pesan chatbot menunggu tinjauan &middot;
                <?= (int) $statistik['artikel'] ?> artikel &middot;
                <?= (int) $statistik['pengguna'] ?> pengguna terdaftar
            </div>
        </div>
    </section>
</div>

<?php admin_footer(); ?>
