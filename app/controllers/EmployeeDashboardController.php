<?php
class EmployeeDashboardController
{
    public function index(): void
    {
        Auth::requireEmployee();
        $storeId      = Auth::storeId();
        $memberId     = Auth::storeMemberId();
        $today        = date('Y-m-d');

        $member       = StoreMember::find($memberId, $storeId);
        if (!$member) {
            Auth::logout();
            redirect(url('auth', 'login'));
        }

        // 점주 설정 (급여 공개)
        $storeSettings     = Setting::getByStoreId($storeId);
        $showPayToEmployee = (int)($storeSettings['show_pay_to_employee'] ?? 1);

        $working      = AttendanceLog::currentlyWorking($memberId);
        $recentLogs   = AttendanceLog::recentForMember($memberId, 14);

        [$weekStart]  = getWeekRange($today);
        $weekSummary  = AttendanceLog::weekSummary($memberId, $weekStart);
        $monthSummary = AttendanceLog::monthSummary($memberId, date('Y'), date('m'));

        $monthPayEst = (int) round((int)$monthSummary['total_minutes'] / 60 * $member['hourly_wage']);

        // 퇴근 직후 결과 (한 번만 표시)
        $clockOutResult = $_SESSION['clock_out_result'] ?? null;
        unset($_SESSION['clock_out_result']);

        $corrections = AttendanceCorrectionRequest::allForMember($memberId);

        $myAlerts     = LaborRiskAlert::forMember($memberId, (int)$member['owner_id']);
        $myObjections = EmployeeRecordResponse::forMember($memberId, (int)$member['owner_id']);

        $pendingChanges = AttendanceChangeRequest::forMember($memberId, $storeId);

        render_employee('employee/dashboard', [
            'title'             => '출퇴근',
            'member'            => $member,
            'working'           => $working,
            'recentLogs'        => $recentLogs,
            'weekSummary'       => $weekSummary,
            'monthSummary'      => $monthSummary,
            'monthPayEst'       => $monthPayEst,
            'showPayToEmployee' => $showPayToEmployee,
            'clockOutResult'    => $clockOutResult,
            'corrections'       => $corrections,
            'today'             => $today,
            'myAlerts'          => $myAlerts,
            'myObjections'      => $myObjections,
            'pendingChanges'    => $pendingChanges,
        ]);
    }

    public function clockIn(): void
    {
        Auth::requireEmployee();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(url('employee'));
        }
        verify_csrf();

        $gps = gps_validate(Auth::storeId());
        if ($gps['error'] !== null) {
            flash('error', $gps['error']);
            redirect(url('employee'));
        }

        $storeId  = Auth::storeId();
        $memberId = Auth::storeMemberId();

        $already = AttendanceLog::currentlyWorking($memberId);
        if ($already) {
            flash('error', '이미 출근 중입니다.');
        } else {
            $newId = AttendanceLog::clockIn($storeId, $memberId, 'pwa', Auth::id());
            if ($newId) {
                AttendanceLog::saveGpsSnapshot($newId, $gps + ['source' => 'APP_BUTTON']);
            }
            flash('success', '출근 완료!');
        }
        redirect(url('employee'));
    }

    public function clockOut(): void
    {
        Auth::requireEmployee();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(url('employee'));
        }
        verify_csrf();

        $gps = gps_validate(Auth::storeId());
        if ($gps['error'] !== null) {
            flash('error', $gps['error']);
            redirect(url('employee'));
        }

        $storeId  = Auth::storeId();
        $memberId = Auth::storeMemberId();
        $logId    = (int)($_POST['log_id'] ?? 0);

        if ($logId && AttendanceLog::clockOut($logId, $storeId, $memberId)) {
            AttendanceLog::saveGpsSnapshot($logId, $gps + ['source' => 'APP_BUTTON']);
            // attendance_logs → work_logs 자동 동기화
            AttendanceSyncService::sync($logId);

            // 퇴근 직후 오늘 급여 계산 (점주가 공개 허용한 경우만)
            $member        = StoreMember::find($memberId, $storeId);
            $storeSettings = $member ? Setting::getByStoreId($storeId) : [];
            if ($member && (int)($storeSettings['show_pay_to_employee'] ?? 1)) {
                $log = DB::fetchOne(
                    'SELECT original_clock_in_at, original_clock_out_at FROM attendance_logs WHERE id = ? AND store_id = ?',
                    [$logId, $storeId]
                );
                if ($log && $log['original_clock_out_at']) {
                    $minutes = (int) round((strtotime($log['original_clock_out_at']) - strtotime($log['original_clock_in_at'])) / 60);
                    $_SESSION['clock_out_result'] = [
                        'minutes' => $minutes,
                        'pay'     => (int) round($minutes / 60 * $member['hourly_wage']),
                    ];
                }
            }
            flash('success', '퇴근 완료!');
        } else {
            flash('error', '퇴근 처리에 실패했습니다.');
        }
        redirect(url('employee'));
    }

    /** 직원 자신의 월 급여명세서 (예상) */
    public function payslip(): void
    {
        Auth::requireEmployee();
        $storeId  = Auth::storeId();
        $memberId = Auth::storeMemberId();
        $year     = (int)($_GET['year']  ?? date('Y'));
        $month    = (int)($_GET['month'] ?? date('n'));

        $member = StoreMember::find($memberId, $storeId);
        if (!$member) { redirect(url('employee')); }

        $storeSettings = Setting::getByStoreId($storeId);
        $showPay = (int)($storeSettings['show_pay_to_employee'] ?? 1);

        // 점주·직원이 동일한 금액을 보도록 PayrollCalculator(정답 소스)에 위임.
        // 직원 세션의 Auth::ownerId()는 본인 user_id이므로, work_logs 조회는
        // 매장 owner_id(=$member['owner_id'])로 명시적으로 스코프한다.
        $ownerId = (int)$member['owner_id'];
        [$rangeStart, $rangeEnd] = $this->monthLogRange($year, $month);
        $logsByEmployee = WorkLog::forStorePeriodGrouped($storeId, $ownerId, $rangeStart, $rangeEnd);
        $empLogs        = $logsByEmployee[(int)$member['id']] ?? [];

        $calc = new PayrollCalculator();
        $data = $calc->calculateMonthlyPayroll($member, $year, $month, $storeSettings, $empLogs);

        // 뷰가 기대하는 주별 키로 매핑 (PayrollCalculator 주간 결과 → 직원 명세서 형식)
        $weeks = [];
        foreach ($data['weeks'] as $w) {
            $holidayPay = (int) round($w['weekly_holiday_pay']);
            $base       = (int) round($w['base_pay']);
            $premiums   = (int) round(
                $w['night_premium'] + $w['overtime_premium'] + $w['holiday_premium'] + $w['furlough_pay']
            );
            $workDays   = count(array_filter($w['details'], fn($d) => empty($d['is_absent'])));
            $weeks[] = [
                'period_start'  => $w['period_start'],
                'period_end'    => $w['period_end'],
                'work_days'     => $workDays,
                'total_minutes' => $w['total_work_minutes'],
                'paid_minutes'  => $w['paid_work_minutes'],
                'base_pay'      => $base,
                'holiday_pay'   => $holidayPay,
                'subtotal'      => (int) round($w['total_pay']),
                'premium_pay'   => $premiums,
            ];
        }

        $grossPay   = (int) round($data['monthly']['total_pay']);
        $deductions = $showPay ? calculateInsuranceDeductions($grossPay, $storeSettings) : [];
        $netPay     = $showPay ? ($grossPay - ($deductions['total'] ?? 0)) : 0;

        render_employee('employee/payslip', [
            'title'      => "{$year}년 {$month}월 예상 급여명세서",
            'member'     => $member,
            'year'       => $year,
            'month'      => $month,
            'weeks'      => $weeks,
            'grossPay'   => $grossPay,
            'deductions' => $deductions,
            'netPay'     => $netPay,
            'showPay'    => $showPay,
            'settings'   => $storeSettings,
        ]);
    }

    /** 알바 본인에게 발급된 급여명세서 목록 (ISSUED 상태만) */
    public function payslips(): void
    {
        Auth::requireEmployee();
        $memberId = Auth::storeMemberId();
        $member   = StoreMember::find($memberId, Auth::storeId());
        if (!$member) { redirect(url('employee')); }

        // 본인 employee_id(store_members.id) + ISSUED 상태만. 정정된(CORRECTED) 버전은 제외.
        $payslips = DB::fetchAll(
            'SELECT id, period_start, period_end, payment_date, version,
                    gross_pay, net_pay, issued_at, corrected_from_payslip_id
               FROM payslips
              WHERE employee_id=? AND status=?
              ORDER BY period_start DESC, version DESC',
            [$memberId, Payslip::STATUS_ISSUED]
        );

        render_employee('employee/payslips', [
            'title'    => '급여명세서',
            'member'   => $member,
            'payslips' => $payslips,
        ]);
    }

    /** 알바 본인의 다중 사업장 수입 현황 (내 수입) */
    public function income(): void
    {
        Auth::requireLogin();
        if (!Auth::isEmployee()) {
            http_response_code(403);
            echo '<p style="padding:2rem;text-align:center;">이 페이지는 알바 계정 전용입니다.</p>';
            exit;
        }

        $userId = Auth::id();
        $nowY   = (int)date('Y');
        $nowM   = (int)date('n');
        $thisMonthStart = sprintf('%04d-%02d-01', $nowY, $nowM);
        $thisMonthEnd   = date('Y-m-t', strtotime($thisMonthStart));

        // 알바가 속한 모든 활성 사업장
        $allMembers = StoreMember::allByUserId($userId);

        $storeCards   = [];   // 사업장별 카드 데이터
        $visibleTotal = 0;    // 공개 사업장 예상 급여 합계
        $hasHidden    = false; // 비공개 사업장 존재 여부

        foreach ($allMembers as $member) {
            $storeId  = (int)$member['store_id'];
            $memberId = (int)$member['id'];
            $ownerId  = (int)$member['owner_id'];

            $store      = Store::find($storeId);
            $visibility = $store['employee_pay_visibility'] ?? 'ESTIMATED_TOTAL_ONLY';

            // 이번 달 근무시간 집계 (항상 표시)
            $monthLogs = DB::fetchAll(
                "SELECT * FROM work_logs
                  WHERE owner_id=? AND store_id=? AND employee_id=?
                    AND work_date BETWEEN ? AND ?
                    AND is_absent=0
                  ORDER BY work_date ASC",
                [$ownerId, $storeId, $memberId, $thisMonthStart, $thisMonthEnd]
            );

            $totalWorkMinutes = 0;
            foreach ($monthLogs as $log) {
                $calc = new PayrollCalculator();
                $wm   = $calc->calculateWorkMinutes($log['work_date'], $log['start_time'], $log['end_time']);
                $bm   = $log['break_auto']
                    ? $calc->calculateAutoBreakMinutes($wm)
                    : (int)$log['break_minutes'];
                $totalWorkMinutes += $calc->calculatePaidWorkMinutes($wm, $bm);
            }

            // 예상 급여 계산 (visibility에 따라)
            $estimatedPay = null;
            $payBreakdown = null;

            if ($visibility !== 'HOURS_ONLY') {
                $estimatedPay  = 0;
                $storeSettings = Setting::getByStoreId($storeId);

                $firstDay = new DateTime($thisMonthStart);
                $lastDay  = (clone $firstDay)->modify('last day of this month');
                [$rangeStart]  = getWeekRange($firstDay->format('Y-m-d'));
                [, $rangeEnd]  = getWeekRange($lastDay->format('Y-m-d'));

                $logsByEmployee = WorkLog::forStorePeriodGrouped($storeId, $ownerId, $rangeStart, $rangeEnd);
                $empLogs        = $logsByEmployee[$memberId] ?? [];

                if (!empty($empLogs)) {
                    $pcalc    = new PayrollCalculator();
                    $calcData = $pcalc->calculateMonthlyPayroll($member, $nowY, $nowM, $storeSettings, $empLogs);
                    $monthly  = $calcData['monthly'];

                    $estimatedPay  = (int)round($monthly['total_pay']);
                    $visibleTotal += $estimatedPay;

                    if ($visibility === 'ESTIMATED_WITH_BREAKDOWN') {
                        $deductions = calculateInsuranceDeductions($estimatedPay, $storeSettings);
                        $payBreakdown = [
                            'base_pay'           => (int)round($monthly['base_pay']),
                            'weekly_holiday_pay' => (int)round($monthly['weekly_holiday_pay']),
                            'night_premium'      => (int)round($monthly['night_premium']),
                            'overtime_premium'   => (int)round($monthly['overtime_premium']),
                            'holiday_premium'    => (int)round($monthly['holiday_premium']),
                            'deductions_total'   => $deductions['total'] ?? 0,
                            'net_pay'            => $estimatedPay - ($deductions['total'] ?? 0),
                        ];
                    }
                }
            } else {
                $hasHidden = true;
            }

            // 지난달 확정 급여 (payroll_results)
            $prevY = $nowM === 1 ? $nowY - 1 : $nowY;
            $prevM = $nowM === 1 ? 12 : $nowM - 1;
            $prevStart = sprintf('%04d-%02d-01', $prevY, $prevM);
            $prevEnd   = date('Y-m-t', strtotime($prevStart));

            $lastMonthPay = DB::fetchOne(
                "SELECT SUM(total_pay) AS total FROM payroll_results
                  WHERE owner_id=? AND employee_id=?
                    AND period_start >= ? AND period_end <= ?",
                [$ownerId, $memberId, $prevStart, $prevEnd]
            );

            // 고용보험 공제 여부 확인 필요 여부 (다른 사업장에서 고용보험 가입 중)
            $insNeedsCheck = (($member['works_at_other_business'] ?? '') === 'YES'
                && ($member['other_business_insurance_enrolled'] ?? '') === 'YES');

            $storeCards[] = [
                'member'             => $member,
                'store_name'         => $member['store_name'],
                'visibility'         => $visibility,
                'total_work_minutes' => $totalWorkMinutes,
                'estimated_pay'      => $estimatedPay,
                'pay_breakdown'      => $payBreakdown,
                'last_month_pay'     => (int)round((float)($lastMonthPay['total'] ?? 0)),
                'ins_needs_check'    => $insNeedsCheck,
            ];
        }

        // 월별 수입 추이 (최근 6개월, 모든 사업장 확정 급여 합산)
        $monthlyTrend = [];
        $memberIds    = array_column($allMembers, 'id');
        for ($i = 5; $i >= 0; $i--) {
            $ts    = strtotime("-{$i} months", mktime(0, 0, 0, $nowM, 1, $nowY));
            $y     = (int)date('Y', $ts);
            $m     = (int)date('n', $ts);
            $ms    = sprintf('%04d-%02d-01', $y, $m);
            $me    = date('Y-m-t', strtotime($ms));
            $label = sprintf('%d/%02d', $y, $m);

            $total = 0;
            if (!empty($memberIds)) {
                $placeholders = implode(',', array_fill(0, count($memberIds), '?'));
                $row = DB::fetchOne(
                    "SELECT SUM(total_pay) AS total FROM payroll_results
                      WHERE employee_id IN ($placeholders)
                        AND period_start >= ? AND period_end <= ?",
                    array_merge($memberIds, [$ms, $me])
                );
                $total = (int)round((float)($row['total'] ?? 0));
            }
            $monthlyTrend[] = ['label' => $label, 'total' => $total];
        }

        // employee_mobile_layout이 $member 변수를 사용하므로 현재 세션 매장을 기본 member로 설정
        $currentMember = StoreMember::find(Auth::storeMemberId(), Auth::storeId());

        render_employee('employee/income', [
            'title'        => '내 수입',
            'member'       => $currentMember ?? ($allMembers[0] ?? []),
            'storeCards'   => $storeCards,
            'visibleTotal' => $visibleTotal,
            'hasHidden'    => $hasHidden,
            'monthlyTrend' => $monthlyTrend,
            'nowY'         => $nowY,
            'nowM'         => $nowM,
        ]);
    }

    /**
     * 월 급여 계산이 참조하는 날짜 범위(주 단위 정렬).
     * 월 첫날이 속한 주의 시작일 ~ 월 마지막날이 속한 주의 종료일.
     */
    private function monthLogRange(int $year, int $month): array
    {
        $firstDay = new DateTime(sprintf('%04d-%02d-01', $year, $month));
        $lastDay  = (clone $firstDay)->modify('last day of this month');

        [$weekStart]     = getWeekRange($firstDay->format('Y-m-d'));
        [, $lastWeekEnd] = getWeekRange($lastDay->format('Y-m-d'));

        return [$weekStart, $lastWeekEnd];
    }

    /** 수정 요청 제출 */
    public function requestCorrection(): void
    {
        Auth::requireEmployee();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(url('employee'));
        }
        verify_csrf();

        $storeId  = Auth::storeId();
        $memberId = Auth::storeMemberId();
        $reason   = trim($_POST['reason'] ?? '');
        $logId    = (int)($_POST['attendance_log_id'] ?? 0) ?: null;
        $reqIn    = trim($_POST['requested_clock_in_at']  ?? '') ?: null;
        $reqOut   = trim($_POST['requested_clock_out_at'] ?? '') ?: null;

        if (!$reason) {
            flash('error', '수정 사유를 입력하세요.');
            redirect(url('employee'));
        }

        AttendanceCorrectionRequest::create([
            'attendance_log_id'      => $logId,
            'store_id'               => $storeId,
            'store_member_id'        => $memberId,
            'requested_clock_in_at'  => $reqIn,
            'requested_clock_out_at' => $reqOut,
            'reason'                 => $reason,
        ]);

        flash('success', '수정 요청을 접수했습니다. 점주 승인 후 반영됩니다.');
        redirect(url('employee'));
    }

    public function objectCorrection(): void
    {
        Auth::requireEmployee();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(url('employee'));
        }
        verify_csrf();

        $storeId  = Auth::storeId();
        $memberId = Auth::storeMemberId();
        $id       = (int)($_POST['correction_id'] ?? 0);
        $text     = trim($_POST['objection_text'] ?? '');

        if (!$text) {
            flash('error', '이의제기 사유를 입력하세요.');
            redirect(url('employee'));
        }

        $req = DB::fetchOne(
            "SELECT * FROM attendance_correction_requests WHERE id = ? AND store_id = ? AND store_member_id = ? AND status = 'rejected'",
            [$id, $storeId, $memberId]
        );
        if (!$req) {
            flash('error', '처리할 수 없는 요청입니다.');
            redirect(url('employee'));
        }

        AttendanceCorrectionRequest::objection($id, $storeId, $text);
        flash('success', '이의제기를 제출했습니다. 사장님이 확인 후 처리합니다.');
        redirect(url('employee'));
    }

    public function selectStore(): void
    {
        Auth::requireEmployee();

        $members = $_SESSION['pending_store_members'] ?? [];
        if (empty($members)) {
            redirect(url('employee'));
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $storeMemberId = (int)($_POST['store_member_id'] ?? 0);
            $storeId       = (int)($_POST['store_id']       ?? 0);

            // 후보 목록에 있는 매장인지 검증
            $valid = false;
            foreach ($members as $m) {
                if ((int)$m['id'] === $storeMemberId && (int)$m['store_id'] === $storeId) {
                    $valid = true;
                    break;
                }
            }

            if ($valid) {
                unset($_SESSION['pending_store_members']);
                Auth::setStoreSession($storeId, $storeMemberId);
                redirect(url('employee'));
            } else {
                flash('error', '올바르지 않은 매장 선택입니다.');
            }
        }

        // 레이아웃 없이 직접 뷰 렌더
        $content = '';
        ob_start();
        $members = $members; // make available to view
        require APP_PATH . '/views/employee/select_store.php';
        $content = ob_get_clean();
        echo $content;
        exit;
    }
}
