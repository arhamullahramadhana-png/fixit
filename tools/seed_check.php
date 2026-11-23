<?php

declare(strict_types=1);

/**
 * Pemeriksa basis pengetahuan FIXIT.
 * Jalankan: php tools/seed_check.php
 */

require_once __DIR__ . '/../includes/bootstrap.php';

$masalah = [];

$totalAturan = (int) $pdo->query('SELECT COUNT(*) FROM rules')->fetchColumn();
$tanpaGejala = (int) $pdo->query(
    'SELECT COUNT(*) FROM rules r WHERE NOT EXISTS (SELECT 1 FROM rule_symptoms rs WHERE rs.rule_id = r.id)'
)->fetchColumn();
if ($tanpaGejala > 0) {
    $masalah[] = "$tanpaGejala aturan tidak memiliki gejala.";
}

$tanpaSolusi = (int) $pdo->query(
    'SELECT COUNT(*) FROM causes c WHERE NOT EXISTS (SELECT 1 FROM solutions s WHERE s.cause_id = c.id)'
)->fetchColumn();
if ($tanpaSolusi > 0) {
    $masalah[] = "$tanpaSolusi penyebab tidak memiliki solusi.";
}

$gejalaYatim = (int) $pdo->query(
    'SELECT COUNT(*) FROM symptoms s WHERE NOT EXISTS (SELECT 1 FROM rule_symptoms rs WHERE rs.symptom_id = s.id)'
)->fetchColumn();
if ($gejalaYatim > 0) {
    $masalah[] = "$gejalaYatim gejala tidak dipakai aturan mana pun.";
}

$bobotTidakSah = (int) $pdo->query('SELECT COUNT(*) FROM rules WHERE bobot < 0 OR bobot > 1')->fetchColumn();
if ($bobotTidakSah > 0) {
    $masalah[] = "$bobotTidakSah aturan memiliki bobot di luar 0-1.";
}

echo "Basis pengetahuan: {$totalAturan} aturan, "
    . $pdo->query('SELECT COUNT(*) FROM symptoms')->fetchColumn() . " gejala, "
    . $pdo->query('SELECT COUNT(*) FROM causes')->fetchColumn() . " penyebab.\n\n";

$kasus = [
    'Layar hitam + kipas berputar + lampu nyala + monitor eksternal tampil' => [1, 33, 9, 5],
    'Layar hitam + kipas berputar + monitor eksternal tidak tampil' => [1, 33, 6],
    'Tidak menyala + lampu mati + charger tidak mengisi' => [8, 10, 13],
    'Panas + kipas berisik + hang' => [18, 19, 17],
    'Bunyi klik + file hilang' => [22, 23],
    'Wi-Fi tidak terdeteksi + terhubung tanpa internet' => [27, 28],
    'Keyboard mati + touchpad mati' => [29, 30],
    'Lambat + hang + penyimpanan penuh' => [16, 17, 26],
    'Baterai menggembung' => [15],
];

$gagal = 0;

foreach ($kasus as $nama => $ids) {
    $hasil = $engine->diagnose($ids);
    echo "- {$nama}\n";
    if (!$hasil['hasil']) {
        echo "  ! Tidak ada hasil di atas ambang 0,25\n";
        $gagal++;
        continue;
    }
    foreach ($hasil['hasil'] as $index => $item) {
        printf(
            "  %d. %s - skor %.3f (%d%%)\n",
            $index + 1,
            $item['nama_penyebab'],
            $item['skor'],
            score_persen($item['skor'])
        );
        foreach ($hasil['hasil'][$index]['aturan'] as $aturan) {
            printf(
                "     aturan %s: bobot %.2f, kecocokan %.2f, skor %.3f\n",
                $aturan['kode'],
                $aturan['bobot'],
                $aturan['kecocokan'],
                $aturan['skor']
            );
        }
    }
    if (count($hasil['hasil'][0]['aturan']) === 0) {
        echo "  ! Hasil teratas tidak memiliki rincian aturan\n";
        $gagal++;
    }
    echo "\n";
}

// Uji ekstraksi kata kunci chatbot.
$chatbot = new Chatbot($repo, $engine, null);
$_SESSION['chat'] = [];
$ujiChat = [
    'laptop saya menyala tetapi layarnya hitam dan kipas berputar' => 2,
    'tidak ada bunyi beep, di monitor tidak muncul gambar' => 1,
];
$refleksi = new ReflectionMethod(Chatbot::class, 'extractSymptoms');
$refleksi->setAccessible(true);
foreach ($ujiChat as $teks => $minimal) {
    $ditemukan = $refleksi->invoke($chatbot, mb_strtolower(preg_replace('/[^\p{L}\p{N}\s-]/u', ' ', $teks)));
    $jumlah = count($ditemukan);
    $status = $jumlah >= $minimal ? 'OK' : 'GAGAL';
    echo "Chatbot [{$status}] \"{$teks}\" -> {$jumlah} gejala: " . implode(',', $ditemukan) . "\n";
    if ($jumlah < $minimal) {
        $gagal++;
    }
}

echo "\n";
if ($masalah) {
    echo "MASALAH DATA:\n - " . implode("\n - ", $masalah) . "\n";
}
if ($gagal > 0 || $masalah) {
    echo "Hasil: PERLU DIPERBAIKI ({$gagal} uji gagal)\n";
    exit(1);
}
echo "Hasil: SEMUA PEMERIKSAAN LULUS\n";
