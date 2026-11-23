<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$opsiPenyebab = [];
foreach ($repo->causes() as $penyebab) {
    $opsiPenyebab[(int) $penyebab['id']] = $penyebab['kode'] . ' - ' . $penyebab['nama_penyebab'];
}

$crud = [
    'table'    => 'solutions',
    'judul'    => 'Solusi',
    'subjudul' => 'Langkah awal yang ditampilkan untuk setiap kemungkinan penyebab.',
    'order'    => 'cause_id, urutan',
    'fields'   => [
        [
            'name' => 'cause_id',
            'label' => 'Penyebab',
            'type' => 'select',
            'options' => $opsiPenyebab,
            'required' => true,
        ],
        [
            'name' => 'langkah',
            'label' => 'Langkah',
            'type' => 'textarea',
            'rows' => 2,
            'required' => true,
        ],
        [
            'name' => 'urutan',
            'label' => 'Urutan',
            'type' => 'number',
            'default' => 1,
            'min' => 1,
        ],
        [
            'name' => 'butuh_teknisi',
            'label' => 'Langkah ini sebaiknya dilakukan teknisi',
            'type' => 'checkbox',
            'hint' => 'Ditandai sebagai peringatan pada halaman hasil.',
        ],
    ],
    'list_sql' => 'SELECT s.*, c.nama_penyebab, c.kode AS kode_penyebab
                   FROM solutions s LEFT JOIN causes c ON c.id = s.cause_id
                   ORDER BY c.kode, s.urutan, s.id',
    'list_columns' => [
        ['label' => 'Penyebab', 'value' => fn (array $r): string => ($r['kode_penyebab'] ?? '-') . ' - ' . ($r['nama_penyebab'] ?? '-')],
        ['label' => 'Langkah', 'value' => fn (array $r): string => excerpt($r['langkah'], 80)],
        ['label' => 'Urutan', 'value' => fn (array $r): string => (string) $r['urutan']],
        ['label' => 'Teknisi', 'value' => fn (array $r): string => (int) $r['butuh_teknisi'] === 1 ? 'Ya' : 'Tidak'],
    ],
    'delete_message' => 'Hapus langkah solusi ini?',
];

require __DIR__ . '/_crud.php';
