<?php
define('SS_TOKEN', 'ss_upload_2026_payclock');
// auto_salary/public/img/ss/ — 심볼릭 링크 public_html/img 경유로 서비스됨
define('SS_DIR', dirname(__DIR__) . '/img/ss/');
header('Content-Type: application/json; charset=utf-8');
if (($_POST['token'] ?? '') !== SS_TOKEN) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'forbidden']); exit; }
$name = preg_replace('/[^a-z0-9_\-]/', '', $_POST['name'] ?? '');
if (!$name) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'no name']); exit; }
$dataUrl = $_POST['data'] ?? '';
if (!preg_match('/^data:image\/(?:png|jpeg);base64,/', $dataUrl)) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'invalid']); exit; }
$bytes = base64_decode(preg_replace('/^data:image\/(?:png|jpeg);base64,/', '', $dataUrl), true);
if (!$bytes || strlen($bytes) < 100) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'decode']); exit; }
if (!is_dir(SS_DIR)) mkdir(SS_DIR, 0755, true);
$ext = str_contains($dataUrl,'image/png') ? 'png' : 'jpg';
file_put_contents(SS_DIR . $name . '.' . $ext, $bytes);
echo json_encode(['ok'=>true,'file'=>'img/ss/'.$name.'.'.$ext,'dir'=>SS_DIR]);
