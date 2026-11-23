<?php

/**
 * Konfigurasi koneksi database FIXIT.
 * Mendukung variabel environment (Railway, Docker, Cloud) dengan fallback ke XAMPP lokal.
 */

$host = 'localhost';
$port = '3306';
$user = 'root';
$pass = '';
$db   = 'fixit';

if ($dbUrl = getenv('DATABASE_URL')) {
    $parts = parse_url($dbUrl);
    if (!empty($parts['host'])) {
        $host = $parts['host'];
        $port = (string) ($parts['port'] ?? '3306');
        $user = $parts['user'] ?? 'root';
        $pass = $parts['pass'] ?? '';
        $db   = ltrim($parts['path'] ?? 'fixit', '/');
    }
} else {
    $host = getenv('MYSQLHOST') ?: (getenv('DB_HOST') ?: 'localhost');
    $port = (string) (getenv('MYSQLPORT') ?: (getenv('DB_PORT') ?: '3306'));
    $user = getenv('MYSQLUSER') ?: (getenv('DB_USER') ?: 'root');
    $pass = getenv('MYSQLPASSWORD') !== false ? (string) getenv('MYSQLPASSWORD') : (getenv('DB_PASS') !== false ? (string) getenv('DB_PASS') : '');
    $db   = getenv('MYSQLDATABASE') ?: (getenv('DB_NAME') ?: 'fixit');
}

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
    $pdo = new PDO(
        $dsn,
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );

    // Otomatis inisialisasi tabel jika database baru/kosong (misal saat deploy awal di cloud/Railway)
    try {
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'users'")->fetch();
        if (!$tableCheck) {
            $sqlFile = dirname(__DIR__) . '/database/fixit.sql';
            if (file_exists($sqlFile)) {
                $sql = file_get_contents($sqlFile);
                if ($sql) {
                    $sql = preg_replace('/CREATE\s+DATABASE[^;]+;/i', '', $sql);
                    $sql = preg_replace('/USE\s+[^;]+;/i', '', $sql);
                    $pdo->exec($sql);
                }
            }
        }
    } catch (Throwable $migrationError) {
        error_log('Database auto-init notice: ' . $migrationError->getMessage());
    }
} catch (PDOException $e) {
    http_response_code(500);
    exit(
        'Koneksi database gagal. Pastikan MySQL berjalan dan database <strong>' . htmlspecialchars($db) . '</strong> sudah diimpor. '
        . 'Detail: ' . htmlspecialchars($e->getMessage())
    );
}
