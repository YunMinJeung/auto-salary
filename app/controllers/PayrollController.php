<?php
class PayrollController
{
    public function weekly(): void
    {
        $employees     = Employee::all();
        $settings      = Setting::get();
        $weekDate      = $_GET['week_date'] ?? date('Y-m-d');
        $employeeId    = (int) ($_GET['employee_id'] ?? 0);

        [$periodStart, $periodEnd] = getWeekRange($weekDate);
        $periodMinWage = MinimumWage::effectiveHourlyWage($periodStart);

        if ($employeeId > 0) {
            // ── 단일 직원 상세 뷰 ─────────────────────────────
            $employee = Employee::find($employeeId);
            if (!$employee) {
                flash('error', '직원을 찾을 수 없습니다.');
                redirect(url('payroll'));
            }

            $workLogs = WorkLog::forPeriod($employeeId, $periodStart, $periodEnd);
            $calc     = new PayrollCalculator();
            $result   = $calc->calculateWeeklyPayroll(
                $employee, $workLogs, $settings, $periodStart, $periodEnd
            );

            $pendingAlerts = LaborRiskEngine::detectForPayroll($result, $employee, Auth::ownerId(), $periodStart);

            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
                verify_csrf();

                // 급여 결과 행은 항상 저장(생성)한다. 단 고용보험 공제 여부가
                // NEEDS_CHECK이면 '확정'은 보류 상태로 두고, 사장이 공제 여부를
                // 선택(updateInsStatus)해야 최종 확정으로 간주한다.
                $this->saveResult($result);
                if (!empty($_POST['danger_acknowledged']) && !empty($pendingAlerts)) {
                    foreach ($pendingAlerts as $a) {
                        if (!empty($a['alert_id']) && $a['severity'] === 'danger') {
                            LaborRiskAlert::acknowledge((int)$a['alert_id'], Auth::id());
                        }
                    }
                }

                // 저장 후 현재 공제 상태 확인 — NEEDS_CHECK이면 확정 불가 안내.
                $savedPr = DB::fetchOne(
                    'SELECT employment_insurance_deduction_status FROM payroll_results
                     WHERE employee_id=? AND owner_id=? AND period_start=? AND period_end=?',
                    [$employee['id'], Auth::ownerId(), $periodStart, $periodEnd]
                );
                if ($savedPr && $savedPr['employment_insurance_deduction_status'] === 'NEEDS_CHECK') {
                    flash('error', '확인 필요한 공제 항목이 남아 있어 급여를 확정할 수 없습니다. 고용보험 공제 여부를 먼저 선택해 주세요.');
                } else {
                    flash('success', '급여 계산 결과가 저장되었습니다.');
                }
                redirect(url('payroll', 'index', [
                    'employee_id' => $employeeId,
                    'week_date'   => $weekDate,
                ]));
            }

            // 저장된 급여 결과 행(있으면) — 고용보험 공제 상태 표시·선택 UI용
            $savedResult = DB::fetchOne(
                'SELECT id, employment_insurance_deduction_status, ins_status_reason
                   FROM payroll_results
                  WHERE employee_id=? AND owner_id=? AND period_start=? AND period_end=?',
                [$employeeId, Auth::ownerId(), $periodStart, $periodEnd]
            );

            // 같은 기간 발급된 급여명세서(최신 버전, 있으면) — 발급 버튼/이동 UI용
            $existingPayslip = Payslip::latestByEmployeePeriod(
                Auth::storeId(), $employeeId, $periodStart, $periodEnd
            );

            render('payroll/weekly', [
                'title'           => '주간 급여 계산',
                'employees'       => $employees,
                'employeeId'      => $employeeId,
                'weekDate'        => $weekDate,
                'periodStart'     => $periodStart,
                'periodEnd'       => $periodEnd,
                'result'          => $result,
                'allResults'      => null,
                'settings'        => $settings,
                'periodMinWage'   => $periodMinWage,
                'pendingAlerts'   => $pendingAlerts,
                'savedResult'     => $savedResult,
                'existingPayslip' => $existingPayslip,
            ]);
        } else {
            // ── 전체 직원 요약 뷰 (기본) ─────────────────────
            $calc          = new PayrollCalculator();
            $allResults    = [];
            $logsByEmployee = WorkLog::forStorePeriodGrouped(Auth::storeId(), Auth::ownerId(), $periodStart, $periodEnd);
            foreach ($employees as $emp) {
                $workLogs = $logsByEmployee[(int)$emp['id']] ?? [];
                $r = $calc->calculateWeeklyPayroll($emp, $workLogs, $settings, $periodStart, $periodEnd);
                $r['has_logs'] = !empty($workLogs);
                $allResults[] = $r;
            }

            render('payroll/weekly', [
                'title'         => '주간 급여 계산',
                'employees'     => $employees,
                'employeeId'    => 0,
                'weekDate'      => $weekDate,
                'periodStart'   => $periodStart,
                'periodEnd'     => $periodEnd,
                'result'        => null,
                'allResults'    => $allResults,
                'settings'      => $settings,
                'periodMinWage' => $periodMinWage,
                'pendingAlerts' => [],
            ]);
        }
    }

    public function monthly(): void
    {
        $employees  = Employee::all();
        $settings   = Setting::get();
        $year       = (int) ($_GET['year']  ?? date('Y'));
        $month      = (int) ($_GET['month'] ?? date('n'));
        $employeeId = (int) ($_GET['employee_id'] ?? 0);

        if ($employeeId > 0) {
            // ── 단일 직원 상세 뷰 ─────────────────────────────
            $employee = Employee::find($employeeId);
            if (!$employee) {
                flash('error', '직원을 찾을 수 없습니다.');
                redirect(url('payroll', 'monthly'));
            }

            $calc = new PayrollCalculator();
            $data = $calc->calculateMonthlyPayroll($employee, $year, $month, $settings);

            // 해당 월 발급된 급여명세서(최신 버전, 있으면) — 발급/정정 UI용
            $periodStart = sprintf('%04d-%02d-01', $year, $month);
            $periodEnd   = date('Y-m-t', strtotime($periodStart));
            $existingPayslip = Payslip::latestByEmployeePeriod(
                Auth::storeId(), $employeeId, $periodStart, $periodEnd
            );

            // 이미 저장된 주간 급여 결과 중 고용보험 공제 여부 미확정(NEEDS_CHECK)
            // 행이 있으면 발급 차단. (사전 저장이 없는 주는 발급 시 자동 저장된다.)
            $weeklyRows = DB::fetchAll(
                'SELECT employment_insurance_deduction_status FROM payroll_results
                  WHERE employee_id=? AND owner_id=? AND period_start >= ? AND period_end <= ?',
                [$employeeId, Auth::ownerId(), $periodStart, $periodEnd]
            );
            $insNeedsCheck = false;
            foreach ($weeklyRows as $wr) {
                if (($wr['employment_insurance_deduction_status'] ?? 'APPLIED') === 'NEEDS_CHECK') {
                    $insNeedsCheck = true;
                    break;
                }
            }

            // 발급 가능 여부: 해당 월에 근무기록(계산된 주)이 있으면 발급 가능.
            // payroll_results 사전 저장은 더 이상 선행 조건이 아니다 — 발급 시 자동 계산·저장.
            $hasWorkLogs        = !empty($data['weeks']);
            $defaultPaymentDate = date('Y-m-10', strtotime($periodStart . ' +1 month'));

            render('payroll/monthly', [
                'title'              => '월간 급여 요약',
                'employees'          => $employees,
                'employeeId'         => $employeeId,
                'employee'           => $employee,
                'year'               => $year,
                'month'              => $month,
                'data'               => $data,
                'allData'            => null,
                'settings'           => $settings,
                'existingPayslip'    => $existingPayslip,
                'periodStart'        => $periodStart,
                'periodEnd'          => $periodEnd,
                'hasWorkLogs'        => $hasWorkLogs,
                'insNeedsCheck'      => $insNeedsCheck,
                'defaultPaymentDate' => $defaultPaymentDate,
            ]);
        } else {
            // ── 전체 직원 요약 뷰 (기본) ─────────────────────
            // N+1 방지: 월 전체 로그를 1회 조회 후 직원별로 버킷팅하여 전달.
            [$rangeStart, $rangeEnd] = $this->monthLogRange($year, $month);
            $logsByEmployee = WorkLog::forStorePeriodGrouped(
                Auth::storeId(), Auth::ownerId(), $rangeStart, $rangeEnd
            );

            $calc    = new PayrollCalculator();
            $allData = [];
            foreach ($employees as $emp) {
                $empLogs = $logsByEmployee[(int)$emp['id']] ?? [];
                $d = $calc->calculateMonthlyPayroll($emp, $year, $month, $settings, $empLogs);
                if ($d) $allData[] = $d;
            }

            render('payroll/monthly', [
                'title'      => '월간 급여 요약',
                'employees'  => $employees,
                'employeeId' => 0,
                'year'       => $year,
                'month'      => $month,
                'data'       => null,
                'allData'    => $allData,
                'settings'   => $settings,
            ]);
        }
    }

    public function payslip(): void
    {
        $employeeId = (int)($_GET['employee_id'] ?? 0);
        $year       = (int)($_GET['year']  ?? date('Y'));
        $month      = (int)($_GET['month'] ?? date('n'));

        if (!$employeeId) {
            redirect(url('payroll', 'monthly'));
        }

        $employee = Employee::find($employeeId);
        if (!$employee) {
            flash('error', '직원을 찾을 수 없습니다.');
            redirect(url('payroll', 'monthly'));
        }

        $settings    = Setting::get();
        $calc        = new PayrollCalculator();
        $data        = $calc->calculateMonthlyPayroll($employee, $year, $month, $settings);
        $store       = Store::findOwned(Auth::storeId(), Auth::ownerId());
        $grossPay    = (int) round($data['monthly']['total_pay']);
        $deductions  = calculateInsuranceDeductions($grossPay, $settings);
        $netPay      = $grossPay - $deductions['total'];

        $insChecker = new InsuranceEligibilityChecker();
        $insCheck   = $insChecker->checkAll([
            'weekly_scheduled_hours'       => $employee['weekly_scheduled_hours'] ?? 40,
            'expected_employment_duration' => 'undefined',
        ]);

        render('payroll/payslip', [
            'title'      => "{$employee['name']} {$year}년 {$month}월 급여명세서",
            'data'       => $data,
            'settings'   => $settings,
            'store'      => $store,
            'year'       => $year,
            'month'      => $month,
            'grossPay'   => $grossPay,
            'deductions' => $deductions,
            'netPay'     => $netPay,
            'insCheck'   => $insCheck,
        ], 'payslip_layout');
    }

    public function exportCsv(): void
    {
        $employeeId = (int) ($_GET['employee_id'] ?? 0);
        $year       = (int) ($_GET['year']  ?? date('Y'));
        $month      = (int) ($_GET['month'] ?? date('n'));

        if (!$employeeId) {
            redirect(url('payroll', 'monthly'));
        }

        $employee = Employee::find($employeeId);
        $settings = Setting::get();
        $calc     = new PayrollCalculator();
        $data     = $calc->calculateMonthlyPayroll($employee, $year, $month, $settings);

        $filename = sprintf(
            '%s_%d년%d월_급여내역.csv',
            $employee['name'], $year, $month
        );

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
        header('Cache-Control: no-cache');

        // BOM for Excel compatibility
        echo "\xEF\xBB\xBF";

        $fp = fopen('php://output', 'w');
        fputcsv($fp, ['기간', '총근무시간', '유급근무시간', '야간시간', '연장시간', '기본급', '주휴수당', '야간가산', '연장가산', '휴일가산', '합계']);

        foreach ($data['weeks'] as $w) {
            fputcsv($fp, [
                $w['period_start'] . ' ~ ' . $w['period_end'],
                minutesToDecimal($w['total_work_minutes']),
                minutesToDecimal($w['paid_work_minutes']),
                minutesToDecimal($w['night_minutes']),
                minutesToDecimal($w['overtime_minutes']),
                round($w['base_pay']),
                round($w['weekly_holiday_pay']),
                round($w['night_premium']),
                round($w['overtime_premium']),
                round($w['holiday_premium']),
                round($w['total_pay']),
            ]);
        }

        $m = $data['monthly'];
        fputcsv($fp, [
            '합 계',
            minutesToDecimal($m['total_work_minutes']),
            minutesToDecimal($m['paid_work_minutes']),
            minutesToDecimal($m['night_minutes']),
            minutesToDecimal($m['overtime_minutes']),
            round($m['base_pay']),
            round($m['weekly_holiday_pay']),
            round($m['night_premium']),
            round($m['overtime_premium']),
            round($m['holiday_premium']),
            round($m['total_pay']),
        ]);

        fclose($fp);
        exit;
    }

    /**
     * 월 급여 계산이 실제로 참조하는 날짜 범위(주 단위 정렬).
     * 월 첫날이 속한 주의 시작일 ~ 월 마지막날이 속한 주의 종료일.
     */
    private function monthLogRange(int $year, int $month): array
    {
        $firstDay = new DateTime(sprintf('%04d-%02d-01', $year, $month));
        $lastDay  = (clone $firstDay)->modify('last day of this month');

        [$weekStart]      = getWeekRange($firstDay->format('Y-m-d'));
        [, $lastWeekEnd]  = getWeekRange($lastDay->format('Y-m-d'));

        return [$weekStart, $lastWeekEnd];
    }

    /**
     * 고용보험 공제 상태 변경 (확인 필요 → 공제 적용/제외 확정).
     * POST: payroll_id, ins_status(APPLIED|EXCLUDED), reason(선택)
     */
    public function updateInsStatus(): void
    {
        Auth::requireOwner();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(url('payroll'));
        }
        verify_csrf();

        $payrollId = (int) ($_POST['payroll_id'] ?? 0);
        $newStatus = $_POST['ins_status'] ?? '';
        $reason    = trim($_POST['reason'] ?? '');

        if (!in_array($newStatus, ['APPLIED', 'EXCLUDED'], true)) {
            flash('error', '잘못된 상태값입니다.');
            redirect(url('payroll'));
        }

        $ownerId = Auth::ownerId();
        $pr = DB::fetchOne(
            'SELECT * FROM payroll_results WHERE id=? AND owner_id=?',
            [$payrollId, $ownerId]
        );
        if (!$pr) {
            http_response_code(403);
            exit;
        }

        $before = $pr['employment_insurance_deduction_status'];
        DB::query(
            'UPDATE payroll_results
               SET employment_insurance_deduction_status=?,
                   ins_status_updated_by=?, ins_status_updated_at=NOW(), ins_status_reason=?
             WHERE id=? AND owner_id=?',
            [$newStatus, Auth::id(), ($reason !== '' ? $reason : null), $payrollId, $ownerId]
        );

        AuditLog::record(
            'UPDATE_EMPLOYMENT_INSURANCE_DEDUCTION_STATUS',
            'payroll_result',
            $payrollId,
            $before,
            $newStatus,
            $reason
        );

        flash('success', '고용보험 공제 상태가 변경되었습니다.');
        redirect(url('payroll', 'index', [
            'employee_id' => (int) $pr['employee_id'],
            'week_date'   => $pr['period_start'],
        ]));
    }

    private function saveResult(array $result): void
    {
        $ownerId = Auth::ownerId();

        $existing = DB::fetchOne(
            'SELECT id FROM payroll_results
             WHERE employee_id = ? AND owner_id = ? AND period_start = ? AND period_end = ?',
            [$result['employee']['id'], $ownerId, $result['period_start'], $result['period_end']]
        );

        $params = [
            $result['total_work_minutes'],
            $result['break_minutes'],
            $result['paid_work_minutes'],
            $result['night_minutes'],
            $result['overtime_minutes'],
            $result['holiday_minutes'],
            $result['base_pay'],
            $result['weekly_holiday_hours'],
            $result['weekly_holiday_pay'],
            $result['night_premium'],
            $result['overtime_premium'],
            $result['holiday_premium'],
            $result['total_pay'],
            json_encode($result['details'], JSON_UNESCAPED_UNICODE),
        ];

        if ($existing) {
            DB::query('
                UPDATE payroll_results SET
                    total_work_minutes=?, break_minutes=?, paid_work_minutes=?,
                    night_minutes=?, overtime_minutes=?, holiday_minutes=?,
                    base_pay=?, weekly_holiday_hours=?, weekly_holiday_pay=?,
                    night_premium=?, overtime_premium=?, holiday_premium=?,
                    total_pay=?, calculation_detail_json=?
                WHERE id=? AND owner_id=?
            ', array_merge($params, [$existing['id'], $ownerId]));
        } else {
            // 신규 저장 시 고용보험 공제 상태 결정 (기존 행의 상태는 update 시 보존).
            $member = $result['employee'];
            $insStatus = (($member['works_at_other_business'] ?? '') === 'YES'
                && ($member['other_business_insurance_enrolled'] ?? '') === 'YES')
                ? 'NEEDS_CHECK'
                : 'APPLIED';

            DB::query('
                INSERT INTO payroll_results
                  (owner_id, employee_id, period_start, period_end,
                   total_work_minutes, break_minutes, paid_work_minutes,
                   night_minutes, overtime_minutes, holiday_minutes,
                   base_pay, weekly_holiday_hours, weekly_holiday_pay,
                   night_premium, overtime_premium, holiday_premium,
                   total_pay, calculation_detail_json,
                   employment_insurance_deduction_status)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
            ', array_merge(
                [$ownerId, $result['employee']['id'], $result['period_start'], $result['period_end']],
                $params,
                [$insStatus]
            ));
        }
    }
}
