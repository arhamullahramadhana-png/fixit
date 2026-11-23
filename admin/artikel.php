<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$opsiKategori = [];
foreach ($repo->categories() as $kategori) {
    $opsiKategori[(int) $kategori['id']] = $kategori['nama_kategori'];
}

$crud = [
    'table'    => 'articles',
    'judul'    => 'Artikel',
    'subjudul' => 'Panduan perbaikan yang tampil di halaman artikel.',
    'order'    => 'dibuat_pada DESC',
    'fields'   => [
        [
            'name' => 'category_id',
            'label' => 'Kategori',
            'type' => 'select',
            'options' => $opsiKategori,
            'empty' => 'Tanpa kategori',
            'nullable' => true,
        ],
        [
            'name' => 'judul',
            'label' => 'Judul artikel',
            'type' => 'text',
            'required' => true,
            'slug_source' => 'field-slug',
        ],
        [
            'name' => 'slug',
            'label' => 'Slug',
            'type' => 'text',
            'required' => true,
            'hint' => 'Dipakai pada URL artikel. Huruf kecil dan tanda hubung.',
        ],
        [
            'name' => 'isi',
            'label' => 'Isi artikel',
            'type' => 'textarea',
            'rows' => 14,
            'required' => true,
            'hint' => 'Boleh memakai tag HTML sederhana seperti <p>, <h2>, <ul>, dan <li>.',
        ],
        [
            'name' => 'gambar',
            'label' => 'Gambar utama',
            'type' => 'file',
            'hint' => 'Opsional. JPG, PNG, WebP, atau GIF maksimal 2 MB.',
        ],
    ],
    'list_sql' => 'SELECT a.*, c.nama_kategori
                   FROM articles a LEFT JOIN categories c ON c.id = a.category_id
                   ORDER BY a.dibuat_pada DESC, a.id DESC',
    'list_columns' => [
        ['label' => 'Judul', 'value' => fn (array $r): string => $r['judul']],
        ['label' => 'Kategori', 'value' => fn (array $r): string => (string) ($r['nama_kategori'] ?? '-')],
        ['label' => 'Dibuat', 'value' => fn (array $r): string => format_tanggal($r['dibuat_pada'])],
    ],
    'delete_message' => 'Hapus artikel ini?',
];

require __DIR__ . '/_crud.php';
