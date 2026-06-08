<?php

function render(string $view, array $viewData = [], string $layout = 'layout'): void
{
    extract($viewData, EXTR_SKIP);
    ob_start();
    include APP_PATH . '/views/' . $view . '.php';
    $content = ob_get_clean();
    if ($layout) {
        include APP_PATH . '/views/' . $layout . '.php';
    } else {
        echo $content;
    }
}

function h(?string $str): string
{
    return htmlspecialchars((string) $str, ENT_QUOTES, 'UTF-8');
}

function url(string $controller = '', string $action = 'index', array $params = []): string
{
    $query = [];
    if ($controller) {
        $query['c'] = $controller;
    }
    if ($action && $action !== 'index') {
        $query['a'] = $action;
    }
    $query = array_merge($query, $params);
    $qs = $query ? '?' . http_build_query($query) : '';
    return BASE_URL . 'index.php' . $qs;
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }
    $msg = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $msg;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('CSRF 토큰이 유효하지 않습니다.');
    }
}

// ─── 시간 포매팅 ──────────────────────────────────────────

function minutesToHoursStr(int $minutes): string
{
    $h = (int) floor($minutes / 60);
    $m = $minutes % 60;
    return $m > 0 ? "{$h}시간 {$m}분" : "{$h}시간";
}

function minutesToDecimal(int $minutes): float
{
    return round($minutes / 60, 2);
}

function formatWon(float $amount): string
{
    return number_format((int) round($amount)) . '원';
}

// 주어진 날짜가 속한 주의 월요일/일요일 반환
function getWeekRange(string $date): array
{
    $dt  = new DateTime($date);
    $dow = (int) $dt->format('N'); // 1=월 7=일
    $mon = clone $dt;
    $mon->modify('-' . ($dow - 1) . ' days');
    $sun = clone $mon;
    $sun->modify('+6 days');
    return [$mon->format('Y-m-d'), $sun->format('Y-m-d')];
}

function dayOfWeekKo(string $date): string
{
    $days = ['월', '화', '수', '목', '금', '토', '일'];
    $dt   = new DateTime($date);
    return $days[(int) $dt->format('N') - 1];
}

function isAboveMinWage(int $wage, int $minWage): bool
{
    return $wage >= $minWage;
}
