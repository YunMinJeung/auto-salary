<?php
declare(strict_types=1);

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax',
]);
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

    // ─── QR 스캔 라우트 (로그인 여부 무관 — 컨트롤러 내부에서 처리) ─
    if ($c === 'clock') {
        $ctrl = new ClockQrController();
        switch ($a) {
            case 'clock_in':  $ctrl->clockIn();  break;
            case 'clock_out': $ctrl->clockOut(); break;
            default:          $ctrl->scan();     break;
        }
        exit;
    }

    // ─── 직원 초대 라우트 (수락은 로그인 불필요) ─────────────
    if ($c === 'invite') {
        $ctrl = new InviteController();
        switch ($a) {
            case 'create': $ctrl->create(); break;  // POST, requireOwner 내부 처리
            case 'show':   $ctrl->show();   break;  // GET, requireOwner 내부 처리
            case 'cancel': $ctrl->cancel(); break;  // POST, requireOwner 내부 처리
            case 'form':   $ctrl->form();   break;  // GET, requireOwner 내부 처리
            default:       $ctrl->accept(); break;  // 공개 접근 허용
        }
        exit;
    }

    // ─── 서비스 소개 랜딩 / 홈 (공개) ────────────────────────
    // 비로그인 사용자가 '/' 또는 ?c=home 접근 시 랜딩 페이지 노출.
    // 로그인 사용자는 HomeController 내부에서 역할별 대시보드로 리디렉트.
    if ($c === 'home' || ($c === '' && !Auth::check())) {
        (new HomeController())->index();
        exit;
    }

    // ─── 가입 흐름 (공개) ────────────────────────────────────
    if ($c === 'signup') {
        $ctrl = new SignupController();
        switch ($a) {
            case 'owner':    $ctrl->owner();    break;
            case 'employee': $ctrl->employee(); break;
            default:         $ctrl->index();    break;
        }
        exit;
    }

    // ─── 개인정보처리방침 / 이용약관 (공개) ──────────────────
    if ($c === 'privacy') {
        (new PrivacyController())->index();
        exit;
    }
    if ($c === 'terms') {
        (new PrivacyController())->terms();
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
            case 'object_correction': $ctrl->objectCorrection(); break;
            case 'select_store':   $ctrl->selectStore();        break;
            case 'payslip':            $ctrl->payslip();            break;
            case 'payslips':           $ctrl->payslips();           break;
            case 'payslip_show':       (new PayslipController())->show(); break;
            case 'income':             $ctrl->income();             break;
            case 'respond':            (new LaborRiskController())->employeeRespond(); break;
            case 'change_accept':      (new AttendanceChangeController())->accept(); break;
            case 'change_object':      (new AttendanceChangeController())->object(); break;
            case 'acceptcounter':      (new AttendanceChangeController())->acceptCounter(); break;
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

    // super_admin이 owner 페이지 접근 시 어드민 패널로 리다이렉트
    if (Auth::isSuperAdmin() && $c !== 'admin') {
        redirect(url('admin'));
    }

    switch ($c) {
        case '':
        case 'dashboard':
            $settings    = Setting::get();
            $employees   = Employee::all();
            $recentLogs  = WorkLog::recentAll(10);
            $alertCounts = LaborRiskAlert::countOpen(Auth::ownerId());
            $pendingChangeCnt = AttendanceChangeRequest::pendingCount(Auth::storeId());
            $correctionPendingCnt = AttendanceCorrectionRequest::pendingCount(Auth::storeId());
            $objectionCount   = AttendanceChangeRequest::objectionCount(Auth::storeId());
            $todayAttendance  = AttendanceLog::todayForStore(Auth::storeId(), date('Y-m-d'));
            $todayWorkingCount = 0;
            foreach ($todayAttendance as $ta) {
                if (in_array($ta['status'] ?? '', ['working', 'completed', 'corrected'], true)) {
                    $todayWorkingCount++;
                }
            }

            // 오늘 스케줄 + 지각 분 계산 (대시보드 배지용)
            $todayScheds = Schedule::allForDate(Auth::ownerId(), Auth::storeId(), date('Y-m-d'));
            $dashSchedByMember = [];
            foreach ($todayScheds as $_s) {
                $dashSchedByMember[(int)$_s['employee_id']] = $_s;
            }
            foreach ($todayAttendance as &$_ta) {
                $_ta['late_minutes'] = 0;
                $_ta['sched_start']  = null;
                $mid = (int)($_ta['store_member_id'] ?? 0);
                $sc  = $dashSchedByMember[$mid] ?? null;
                if ($sc) {
                    $_ta['sched_start'] = substr($sc['start_time'], 0, 5);
                    $effIn = $_ta['effective_clock_in_at'] ?? $_ta['original_clock_in_at'] ?? null;
                    if ($effIn) {
                        $schedTs = strtotime(date('Y-m-d') . ' ' . $sc['start_time']);
                        $diff    = (int)((strtotime($effIn) - $schedTs) / 60);
                        if ($diff > 5) $_ta['late_minutes'] = $diff;
                    }
                }
            }
            unset($_ta);

            // ── 차트 데이터 ──────────────────────────────────────
            // 월별 인건비 추이 (최근 6개월) — payroll_results 집계
            $monthlyPayrollTrend = DB::fetchAll(
                "SELECT DATE_FORMAT(period_start, '%Y-%m') AS ym, SUM(total_pay) AS total
                   FROM payroll_results
                  WHERE owner_id = ?
                    AND period_start >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                  GROUP BY ym
                  ORDER BY ym ASC",
                [Auth::ownerId()]
            );

            // 직원별 이번 주 유급 근무시간 (work_logs.employee_id → store_members.id)
            $weekStart = date('Y-m-d', strtotime('monday this week'));
            $weekEnd   = date('Y-m-d', strtotime('sunday this week'));
            $weeklyHours = DB::fetchAll(
                "SELECT sm.name,
                        SUM(TIME_TO_SEC(TIMEDIFF(wl.end_time, wl.start_time)) / 60
                            - COALESCE(wl.break_minutes, 0)) AS total_paid_minutes
                   FROM work_logs wl
                   JOIN store_members sm ON sm.id = wl.employee_id
                  WHERE wl.owner_id = ? AND wl.store_id = ?
                    AND wl.work_date BETWEEN ? AND ? AND wl.is_absent = 0
                  GROUP BY sm.id, sm.name
                  ORDER BY sm.name ASC",
                [Auth::ownerId(), Auth::storeId(), $weekStart, $weekEnd]
            );

            render('dashboard', [
                'title'                => '대시보드',
                'settings'             => $settings,
                'employees'            => $employees,
                'recentLogs'           => $recentLogs,
                'alertCounts'          => $alertCounts,
                'pendingChangeCnt'     => $pendingChangeCnt,
                'correctionPendingCnt' => $correctionPendingCnt,
                'objectionCount'       => $objectionCount,
                'todayAttendance'      => $todayAttendance,
                'todayWorkingCount'    => $todayWorkingCount,
                'monthlyPayrollTrend'  => $monthlyPayrollTrend,
                'weeklyHours'          => $weeklyHours,
                'weekStart'            => $weekStart,
                'weekEnd'              => $weekEnd,
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
                case 'export': $ctrl->export(); break;
                default:       $ctrl->index();  break;
            }
            break;

        case 'payroll':
            $ctrl = new PayrollController();
            switch ($a) {
                case 'monthly':    $ctrl->monthly();   break;
                case 'payslip':    $ctrl->payslip();   break;
                case 'export_csv': $ctrl->exportCsv(); break;
                case 'update_ins_status': $ctrl->updateInsStatus(); break;
                default:           $ctrl->weekly();    break;
            }
            break;

        case 'payslip':
            $ctrl = new PayslipController();
            switch ($a) {
                case 'show':            $ctrl->show();           break;
                case 'issue':           $ctrl->issue();          break;
                case 'preview_monthly': $ctrl->previewMonthly(); break;
                case 'issue_monthly':   $ctrl->issueMonthly();   break;
                case 'correct':         $ctrl->correct();        break;
                case 'cancel':          $ctrl->cancel();         break;
                default:                $ctrl->index();          break;
            }
            break;

        case 'qr':
            $ctrl = new QrController();
            switch ($a) {
                case 'generate': $ctrl->generate(); break;
                case 'revoke':   $ctrl->revoke();   break;
                case 'pdf':      $ctrl->pdf();      break;
                default:         $ctrl->index();    break;
            }
            break;

        case 'severance':
            $ctrl = new SeveranceController();
            $ctrl->index();
            break;

        case 'schedules':
            $ctrl = new ScheduleController();
            switch ($a) {
                case 'create': $ctrl->create(); break;
                case 'edit':   $ctrl->edit();   break;
                case 'delete': $ctrl->delete(); break;
                default:       $ctrl->index();  break;
            }
            break;

        case 'leaves':
            $ctrl = new LeaveController();
            switch ($a) {
                case 'create': $ctrl->create(); break;
                case 'edit':   $ctrl->edit();   break;
                case 'delete': $ctrl->delete(); break;
                default:       $ctrl->index();  break;
            }
            break;

        case 'labor_risk':
            $ctrl = new LaborRiskController();
            switch ($a) {
                case 'scan':        $ctrl->scan();        break;
                case 'acknowledge': $ctrl->acknowledge(); break;
                case 'resolve':     $ctrl->resolve();     break;
                case 'ignore':      $ctrl->ignore();      break;
                default:            $ctrl->index();       break;
            }
            break;

        case 'settings':
            $ctrl = new SettingController();
            switch ($a) {
                case 'min_wage_save':   $ctrl->minWageSave();   break;
                case 'min_wage_delete': $ctrl->minWageDelete(); break;
                default:                $ctrl->index();         break;
            }
            break;

        case 'store':
            $ctrl = new StoreController();
            switch ($a) {
                case 'switch': $ctrl->switch(); break;
                default:       $ctrl->create(); break;
            }
            break;

        // ─── 출퇴근 관련 (점주) ──────────────────────────────
        case 'attendance':
            $ctrl = new OwnerAttendanceController();
            switch ($a) {
                case 'add_log':            $ctrl->addLog();             break;
                case 'edit_log':           $ctrl->editLog();            break;
                case 'corrections':        $ctrl->corrections();        break;
                case 'approve_correction': $ctrl->approveCorrection();  break;
                case 'reject_correction':  $ctrl->rejectCorrection();   break;
                case 'reapprove_correction':    $ctrl->reapproveCorrection();    break;
                case 'final_reject_correction': $ctrl->finalRejectCorrection();  break;
                default:                   $ctrl->index();              break;
            }
            break;

        // ─── 출퇴근 수정 요청 (점주) ─────────────────────────
        case 'attendance_change':
            $ctrl = new AttendanceChangeController();
            switch ($a) {
                case 'forceconfirm':    $ctrl->forceConfirm();    break;
                case 'resolve':         $ctrl->resolve();         break;
                case 'objections':      $ctrl->objections();      break;
                case 'acceptobjection': $ctrl->acceptObjection(); break;
                case 'rejectobjection': $ctrl->rejectObjection(); break;
                case 'counterpropose':  $ctrl->counterPropose();  break;
                default:                $ctrl->index();           break;
            }
            break;

        // ─── 직원 계정 관리 (점주) ───────────────────────────
        case 'members':
            $ctrl = new StoreMemberController();
            switch ($a) {
                case 'add':                $ctrl->add();              break;
                case 'create':             $ctrl->create();           break;
                case 'link_account':       $ctrl->linkAccount();      break;
                case 'edit':               $ctrl->edit();             break;
                case 'delete':             $ctrl->delete();           break;
                case 'contract':           $ctrl->contract();         break;
                case 'contract_view':      $ctrl->contractView();     break;
                case 'minor_consent':      $ctrl->minorConsent();     break;
                case 'minor_consent_view': $ctrl->minorConsentView(); break;
                default:                   $ctrl->index();            break;
            }
            break;

        // ─── 서비스 운영자(SUPER_ADMIN) 관리자 페이지 ───────────
        case 'admin':
            $ctrl = new AdminController();
            switch ($a) {
                case 'businesses':      $ctrl->businesses();     break;
                case 'business_detail': $ctrl->businessDetail(); break;
                case 'business_update': $ctrl->businessUpdate(); break;
                case 'users':           $ctrl->users();          break;
                case 'user_detail':     $ctrl->userDetail();     break;
                case 'user_update':     $ctrl->userUpdate();     break;
                case 'logs':            $ctrl->logs();           break;
                case 'audit':           $ctrl->audit();          break;
                case 'tickets':         $ctrl->tickets();        break;
                case 'ticket_detail':   $ctrl->ticketDetail();   break;
                case 'ticket_update':   $ctrl->ticketUpdate();   break;
                case 'billing':         $ctrl->billing();        break;
                case 'billing_update':  $ctrl->billingUpdate();  break;
                case 'standards':       $ctrl->standards();      break;
                case 'standard_form':   $ctrl->standardForm();   break;
                default:                $ctrl->index();          break;
            }
            break;

        default:
            http_response_code(404);
            echo '<h1>페이지를 찾을 수 없습니다.</h1>';
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo '서버 오류가 발생했습니다.';
}
