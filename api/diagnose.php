<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

function json_hasil(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_hasil(['ok' => false, 'error' => 'Gunakan metode POST.'], 405);
}

$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
$body = file_get_contents('php://input');
$input = json_decode($body ?: '[]', true);
if (!is_array($input)) {
    $input = [];
}

if (!hash_equals(csrf_token(), is_string($token) ? $token : '')) {
    json_hasil(['ok' => false, 'error' => 'Sesi tidak valid. Muat ulang halaman.'], 419);
}

$ids = $input['symptoms'] ?? $input['symptom_ids'] ?? [];
if (!is_array($ids)) {
    $ids = [];
}
$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

if (!$ids) {
    json_hasil(['ok' => false, 'error' => 'Pilih minimal satu gejala.'], 422);
}

$terdaftar = $repo->symptomsByIds($ids);
if (count($terdaftar) !== count($ids)) {
    json_hasil(['ok' => false, 'error' => 'Ada gejala yang tidak dikenali.'], 422);
}

$hasil = $engine->diagnose($ids);
foreach ($hasil['hasil'] as &$item) {
    $item['solusi'] = $engine->solusi((int) $item['cause_id']);
}
unset($item);

json_hasil([
    'ok'   => true,
    'data' => [
        'gejala' => $terdaftar,
        'hasil'  => $hasil['hasil'],
    ],
]);
