<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$kategoriId = isset($_GET['category_id']) ? (int) $_GET['category_id'] : 0;
$cari = isset($_GET['q']) ? mb_strtolower(trim((string) $_GET['q'])) : '';

$daftar = $repo->symptomsByCategory($kategoriId > 0 ? $kategoriId : null, true);

if ($cari !== '') {
    $daftar = array_values(array_filter(
        $daftar,
        static fn (array $item): bool =>
            str_contains(mb_strtolower($item['pertanyaan']), $cari)
            || str_contains(mb_strtolower((string) $item['kata_kunci']), $cari)
    ));
}

echo json_encode([
    'ok'   => true,
    'data' => array_map(static fn (array $item): array => [
        'id'            => (int) $item['id'],
        'kode'          => $item['kode'],
        'category_id'   => (int) $item['category_id'],
        'nama_kategori' => $item['nama_kategori'],
        'pertanyaan'    => $item['pertanyaan'],
    ], $daftar),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
