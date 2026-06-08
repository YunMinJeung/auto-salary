<?php
// ─── DB 설정 ──────────────────────────────────────────────
// 이 파일을 app/config.php 로 복사한 뒤 아래 값을 수정하세요.
define('DB_HOST', 'localhost');
define('DB_NAME', 'auto_salary');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ─── 앱 경로 ──────────────────────────────────────────────
// 서브디렉토리에 설치 시 예: '/auto_salary/'
define('BASE_URL', '/');

// ─── 법정 기준값 ──────────────────────────────────────────
define('DEFAULT_MIN_WAGE', 10320);
define('NIGHT_START_HOUR', 22);
define('NIGHT_END_HOUR', 6);
