<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$crud = [
    'table'    => 'causes',
    'judul'    => 'Penyebab',
    'subjudul' => 'Daftar kemungkinan penyebab yang bisa muncul pada hasil diagnosis.',
    'order'    => 'kode',
    'fields'   => [
        [
            'name' => 'kode',
            'label' => 'Kode penyebab',
            'type' => 'text',
            'required' => true,
            'hint' => 'Kode unik, misalnya C01.',
        ],
        [
            'name' => 'nama_penyebab',
            'label' => 'Nama penyebab',
            'type' => 'text',
            'required' => true,
        ],
        [
            'name' => 'deskripsi',
            'label' => 'Deskripsi singkat',
            'type' => 'textarea',
            'rows' => 3,
            'required' => true,
            'hint' => 'Jelaskan penyebab dengan bahasa yang mudah dipahami pengguna awam.',
        ],
        [
            'name' => 'tingkat_risiko',
            'label' => 'Tingkat risiko',
            'type' => 'select',
            'options' => [
                'rendah' => 'Rendah',
                'sedang' => 'Sedang',
                'tinggi' => 'Tinggi',
            ],
            'default' => 'sedang',
            'required' => true,
        ],
    ],
    'list_sql' => 'SELECT c.*, (SELECT COUNT(*) FROM rules r WHERE r.cause_id = c.id) AS jumlah_aturan
                   FROM causes c ORDER BY c.kode',
    'list_columns' => [
        ['label' => 'Kode', 'value' => fn (array $r): string => $r['kode']],
        ['label' => 'Penyebab', 'value' => fn (array $r): string => $r['nama_penyebab']],
        ['label' => 'Risiko', 'value' => fn (array $r): string => ucfirst($r['tingkat_risiko'])],
        ['label' => 'Aturan', 'value' => fn (array $r): string => (string) $r['jumlah_aturan']],
    ],
    'delete_message' => 'Hapus penyebab ini? Aturan dan solusi yang terkait juga akan terhapus.',
];

require __DIR__ . '/_crud.php';
