<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (isset($_GET['reset'])) {
    unset($_SESSION['last_symptoms']);
    redirect('/pages/diagnosis.php');
}

$grup = $repo->symptomsGrouped(true);
$terpilih = array_map('intval', $_SESSION['last_symptoms'] ?? []);
$kategoriAktif = isset($_GET['kategori']) ? (int) $_GET['kategori'] : 0;

// Prefill dari detail riwayat bila pengguna memilih "Diagnosis lagi".
$dariId = isset($_GET['dari']) ? (int) $_GET['dari'] : 0;
if ($dariId > 0 && is_logged_in()) {
    $asal = $repo->diagnosis($dariId);
    if ($asal && (int) $asal['user_id'] === (int) current_user()['id']) {
        $terpilih = array_map(
            static fn (array $item): int => (int) $item['id'],
            $repo->diagnosisSymptoms($dariId)
        );
    }
}

$pageTitle = 'Diagnosis gejala';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <nav class="breadcrumb" aria-label="Remah roti">
            <a href="<?= url('/index.php') ?>">Beranda</a>
            <span>/</span>
            <span>Diagnosis</span>
        </nav>
        <h1>Pilih gejala yang Anda alami</h1>
        <p>Centang semua gejala yang benar-benar terjadi. FIXIT akan membandingkannya dengan basis aturan untuk memperkirakan penyebab paling mungkin.</p>
    </div>
</div>

<div class="container">
    <div class="diagnosis-layout">
        <form id="form-diagnosis" method="post" action="<?= url('/pages/hasil.php') ?>">
            <?= csrf_field() ?>

            <div class="toolbar">
                <div class="search-box">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="11" cy="11" r="7"/><path d="m20 20-3.2-3.2"/>
                    </svg>
                    <input type="search" id="symptom-search" placeholder="Cari gejala, misalnya layar hitam" autocomplete="off">
                </div>
                <button type="button" class="btn btn-outline btn-sm" data-select-visible>Pilih yang tampil</button>
                <button type="button" class="btn btn-ghost btn-sm" data-clear-all>Kosongkan</button>
            </div>

            <div class="category-tabs" role="group" aria-label="Filter kategori">
                <button type="button" class="cat-tab<?= $kategoriAktif === 0 ? ' is-active' : '' ?>" data-cat="all">
                    Semua kategori
                </button>
                <?php foreach ($grup as $kategori): ?>
                    <button type="button"
                            class="cat-tab<?= $kategoriAktif === (int) $kategori['id'] ? ' is-active' : '' ?>"
                            data-cat="<?= (int) $kategori['id'] ?>">
                        <?= category_icon($kategori['ikon']) ?>
                        <?= e($kategori['nama_kategori']) ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <?php foreach ($grup as $kategori): ?>
                <?php if (!$kategori['symptoms']) { continue; } ?>
                <section class="symptom-group"
                         data-cat-group="<?= (int) $kategori['id'] ?>"
                         <?= ($kategoriAktif !== 0 && $kategoriAktif !== (int) $kategori['id']) ? 'hidden' : '' ?>>
                    <div class="symptom-group-head">
                        <?= category_icon($kategori['ikon']) ?>
                        <h2><?= e($kategori['nama_kategori']) ?></h2>
                    </div>
                    <div class="symptom-list">
                        <?php foreach ($kategori['symptoms'] as $gejala): ?>
                            <label class="symptom-item"
                                   data-symptom-item
                                   data-symptom-id="<?= (int) $gejala['id'] ?>"
                                   data-cat="<?= (int) $kategori['id'] ?>"
                                   data-label="<?= e($gejala['pertanyaan']) ?>">
                                <input type="checkbox"
                                       name="symptoms[]"
                                       value="<?= (int) $gejala['id'] ?>"
                                       <?= in_array((int) $gejala['id'], $terpilih, true) ? 'checked' : '' ?>>
                                <span class="symptom-text">
                                    <?= e($gejala['pertanyaan']) ?>
                                    <span class="symptom-code"><?= e($gejala['kode']) ?></span>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>

            <aside class="selection-panel">
                <h2>Gejala terpilih</h2>
                <div class="selection-count">
                    <strong data-count>0</strong>
                    <span>gejala</span>
                </div>
                <p class="selection-hint">Pilih minimal satu gejala untuk melihat kemungkinan penyebab.</p>

                <div class="selected-list" data-selected-list></div>
                <p class="selected-empty" data-selected-empty>Belum ada gejala dipilih.</p>

                <button type="submit" class="btn btn-primary btn-block" data-submit disabled>
                    Lihat kemungkinan penyebab
                </button>

                <div class="panel-note">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="12" cy="12" r="9"/><path d="M12 8h.01M11 12h1v4h1"/>
                    </svg>
                    <span>Semakin banyak gejala yang sesuai, semakin tinggi skor kepastian. Hasil tetap berupa perkiraan awal.</span>
                </div>
            </aside>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
