<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$opsiKategori = [];
foreach ($repo->categories() as $kategori) {
    $opsiKategori[(int) $kategori['id']] = $kategori['nama_kategori'];
}

$crud = [
    'table'    => 'symptoms',
    'judul'    => 'Gejala',
    'subjudul' => 'Daftar gejala yang dapat dipilih pengguna. Kata kunci dipakai chatbot untuk mengenali cerita bebas.',
    'order'    => 'kode',
    'fields'   => [
        [
            'name' => 'category_id',
            'label' => 'Kategori',
            'type' => 'select',
            'options' => $opsiKategori,
            'required' => true,
        ],
        [
            'name' => 'kode',
            'label' => 'Kode gejala',
            'type' => 'text',
            'required' => true,
            'hint' => 'Kode unik, misalnya S01.',
        ],
        [
            'name' => 'pertanyaan',
            'label' => 'Pernyataan gejala',
            'type' => 'textarea',
            'rows' => 2,
            'required' => true,
            'hint' => 'Tulis sebagai pernyataan yang bisa dicentang pengguna, misalnya "Layar tetap hitam padahal laptop menyala".',
        ],
        [
            'name' => 'kata_kunci',
            'label' => 'Kata kunci chatbot',
            'type' => 'textarea',
            'rows' => 3,
            'hint' => 'Pisahkan dengan koma. Contoh: layar hitam, blank, layar gelap.',
        ],
        [
            'name' => 'aktif',
            'label' => 'Aktif dan bisa dipilih',
            'type' => 'checkbox',
            'default' => 1,
        ],
    ],
    'list_sql' => 'SELECT s.*, c.nama_kategori
                   FROM symptoms s LEFT JOIN categories c ON c.id = s.category_id
                   ORDER BY c.urutan, s.kode',
    'list_columns' => [
        ['label' => 'Kode', 'value' => fn (array $r): string => $r['kode']],
        ['label' => 'Gejala', 'value' => fn (array $r): string => $r['pertanyaan']],
        ['label' => 'Kategori', 'value' => fn (array $r): string => (string) ($r['nama_kategori'] ?? '-')],
        ['label' => 'Status', 'value' => fn (array $r): string => (int) $r['aktif'] === 1 ? 'Aktif' : 'Nonaktif'],
    ],
    'delete_message' => 'Hapus gejala ini? Relasi aturan yang memakai gejala ini juga akan dihapus.',
];

require __DIR__ . '/_crud.php';
