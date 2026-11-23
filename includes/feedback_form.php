<?php

declare(strict_types=1);

/**
 * Formulir umpan balik diagnosis.
 *
 * Set sebelum menyertakan file ini:
 * - $diagnosisId : int  id diagnosis yang sedang ditampilkan
 * - $feedback    : ?array baris diagnosis_feedback (null bila belum ada)
 * - $kembali     : string path halaman asal, misalnya /pages/hasil.php
 */

if (empty($diagnosisId)) {
    return;
}

$penilaian = (string) ($feedback['penilaian'] ?? '');
$tepat = $penilaian === 'tepat';
$kurangTepat = $penilaian === 'tidak_tepat';
$catatan = (string) ($feedback['catatan'] ?? '');
?>
<section class="feedback-card" aria-labelledby="feedback-title">
    <div class="feedback-head">
        <div>
            <h2 id="feedback-title">Apakah hasil ini sesuai?</h2>
            <p>
                Umpan balik Anda membantu admin meninjau apakah bobot aturan sudah tepat.
                Jawaban tidak mengubah hasil yang sedang Anda lihat.
            </p>
        </div>
        <?php if ($penilaian !== ''): ?>
            <span class="feedback-badge <?= $tepat ? 'is-tepat' : 'is-kurang' ?>">
                <?= $tepat ? 'Dinilai tepat' : 'Dinilai kurang tepat' ?>
            </span>
        <?php endif; ?>
    </div>

    <?php if ($penilaian !== ''): ?>
        <p class="feedback-status">
            Umpan balik terakhir dikirim <?= e(format_tanggal($feedback['waktu'] ?? null, true)) ?>.
            Anda masih bisa mengubahnya.
        </p>
    <?php endif; ?>

    <form method="post" action="<?= url('/pages/feedback.php') ?>" class="feedback-form">
        <?= csrf_field() ?>
        <input type="hidden" name="diagnosis_id" value="<?= (int) $diagnosisId ?>">
        <input type="hidden" name="kembali" value="<?= e($kembali) ?>">

        <div class="field">
            <label for="feedback-catatan">Catatan tambahan <span class="muted">(opsional)</span></label>
            <textarea id="feedback-catatan" name="catatan" rows="2" maxlength="500"
                      placeholder="Misalnya: langkah kedua berhasil, atau penyebab sebenarnya berbeda."><?= e($catatan) ?></textarea>
        </div>

        <div class="feedback-actions">
            <button type="submit" name="penilaian" value="tepat"
                    class="btn <?= $tepat ? 'btn-primary' : 'btn-outline' ?>">
                Tepat
            </button>
            <button type="submit" name="penilaian" value="tidak_tepat"
                    class="btn <?= $kurangTepat ? 'btn-primary' : 'btn-outline' ?>">
                Kurang tepat
            </button>
        </div>
    </form>
</section>
