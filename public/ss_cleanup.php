<?php
if (($_GET['token'] ?? '') !== 'ss_upload_2026_payclock') { http_response_code(403); exit('forbidden'); }
$dir = __DIR__ . '/';
$deleted = [];
foreach (['upload_ss.php', 'ss_cleanup.php'] as $f) {
    if (file_exists($dir . $f) && unlink($dir . $f)) $deleted[] = $f;
}
echo 'deleted: ' . implode(', ', $deleted);
