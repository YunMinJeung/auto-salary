<?php
// 임시 스크린샷 업로드 엔드포인트 — 작업 완료 후 삭제
define('SS_TOKEN', 'ss_upload_2026_payclock');
define('SS_DIR', __DIR__ . '/img/ss/');

header('Content-Type: application/json; charset=utf-8');

if (($_POST['token'] ?? '') !== SS_TOKEN) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'forbidden']);
    exit;
}

$name = preg_replace('/[^a-z0-9_\-]/', '', $_POST['name'] ?? '');
if (!$name) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'no name']);
    exit;
}

$dataUrl = $_POST['data'] ?? '';
if (!preg_match('/^data:image\/(?:png|jpeg);base64,/', $dataUrl)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid data']);
    exit;
}

$base64 = preg_replace('/^data:image\/(?:png|jpeg);base64,/', '', $dataUrl);
$bytes  = base64_decode($base64, true);
if ($bytes === false || strlen($bytes) < 100) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'decode failed']);
    exit;
}

if (!is_dir(SS_DIR)) {
    mkdir(SS_DIR, 0755, true);
}

$ext  = str_contains($dataUrl, 'image/png') ? 'png' : 'jpg';
$file = SS_DIR . $name . '.' . $ext;
file_put_contents($file, $bytes);

echo json_encode(['ok' => true, 'file' => 'img/ss/' . $name . '.' . $ext]);
