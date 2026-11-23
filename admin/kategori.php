<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$crud = [
    'table'      => 'categories',
    'judul'      => 'Kategori',
    'subjudul'   => 'Kelompokkan gejala berdasarkan jenis kerusakan.',
    'order'      => 'urutan, id',
    'fields'     => [
        [
            'name' => 'nama_kategori',
            'label' => 'Nama kategori',
            'type' => 'text',
            'required' => true,
            'slug_source' => 'field-slug',
        ],
        [
            'name' => 'slug',
            'label' => 'Slug',
            'type' => 'text',
            'required' => true,
            'hint' => 'Dipakai pada URL dan ikon. Huruf kecil tanpa spasi, misalnya layar atau daya.',
        ],
        [
            'name' => 'ikon',
            'label' => 'Ikon',
            'type' => 'select',
            'options' => [
                'monitor' => 'Monitor / layar',
                'battery' => 'Baterai',
                'gauge'   => 'Gauge / performa',
                'disk'    => 'Disk / penyimpanan',
                'wifi'    => 'Wi-Fi / jaringan',
            ],
            'default' => 'monitor',
            'required' => true,
        ],
        [
            'name' => 'urutan',
            'label' => 'Urutan tampil',
            'type' => 'number',
            'default' => 0,
            'min' => 0,
        ],
    ],
    'list_columns' => [
        ['label' => 'Nama', 'value' => fn (array $r): string => $r['nama_kategori']],
        ['label' => 'Slug', 'value' => fn (array $r): string => $r['slug']],
        ['label' => 'Ikon', 'value' => fn (array $r): string => $r['ikon']],
        ['label' => 'Urutan', 'value' => fn (array $r): string => (string) $r['urutan']],
    ],
    'delete_message' => 'Hapus kategori ini? Gejala dan artikel di dalamnya juga akan terhapus atau kehilangan kategorinya.',
];

require __DIR__ . '/_crud.php';
