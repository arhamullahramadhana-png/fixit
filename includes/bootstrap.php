<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Jakarta');

define('APP_ROOT', dirname(__DIR__));

// Tentukan URL dasar aplikasi, misalnya /pemrograman-web/fixit
if (!defined('BASE_URL')) {
    $base = '';
    if (!empty($_SERVER['DOCUMENT_ROOT'])) {
        $docRoot = str_replace('\\', '/', (string) realpath($_SERVER['DOCUMENT_ROOT']));
        $appRoot = str_replace('\\', '/', APP_ROOT);
        if ($docRoot !== '' && str_starts_with($appRoot, $docRoot)) {
            $base = substr($appRoot, strlen($docRoot));
        }
    }
    define('BASE_URL', rtrim($base, '/'));
}

require_once APP_ROOT . '/config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/classes/Repository.php';
require_once __DIR__ . '/classes/DiagnosisEngine.php';
require_once __DIR__ . '/classes/Chatbot.php';
require_once __DIR__ . '/auth.php';

$repo   = new Repository($pdo);
$engine = new DiagnosisEngine($pdo, $repo);
