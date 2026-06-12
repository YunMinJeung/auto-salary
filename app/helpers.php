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

function render_auth(string $view, array $viewData = []): void
{
    render($view, $viewData, 'auth_layout');
}

function render_employee(string $view, array $viewData = []): void
{
    render($view, $viewData, 'employee_mobile_layout');
}

function render_landing(string $view, array $viewData = []): void
{
    render($view, $viewData, 'landing_layout');
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

/** 관리자 화면 상태/역할 배지 (admin_layout.php 의 .badge-* 클래스 사용) */
function adminBadge(?string $value, ?string $label = null): string
{
    $value = (string) $value;
    if ($value === '') {
        return '<span class="badge bg-light text-muted">-</span>';
    }
    static $ko = [
        // 사업장 상태
        'ACTIVE'            => '정상',
        'TRIAL'             => '체험중',
        'PAYMENT_PENDING'   => '결제대기',
        'SUSPENDED'         => '정지',
        'CANCEL_REQUESTED'  => '해지신청',
        'INACTIVE'          => '비활성',
        // 요금제
        'FREE'              => '무료',
        'STARTER'           => '스타터',
        'BUSINESS'          => '비즈니스',
        'PRO'               => '프로',
        // 구독 상태
        'PAID'              => '결제완료',
        'FAILED'            => '결제실패',
        'CANCEL_SCHEDULED'  => '해지예정',
        'CANCELLED'         => '해지됨',
        // 문의 상태
        'OPEN'              => '접수',
        'IN_PROGRESS'       => '처리중',
        'ANSWERED'          => '답변완료',
        'HOLD'              => '보류',
        'CLOSED'            => '종료',
        // 역할
        'super_admin'       => '최고관리자',
        'owner'             => '사장',
        'admin'             => '관리자',
        'employee'          => '직원',
        // 계정 상태
        'ACTIVE_account'    => '정상',
        'SUSPENDED_account' => '정지',
    ];
    $displayLabel = $label ?? ($ko[$value] ?? $value);
    return '<span class="badge badge-' . h($value) . '">' . h($displayLabel) . '</span>';
}

/** 관리자 코드값 → 한글 레이블 (배지 없이 텍스트만) */
function adminLabel(string $value): string
{
    static $ko = [
        'ACTIVE'            => '정상',
        'TRIAL'             => '체험중',
        'PAYMENT_PENDING'   => '결제대기',
        'SUSPENDED'         => '정지',
        'CANCEL_REQUESTED'  => '해지신청',
        'INACTIVE'          => '비활성',
        'FREE'              => '무료',
        'STARTER'           => '스타터',
        'BUSINESS'          => '비즈니스',
        'PRO'               => '프로',
        'PAID'              => '결제완료',
        'FAILED'            => '결제실패',
        'CANCEL_SCHEDULED'  => '해지예정',
        'CANCELLED'         => '해지됨',
        'OPEN'              => '접수',
        'IN_PROGRESS'       => '처리중',
        'ANSWERED'          => '답변완료',
        'HOLD'              => '보류',
        'CLOSED'            => '종료',
        'super_admin'       => '최고관리자',
        'owner'             => '사장',
        'admin'             => '관리자',
        'employee'          => '직원',
    ];
    return $ko[$value] ?? $value;
}

/**
 * 4대보험 근로자 공제액 계산 (2026년 요율 기준).
 * 국민연금 4.75% / 건강보험 3.595% / 장기요양 건강보험료×13.14% / 고용보험 0.9%
 * 산재보험은 사용자 전액 부담이므로 공제 없음.
 * 10원 미만 절사 (국민연금 기준소득월액 1,000원 미만 절사 후 적용 포함).
 *
 * @param  int   $grossPay  세전 급여 (원)
 * @param  array $settings  DB settings 레코드
 * @return array            항목별 공제액 + 합계
 */
function calculateInsuranceDeductions(int $grossPay, array $settings): array
{
    // 국민연금: 기준소득월액 1,000원 미만 절사 후 적용
    // 2026년 기준소득월액 하한: 400,000원 / 상한: 6,370,000원 (1~6월 기준)
    $pensionBase = (int)(floor($grossPay / 1000) * 1000);
    $pensionBase = max(400000, min(6370000, $pensionBase));
    $pension = ($settings['apply_national_pension'] ?? 1)
        ? (int)(floor($pensionBase * 0.045 / 10) * 10) : 0;

    // 건강보험: 10원 미만 절사
    $health = ($settings['apply_health_insurance'] ?? 1)
        ? (int)(floor($grossPay * 0.03595 / 10) * 10) : 0;

    // 장기요양: 건강보험료 기준 13.14%, 10원 미만 절사
    $ltCare = ($settings['apply_health_insurance'] ?? 1)
        ? (int)(floor($health * 0.1314 / 10) * 10) : 0;

    // 고용보험: 10원 미만 절사
    $employ = ($settings['apply_employment_insurance'] ?? 1)
        ? (int)(floor($grossPay * 0.009 / 10) * 10) : 0;

    $total = $pension + $health + $ltCare + $employ;

    return [
        'national_pension'     => $pension,
        'health_insurance'     => $health,
        'long_term_care'       => $ltCare,
        'employment_insurance' => $employ,
        'total'                => $total,
    ];
}

// ─── GPS 출퇴근 위치 검증 ─────────────────────────────────

/** 두 좌표 간 거리(미터). Haversine 공식. */
function gps_haversine(float $lat1, float $lng1, float $lat2, float $lng2): int
{
    $R    = 6371000;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a    = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
    return (int)round($R * 2 * atan2(sqrt($a), sqrt(1 - $a)));
}

/**
 * GPS 인증이 켜진 매장이면 현재 좌표가 반경 내인지 검증.
 * 항상 배열 반환. error 키가 null이면 통과.
 */
function gps_validate(int $storeId): array
{
    $result = [
        'error'           => null,
        'geo_status'      => 'NOT_CHECKED',
        'distance_meters' => null,
        'biz_lat'         => null,
        'biz_lng'         => null,
        'allowed_radius'  => null,
        'employee_lat'    => null,
        'employee_lng'    => null,
        'accuracy'        => null,
        'geo_error_code'  => null,
    ];

    $store = DB::fetchOne(
        'SELECT gps_required, latitude, longitude, gps_radius FROM stores WHERE id=?',
        [$storeId]
    );
    if (!$store || !$store['gps_required']) {
        return $result;
    }

    $storeLat = (float)$store['latitude'];
    $storeLng = (float)$store['longitude'];
    $radius   = max(50, (int)($store['gps_radius'] ?? 200));

    $result['biz_lat']        = $storeLat;
    $result['biz_lng']        = $storeLng;
    $result['allowed_radius'] = $radius;

    if (!$storeLat && !$storeLng) {
        $result['geo_status'] = 'NOT_CONFIGURED';
        $result['error']      = '사업장 출퇴근 위치가 설정되지 않았습니다. 사장님에게 문의해 주세요.';
        return $result;
    }

    $geoError = trim($_POST['geo_error'] ?? '');
    $lat      = isset($_POST['latitude'])  && $_POST['latitude']  !== '' ? (float)$_POST['latitude']  : null;
    $lng      = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? (float)$_POST['longitude'] : null;
    $accuracy = isset($_POST['accuracy'])  && $_POST['accuracy']  !== '' ? (float)$_POST['accuracy']  : null;

    $result['employee_lat'] = $lat;
    $result['employee_lng'] = $lng;
    $result['accuracy']     = $accuracy;

    if ($geoError !== '') {
        $result['geo_status']     = 'GEO_ERROR';
        $result['geo_error_code'] = $geoError;
        $result['error']          = '위치 권한이 필요합니다. 브라우저에서 위치 권한을 허용한 뒤 다시 시도해 주세요.';
        return $result;
    }

    if ($lat === null || $lng === null) {
        $result['geo_status'] = 'FAILED_GPS_REQUIRED';
        $result['error']      = '위치 정보를 받지 못했습니다. 다시 시도해 주세요.';
        return $result;
    }

    if ($accuracy !== null && $accuracy > 150) {
        $result['geo_status'] = 'FAILED_LOW_ACCURACY';
        $result['error']      = "GPS 정확도가 낮습니다 ({$accuracy}m). Wi-Fi를 켜거나 잠시 후 다시 시도해 주세요.";
        return $result;
    }

    $distance = gps_haversine($storeLat, $storeLng, $lat, $lng);
    $result['distance_meters'] = (float)$distance;

    if ($distance > $radius) {
        $msg = "매장에서 너무 멀리 있습니다. (현재 {$distance}m / 허용 {$radius}m 이내)";
        if ($accuracy !== null && $accuracy > 50) {
            $msg .= ' GPS 정확도가 낮아 실제 위치와 차이가 있을 수 있습니다.';
        }
        $result['geo_status'] = 'FAILED_OUT_OF_RADIUS';
        $result['error']      = $msg;
        return $result;
    }

    $result['geo_status'] = 'PASSED';
    return $result;
}
