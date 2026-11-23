<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

function json_keluar(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
    json_keluar(['ok' => false, 'error' => 'Sesi tidak valid. Muat ulang halaman.'], 419);
}

$userId = is_logged_in() ? (int) current_user()['id'] : null;
$chatbot = new Chatbot($repo, $engine, $userId);

try {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
        json_keluar(['ok' => true, 'data' => $chatbot->current()]);
    }

    $body = file_get_contents('php://input');
    $input = json_decode($body ?: '[]', true);
    if (!is_array($input)) {
        $input = [];
    }

    if (($input['action'] ?? '') === 'reset') {
        $state = $chatbot->reset();
    } elseif (isset($input['symptom_id'])) {
        $state = $chatbot->handleSymptom((int) $input['symptom_id']);
    } elseif (isset($input['category_id'])) {
        $state = $chatbot->handleCategory((int) $input['category_id']);
    } else {
        $pesan = isset($input['message']) ? trim((string) $input['message']) : '';
        if ($pesan === '') {
            json_keluar(['ok' => false, 'error' => 'Pesan kosong.'], 422);
        }
        $state = $chatbot->handleText($pesan);
    }

    json_keluar(['ok' => true, 'data' => $state]);
} catch (Throwable $e) {
    error_log('Chatbot error: ' . $e->getMessage());
    json_keluar(['ok' => false, 'error' => 'Terjadi kesalahan pada asisten. Coba lagi.'], 500);
}
