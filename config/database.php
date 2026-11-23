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

$dbUrl = getenv('DATABASE_URL') ?: (getenv('MYSQL_URL') ?: (getenv('MYSQL_PRIVATE_URL') ?: null));

if ($dbUrl) {
    $parts = parse_url($dbUrl);
    if (!empty($parts['host'])) {
        $host = $parts['host'];
        $port = (string) ($parts['port'] ?? '3306');
        $user = $parts['user'] ?? 'root';
        $pass = $parts['pass'] ?? '';
        $db   = ltrim($parts['path'] ?? 'fixit', '/');
    }
} else {
    $host = getenv('MYSQLHOST') ?: (getenv('MYSQL_HOST') ?: (getenv('DB_HOST') ?: 'localhost'));
    $port = (string) (getenv('MYSQLPORT') ?: (getenv('MYSQL_PORT') ?: (getenv('DB_PORT') ?: '3306')));
    $user = getenv('MYSQLUSER') ?: (getenv('MYSQL_USER') ?: (getenv('DB_USER') ?: 'root'));
    $pass = getenv('MYSQLPASSWORD') !== false ? (string) getenv('MYSQLPASSWORD') : (getenv('MYSQL_PASSWORD') !== false ? (string) getenv('MYSQL_PASSWORD') : (getenv('DB_PASS') !== false ? (string) getenv('DB_PASS') : ''));
    $db   = getenv('MYSQLDATABASE') ?: (getenv('MYSQL_DATABASE') ?: (getenv('DB_NAME') ?: 'fixit'));
}

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    if (defined('PDO::MYSQL_ATTR_MULTI_STATEMENTS')) {
        $options[PDO::MYSQL_ATTR_MULTI_STATEMENTS] = true;
    }

    $pdo = new PDO($dsn, $user, $pass, $options);

    // Otomatis inisialisasi tabel jika database baru/kosong (misal saat deploy awal di cloud/Railway)
    try {
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'rules'")->fetch();
        if (!$tableCheck) {
            $sqlFile = dirname(__DIR__) . '/database/fixit.sql';
            if (file_exists($sqlFile)) {
                $sql = file_get_contents($sqlFile);
                if ($sql) {
                    $sql = preg_replace('/\bCREATE\s+DATABASE\b[^;]+;/is', '', $sql);
                    $sql = preg_replace('/\bUSE\b\s+[^;]+;/is', '', $sql);

                    $lines = explode("\n", $sql);
                    $clean = '';
                    foreach ($lines as $line) {
                        $trimmed = trim($line);
                        if (str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#')) {
                            continue;
                        }
                        $clean .= $line . "\n";
                    }

                    $queries = array_filter(array_map('trim', explode(';', $clean)));
                    foreach ($queries as $query) {
                        if ($query !== '') {
                            $pdo->exec($query);
                        }
                    }
                }
            }
        }
    } catch (Throwable $migrationError) {
        error_log('Database auto-init error: ' . $migrationError->getMessage());
        throw new RuntimeException('Inisialisasi database otomatis gagal: ' . $migrationError->getMessage(), 0, $migrationError);
    }
} catch (Throwable $e) {
    http_response_code(500);
    $isCloud = (bool) (getenv('PORT') || getenv('RAILWAY_ENVIRONMENT') || file_exists('/.dockerenv'));
    $hint = '';
    if ($isCloud && $host === 'localhost') {
        $hint = '<br><br><strong>Penyebab:</strong> Aplikasi berjalan di Railway tetapi variabel database belum dihubungkan ke service ini. '
              . 'Buka service <strong>fixit</strong> di Railway &rarr; tab <strong>Variables</strong> &rarr; tambahkan variabel <strong>DATABASE_URL</strong> '
              . 'yang mereferensikan service MySQL Anda.';
    }
    exit(
        'Koneksi database gagal. Pastikan MySQL berjalan dan database <strong>' . htmlspecialchars($db) . '</strong> sudah diimpor. '
        . 'Detail: ' . htmlspecialchars($e->getMessage())
        . $hint
    );
}
