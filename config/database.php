<?php

/**
 * Konfigurasi koneksi database FIXIT.
 * Sesuaikan nilai di bawah bila pengaturan MySQL Anda berbeda.
 */

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'fixit';

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$db};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    exit(
        'Koneksi database gagal. Pastikan MySQL berjalan dan database <strong>fixit</strong> sudah diimpor. '
        . 'Detail: ' . htmlspecialchars($e->getMessage())
    );
}
