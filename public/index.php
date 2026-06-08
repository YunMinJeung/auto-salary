<?php
declare(strict_types=1);

session_start();

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH',  ROOT_PATH . '/app');

require APP_PATH . '/config.php';
require APP_PATH . '/db.php';
require APP_PATH . '/helpers.php';
require APP_PATH . '/Auth.php';

// 모델 / 컨트롤러 오토로드
spl_autoload_register(function (string $class): void {
    $paths = [
        APP_PATH . '/models/'      . $class . '.php',
        APP_PATH . '/controllers/' . $class . '.php',
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require $path;
            return;
        }
    }
});

$c = $_GET['c'] ?? '';
$a = $_GET['a'] ?? 'index';

// 유효한 문자만 허용 (경로 트래버설 방지)
$c = preg_replace('/[^a-z_]/', '', $c);
$a = preg_replace('/[^a-z_]/', '', $a);

try {
    // ─── 인증 라우트 (로그인 불필요) ─────────────────────────
    if ($c === 'auth') {
        $ctrl = new AuthController();
        switch ($a) {
            case 'register': $ctrl->register(); break;
            case 'logout':   $ctrl->logout();   break;
            default:         $ctrl->login();    break;
        }
        exit;
    }

    // ─── 알바생 전용 라우트 (employee 역할만) ────────────────
    if ($c === 'employee') {
        Auth::requireLogin();
        $ctrl = new EmployeeDashboardController();
        switch ($a) {
            case 'clock_in':           $ctrl->clockIn();            break;
            case 'clock_out':          $ctrl->clockOut();           break;
            case 'request_correction': $ctrl->requestCorrection();  break;
            default:                   $ctrl->index();              break;
        }
        exit;
    }

    // ─── 이하 모든 라우트는 점주 로그인 필요 ─────────────────
    Auth::requireLogin();

    // 알바생이 점주 페이지 접근 시 자신의 대시보드로 리다이렉트
    if (Auth::isEmployee()) {
        redirect(url('employee'));
    }

    switch ($c) {
        case '':
        case 'dashboard':
            $settings   = Setting::get();
            $employees  = Employee::all();
            $recentLogs = WorkLog::recentAll(10);
            render('dashboard', [
                'title'      => '대시보드',
                'settings'   => $settings,
                'employees'  => $employees,
                'recentLogs' => $recentLogs,
            ]);
            break;

        case 'employees':
            $ctrl = new EmployeeController();
            switch ($a) {
                case 'create': $ctrl->create(); break;
                case 'edit':   $ctrl->edit();   break;
                case 'delete': $ctrl->delete(); break;
                default:       $ctrl->index();  break;
            }
            break;

        case 'work_logs':
            $ctrl = new WorkLogController();
            switch ($a) {
                case 'create': $ctrl->create(); break;
                case 'edit':   $ctrl->edit();   break;
                case 'delete': $ctrl->delete(); break;
                default:       $ctrl->index();  break;
            }
            break;

        case 'payroll':
            $ctrl = new PayrollController();
            switch ($a) {
                case 'monthly':    $ctrl->monthly();   break;
                case 'export_csv': $ctrl->exportCsv(); break;
                default:           $ctrl->weekly();    break;
            }
            break;

        case 'severance':
            $ctrl = new SeveranceController();
            $ctrl->index();
            break;

        case 'settings':
            $ctrl = new SettingController();
            $ctrl->index();
            break;

        // ─── 출퇴근 관련 (점주) ──────────────────────────────
        case 'attendance':
            $ctrl = new OwnerAttendanceController();
            switch ($a) {
                case 'add_log':            $ctrl->addLog();             break;
                case 'corrections':        $ctrl->corrections();        break;
                case 'approve_correction': $ctrl->approveCorrection();  break;
                case 'reject_correction':  $ctrl->rejectCorrection();   break;
                default:                   $ctrl->index();              break;
            }
            break;

        // ─── 직원 계정 관리 (점주) ───────────────────────────
        case 'members':
            $ctrl = new StoreMemberController();
            switch ($a) {
                case 'create': $ctrl->create(); break;
                case 'edit':   $ctrl->edit();   break;
                case 'delete': $ctrl->delete(); break;
                default:       $ctrl->index();  break;
            }
            break;

        default:
            http_response_code(404);
            echo '<h1>페이지를 찾을 수 없습니다.</h1>';
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo '<h2>데이터베이스 오류</h2>';
    echo '<p>DB 접속 정보를 확인하세요: <code>app/config.php</code></p>';
    if (ini_get('display_errors')) {
        echo '<pre>' . h($e->getMessage()) . '</pre>';
    }
}
