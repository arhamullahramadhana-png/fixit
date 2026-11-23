<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (!is_post()) {
    redirect('/pages/hasil.php');
}

csrf_verify();

$diagnosisId = post_int('diagnosis_id');
$penilaian = post_str('penilaian');
$catatan = post_str('catatan');
$kembali = post_str('kembali');

if (!preg_match('#\A/pages/(?:hasil\.php|riwayat\.php(?:\?id=\d+)?)\z#', $kembali)) {
    $kembali = '/pages/hasil.php';
}

if (!in_array($penilaian, ['tepat', 'tidak_tepat'], true)) {
    flash('error', 'Pilih penilaian umpan balik terlebih dahulu.');
    redirect($kembali);
}

if (mb_strlen($catatan) > 500) {
    flash('error', 'Catatan umpan balik maksimal 500 karakter.');
    redirect($kembali);
}

$diagnosis = $repo->diagnosis($diagnosisId);
$userId = is_logged_in() ? (int) current_user()['id'] : null;
$boleh = false;

if ($diagnosis) {
    if ($diagnosis['user_id'] !== null) {
        $boleh = $userId !== null && (int) $diagnosis['user_id'] === $userId;
    } else {
        $boleh = (int) ($_SESSION['hasil']['diagnosis_id'] ?? 0) === $diagnosisId;
    }
}

if (!$boleh) {
    flash('error', 'Umpan balik tidak dapat dikirim untuk diagnosis ini.');
    redirect($kembali);
}

$repo->saveFeedback(
    $diagnosisId,
    $userId,
    $penilaian,
    $catatan !== '' ? $catatan : null
);

flash('success', 'Terima kasih, umpan balik Anda sudah disimpan.');
redirect($kembali);
