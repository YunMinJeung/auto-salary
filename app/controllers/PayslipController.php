<?php
/**
 * 급여명세서 발급 워크플로우.
 * 발급(ISSUED) 후에는 수정 불가. 정정 시 새 버전 발급 + 원본 CORRECTED 처리.
 *
 * 데이터 소스: payroll_results(주 단위 확정 행)를 스냅샷으로 동결.
 * 공제액은 발급 시점에 calculateInsuranceDeductions()로 계산하여 스냅샷에 함께 저장.
 */
class PayslipController
{
    /** 사장용 급여명세서 목록 */
    public function index(): void
    {
        Auth::requireOwner();

        $filters = [
            'status'      => $_GET['status']      ?? '',
            'employee_id' => (int) ($_GET['employee_id'] ?? 0),
            'year'        => (int) ($_GET['year']  ?? 0),
            'month'       => (int) ($_GET['month'] ?? 0),
        ];

        $payslips  = Payslip::allByStore(Auth::storeId(), $filters);
        $employees = Employee::all();

        render('payslip/index', [
            'title'     => '급여명세서 관리',
            'payslips'  => $payslips,
            'employees' => $employees,
            'filters'   => $filters,
        ]);
    }

    /**
     * 월간 급여명세서 미리보기 (POST) — DB 저장 없이 계산 결과만 표시.
     * 사용자가 확인 후 "이대로 발급" 버튼을 눌러야 실제 저장됨.
     */
    public function previewMonthly(): void
    {
        Auth::requireOwner();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(url('payroll', 'monthly'));
        }
        verify_csrf();

        $employeeId  = (int)($_POST['employee_id'] ?? 0);
        $year        = (int)($_POST['year']  ?? 0);
        $month       = (int)($_POST['month'] ?? 0);
        $paymentDate = trim($_POST['payment_date'] ?? '') ?: null;

        if (!$employeeId || !$year || !$month) {
            flash('error', '잘못된 요청입니다.');
            redirect(url('payroll', 'monthly'));
            return;
        }

        $employee = DB::fetchOne(
            'SELECT * FROM store_members WHERE id=? AND store_id=?',
            [$employeeId, Auth::storeId()]
        );
        if (!$employee) {
            http_response_code(403);
            exit;
        }

        [$periodStart, $periodEnd] = $this->monthPeriod($year, $month);

        $existing = Payslip::latestByEmployeePeriod(Auth::storeId(), $employeeId, $periodStart, $periodEnd);
        if ($existing && $existing['status'] === Payslip::STATUS_ISSUED) {
            flash('error', '이미 발급된 급여명세서가 있습니다. 정정 발급을 이용하세요.');
            redirect(url('payslip', 'show', ['id' => $existing['id']]));
            return;
        }

        $settings = Setting::get();
        $calc     = new PayrollCalculator();
        $data     = $calc->calculateMonthlyPayroll($employee, $year, $month, $settings);

        if (empty($data['weeks'])) {
            flash('error', '이 달에 근무기록이 없어 급여명세서를 발급할 수 없습니다.');
            redirect(url('payroll', 'monthly', ['employee_id' => $employeeId, 'year' => $year, 'month' => $month]));
            return;
        }

        $storeSettings = Setting::getByStoreId(Auth::storeId());
        $grossPay      = 0;
        foreach ($data['weeks'] as $w) {
            $grossPay += (float)($w['total_pay'] ?? 0);
        }
        $grossPay        = (int)round($grossPay);
        $deductions      = calculateInsuranceDeductions($grossPay, $storeSettings);
        $totalDeductions = (int)$deductions['total'];
        $netPay          = $grossPay - $totalDeductions;

        // DB 컬럼명과 맞게 변환 (show.php의 weekly_rows 형식)
        $weeklyRows = [];
        foreach ($data['weeks'] as $w) {
            $weeklyRows[] = [
                'period_start'       => $w['period_start'],
                'period_end'         => $w['period_end'],
                'paid_work_minutes'  => $w['paid_work_minutes']  ?? 0,
                'base_pay'           => $w['base_pay']           ?? 0,
                'weekly_holiday_pay' => $w['weekly_holiday_pay'] ?? 0,
                'night_premium'      => $w['night_premium']      ?? 0,
                'overtime_premium'   => $w['overtime_premium']   ?? 0,
                'holiday_premium'    => $w['holiday_premium']    ?? 0,
                'total_pay'          => $w['total_pay']          ?? 0,
            ];
        }

        render('payslip/preview', [
            'title'          => "{$year}년 {$month}월 급여명세서 미리보기",
            'employee'       => $employee,
            'year'           => $year,
            'month'          => $month,
            'periodStart'    => $periodStart,
            'periodEnd'      => $periodEnd,
            'paymentDate'    => $paymentDate,
            'grossPay'       => $grossPay,
            'deductions'     => $deductions,
            'totalDeductions' => $totalDeductions,
            'netPay'         => $netPay,
            'weeklyRows'     => $weeklyRows,
        ]);
    }

    /** 급여명세서 발급 (POST) — 월 단위 집계 */
    public function issue(): void
    {
        Auth::requireOwner();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(url('payslip', 'index'));
        }
        verify_csrf();

        $employeeId  = (int) ($_POST['employee_id'] ?? 0);
        $year        = (int) ($_POST['year']  ?? 0);
        $month       = (int) ($_POST['month'] ?? 0);
        $paymentDate = trim($_POST['payment_date'] ?? '') ?: null;
        $periodType  = $_POST['pay_period_type'] ?? Payslip::PERIOD_MONTHLY;

        if (!$employeeId || !$year || !$month) {
            flash('error', '잘못된 요청입니다.');
            redirect(url('payslip', 'index'));
            return;
        }

        // MVP 가드: 월급(MONTHLY)만 지원
        if (!in_array($periodType, Payslip::SUPPORTED_PERIOD_TYPES, true)) {
            flash('error', $periodType . ' 정산은 아직 지원하지 않습니다. 현재는 월급 정산만 지원합니다.');
            redirect(url('payslip', 'index'));
            return;
        }

        // 직원 소유 검증 (store_members 기준)
        $employee = DB::fetchOne(
            'SELECT * FROM store_members WHERE id=? AND store_id=?',
            [$employeeId, Auth::storeId()]
        );
        if (!$employee) {
            http_response_code(403);
            exit;
        }

        [$periodStart, $periodEnd] = $this->monthPeriod($year, $month);

        // 같은 달 기존 발급본 확인
        $existing = Payslip::latestByEmployeePeriod(
            Auth::storeId(), $employeeId, $periodStart, $periodEnd
        );
        if ($existing && $existing['status'] === Payslip::STATUS_ISSUED) {
            flash('error', '이미 발급된 급여명세서가 있습니다. 정정 발급을 이용하세요.');
            redirect(url('payslip', 'show', ['id' => $existing['id']]));
            return;
        }

        // 해당 월의 주간 급여 결과 조회·검증
        [$ok, $weeklyRows, $error] = $this->collectMonthlyRows($employeeId, $periodStart, $periodEnd);
        if (!$ok) {
            flash('error', $error);
            redirect(url('payroll', 'monthly', ['employee_id' => $employeeId, 'year' => $year, 'month' => $month]));
            return;
        }

        $version     = $existing ? ((int) $existing['version'] + 1) : 1;
        $periodLabel = Payslip::periodLabel($periodType, $periodStart, $periodEnd);
        $snap        = $this->buildMonthlySnapshot($employee, $weeklyRows, $year, $month, $periodStart, $periodEnd, $paymentDate, $periodType, $periodLabel);

        $payslipId = Payslip::create([
            'store_id'          => Auth::storeId(),
            'owner_id'          => Auth::ownerId(),
            'employee_id'       => $employeeId,
            'payroll_result_id' => null,  // 월 단위이므로 단일 행 참조 없음
            'period_start'      => $periodStart,
            'period_end'        => $periodEnd,
            'payment_date'      => $paymentDate,
            'version'           => $version,
            'status'            => Payslip::STATUS_ISSUED,
            'pay_period_type'   => $periodType,
            'period_label'      => $periodLabel,
            'gross_pay'         => $snap['gross_pay'],
            'total_deductions'  => $snap['total_deductions'],
            'net_pay'           => $snap['net_pay'],
            'snapshot_json'     => json_encode($snap, JSON_UNESCAPED_UNICODE),
            'issued_at'         => date('Y-m-d H:i:s'),
            'issued_by'         => Auth::id(),
        ]);

        // DRAFT/CONFIRMED 이전 버전이 있으면 CORRECTED로 정리
        if ($existing && in_array($existing['status'], [Payslip::STATUS_DRAFT, Payslip::STATUS_CONFIRMED], true)) {
            Payslip::updateStatus((int) $existing['id'], Auth::ownerId(), Payslip::STATUS_CORRECTED);
        }

        AuditLog::record(
            'ISSUE_PAYSLIP', 'payslip', $payslipId,
            null,
            ['version' => $version, 'period' => "{$year}-{$month}", 'net_pay' => $snap['net_pay']],
            ''
        );

        flash('success', "{$year}년 {$month}월 급여명세서가 발급되었습니다.");
        redirect(url('payslip', 'show', ['id' => $payslipId]));
    }

    /**
     * 월간 급여명세서 일괄 계산·저장·발급 (POST).
     * 주간 payroll_results가 사전 저장되어 있지 않아도 이 메서드 하나로
     * 해당 월 전체 주간을 자동 계산·저장한 뒤 곧바로 발급한다.
     * pay_period_type=MONTHLY만 지원 (MVP).
     */
    public function issueMonthly(): void
    {
        Auth::requireOwner();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(url('payroll', 'monthly'));
        }
        verify_csrf();

        $employeeId  = (int) ($_POST['employee_id'] ?? 0);
        $year        = (int) ($_POST['year']  ?? 0);
        $month       = (int) ($_POST['month'] ?? 0);
        $paymentDate = trim($_POST['payment_date'] ?? '') ?: null;

        if (!$employeeId || !$year || !$month) {
            flash('error', '잘못된 요청입니다.');
            redirect(url('payroll', 'monthly'));
            return;
        }

        // 직원 소유 검증 (store_members 기준)
        $employee = DB::fetchOne(
            'SELECT * FROM store_members WHERE id=? AND store_id=?',
            [$employeeId, Auth::storeId()]
        );
        if (!$employee) {
            http_response_code(403);
            exit;
        }

        [$periodStart, $periodEnd] = $this->monthPeriod($year, $month);

        // 같은 달 기존 발급본 확인 — 이미 발급(ISSUED)됐으면 정정으로 유도
        $existing = Payslip::latestByEmployeePeriod(
            Auth::storeId(), $employeeId, $periodStart, $periodEnd
        );
        if ($existing && $existing['status'] === Payslip::STATUS_ISSUED) {
            flash('error', '이미 발급된 급여명세서가 있습니다. 정정 발급을 이용하세요.');
            redirect(url('payslip', 'show', ['id' => $existing['id']]));
            return;
        }

        // ── 해당 월 전체 주간을 자동 계산하여 payroll_results에 저장 ──
        // (없는 주만 신규 저장, 이미 있는 주는 보존)
        $settings = Setting::get();
        $calc     = new PayrollCalculator();
        $data     = $calc->calculateMonthlyPayroll($employee, $year, $month, $settings);

        if (empty($data['weeks'])) {
            flash('error', '이 달에 근무기록이 없어 급여명세서를 발급할 수 없습니다.');
            redirect(url('payroll', 'monthly', ['employee_id' => $employeeId, 'year' => $year, 'month' => $month]));
            return;
        }

        foreach ($data['weeks'] as $weekResult) {
            $this->ensureWeeklyResultSaved($weekResult);
        }

        // ── 저장된 주간 rows 재조회·검증 후 월 단위 스냅샷 발급 ──
        [$ok, $weeklyRows, $error] = $this->collectMonthlyRows($employeeId, $periodStart, $periodEnd);
        if (!$ok) {
            flash('error', $error);
            redirect(url('payroll', 'monthly', ['employee_id' => $employeeId, 'year' => $year, 'month' => $month]));
            return;
        }

        $periodType  = Payslip::PERIOD_MONTHLY;
        $version     = $existing ? ((int) $existing['version'] + 1) : 1;
        $periodLabel = Payslip::periodLabel($periodType, $periodStart, $periodEnd);
        $snap        = $this->buildMonthlySnapshot($employee, $weeklyRows, $year, $month, $periodStart, $periodEnd, $paymentDate, $periodType, $periodLabel);

        $payslipId = Payslip::create([
            'store_id'          => Auth::storeId(),
            'owner_id'          => Auth::ownerId(),
            'employee_id'       => $employeeId,
            'payroll_result_id' => null,
            'period_start'      => $periodStart,
            'period_end'        => $periodEnd,
            'payment_date'      => $paymentDate,
            'version'           => $version,
            'status'            => Payslip::STATUS_ISSUED,
            'pay_period_type'   => $periodType,
            'period_label'      => $periodLabel,
            'gross_pay'         => $snap['gross_pay'],
            'total_deductions'  => $snap['total_deductions'],
            'net_pay'           => $snap['net_pay'],
            'snapshot_json'     => json_encode($snap, JSON_UNESCAPED_UNICODE),
            'issued_at'         => date('Y-m-d H:i:s'),
            'issued_by'         => Auth::id(),
        ]);

        // DRAFT/CONFIRMED 이전 버전이 있으면 CORRECTED로 정리
        if ($existing && in_array($existing['status'], [Payslip::STATUS_DRAFT, Payslip::STATUS_CONFIRMED], true)) {
            Payslip::updateStatus((int) $existing['id'], Auth::ownerId(), Payslip::STATUS_CORRECTED);
        }

        AuditLog::record(
            'ISSUE_PAYSLIP', 'payslip', $payslipId,
            null,
            ['version' => $version, 'period' => "{$year}-{$month}", 'net_pay' => $snap['net_pay'], 'auto_calc' => true],
            ''
        );

        flash('success', "{$year}년 {$month}월 급여명세서가 발급되었습니다.");
        redirect(url('payslip', 'show', ['id' => $payslipId]));
    }

    /**
     * 주간 계산 결과를 payroll_results에 저장한다. 이미 같은 기간 행이 있으면
     * 보존하고(재사용), 없을 때만 신규 INSERT. PayrollController::saveResult()와
     * 동일한 컬럼 구성을 사용한다.
     */
    private function ensureWeeklyResultSaved(array $result): void
    {
        $ownerId = Auth::ownerId();

        $existing = DB::fetchOne(
            'SELECT id FROM payroll_results
             WHERE employee_id = ? AND owner_id = ? AND period_start = ? AND period_end = ?',
            [$result['employee']['id'], $ownerId, $result['period_start'], $result['period_end']]
        );
        if ($existing) {
            return; // 이미 저장된 주는 재사용
        }

        // 신규 저장 시 고용보험 공제 상태 결정 (saveResult()와 동일 규칙)
        $member    = $result['employee'];
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
        ', [
            $ownerId, $result['employee']['id'], $result['period_start'], $result['period_end'],
            $result['total_work_minutes'], $result['break_minutes'], $result['paid_work_minutes'],
            $result['night_minutes'], $result['overtime_minutes'], $result['holiday_minutes'],
            $result['base_pay'], $result['weekly_holiday_hours'], $result['weekly_holiday_pay'],
            $result['night_premium'], $result['overtime_premium'], $result['holiday_premium'],
            $result['total_pay'], json_encode($result['details'], JSON_UNESCAPED_UNICODE),
            $insStatus,
        ]);
    }

    /** 정정 발급 시 사용: 기존 payroll_results를 재계산값으로 강제 UPDATE (없으면 INSERT) */
    private function forceUpdateWeeklyResult(array $result): void
    {
        $ownerId = Auth::ownerId();
        $member  = $result['employee'];
        $insStatus = (($member['works_at_other_business'] ?? '') === 'YES'
            && ($member['other_business_insurance_enrolled'] ?? '') === 'YES')
            ? 'NEEDS_CHECK' : 'APPLIED';

        $existing = DB::fetchOne(
            'SELECT id FROM payroll_results
             WHERE employee_id=? AND owner_id=? AND period_start=? AND period_end=?',
            [$result['employee']['id'], $ownerId, $result['period_start'], $result['period_end']]
        );

        if ($existing) {
            DB::query('
                UPDATE payroll_results SET
                  total_work_minutes=?, break_minutes=?, paid_work_minutes=?,
                  night_minutes=?, overtime_minutes=?, holiday_minutes=?,
                  base_pay=?, weekly_holiday_hours=?, weekly_holiday_pay=?,
                  night_premium=?, overtime_premium=?, holiday_premium=?,
                  total_pay=?, calculation_detail_json=?, updated_at=NOW()
                WHERE id=?
            ', [
                $result['total_work_minutes'], $result['break_minutes'], $result['paid_work_minutes'],
                $result['night_minutes'], $result['overtime_minutes'], $result['holiday_minutes'],
                $result['base_pay'], $result['weekly_holiday_hours'], $result['weekly_holiday_pay'],
                $result['night_premium'], $result['overtime_premium'], $result['holiday_premium'],
                $result['total_pay'], json_encode($result['details'], JSON_UNESCAPED_UNICODE),
                $existing['id'],
            ]);
        } else {
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
            ', [
                $ownerId, $result['employee']['id'], $result['period_start'], $result['period_end'],
                $result['total_work_minutes'], $result['break_minutes'], $result['paid_work_minutes'],
                $result['night_minutes'], $result['overtime_minutes'], $result['holiday_minutes'],
                $result['base_pay'], $result['weekly_holiday_hours'], $result['weekly_holiday_pay'],
                $result['night_premium'], $result['overtime_premium'], $result['holiday_premium'],
                $result['total_pay'], json_encode($result['details'], JSON_UNESCAPED_UNICODE),
                $insStatus,
            ]);
        }
    }

    /** 정정 발급 (POST) */
    public function correct(): void
    {
        Auth::requireOwner();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(url('payslip', 'index'));
        }
        verify_csrf();

        $originalId = (int) ($_POST['original_payslip_id'] ?? 0);
        $reason     = trim($_POST['correction_reason'] ?? '');

        if ($reason === '') {
            flash('error', '정정 사유를 입력해 주세요.');
            redirect(url('payslip', 'show', ['id' => $originalId]));
            return;
        }

        $original = Payslip::findById($originalId, Auth::ownerId());
        if (!$original || $original['status'] !== Payslip::STATUS_ISSUED) {
            flash('error', '정정 가능한 급여명세서가 아닙니다.');
            redirect(url('payslip', 'index'));
            return;
        }

        $employeeId  = (int) $original['employee_id'];
        $periodStart = $original['period_start'];
        $periodEnd   = $original['period_end'];
        $periodType  = $original['pay_period_type'] ?? Payslip::PERIOD_MONTHLY;

        // MVP 가드: 월급(MONTHLY) 명세서만 정정 가능
        if (!in_array($periodType, Payslip::SUPPORTED_PERIOD_TYPES, true)) {
            flash('error', $periodType . ' 정산 명세서의 정정은 아직 지원하지 않습니다.');
            redirect(url('payslip', 'show', ['id' => $originalId]));
            return;
        }

        $employee = DB::fetchOne(
            'SELECT * FROM store_members WHERE id=? AND store_id=?',
            [$employeeId, (int) $original['store_id']]
        );
        if (!$employee) {
            flash('error', '직원 정보를 찾을 수 없습니다.');
            redirect(url('payslip', 'show', ['id' => $originalId]));
            return;
        }

        // 발급 기간(월)으로부터 연·월 도출
        $year  = (int) substr($periodStart, 0, 4);
        $month = (int) substr($periodStart, 5, 2);

        // 정정 발급: 현재 근무기록으로 주간 결과를 강제 재계산·업데이트 후 재집계
        $settings = Setting::get();
        $calc     = new PayrollCalculator();
        $data     = $calc->calculateMonthlyPayroll($employee, $year, $month, $settings);

        if (empty($data['weeks'])) {
            flash('error', '이 달에 근무기록이 없어 정정 발급할 수 없습니다.');
            redirect(url('payslip', 'show', ['id' => $originalId]));
            return;
        }

        foreach ($data['weeks'] as $weekResult) {
            $this->forceUpdateWeeklyResult($weekResult);
        }

        [$ok, $weeklyRows, $error] = $this->collectMonthlyRows($employeeId, $periodStart, $periodEnd);
        if (!$ok) {
            flash('error', $error);
            redirect(url('payslip', 'show', ['id' => $originalId]));
            return;
        }

        // 1) 원본 CORRECTED 처리
        Payslip::updateStatus($originalId, Auth::ownerId(), Payslip::STATUS_CORRECTED);

        // 2) 새 버전 발급
        $newVersion  = (int) $original['version'] + 1;
        $periodLabel = $original['period_label'] ?: Payslip::periodLabel($periodType, $periodStart, $periodEnd);
        $snap = $this->buildMonthlySnapshot($employee, $weeklyRows, $year, $month, $periodStart, $periodEnd, $original['payment_date'], $periodType, $periodLabel);
        $snap['correction_reason'] = $reason;

        $newId = Payslip::create([
            'store_id'                  => (int) $original['store_id'],
            'owner_id'                  => Auth::ownerId(),
            'employee_id'               => $employeeId,
            'payroll_result_id'         => null,
            'period_start'              => $periodStart,
            'period_end'                => $periodEnd,
            'payment_date'              => $original['payment_date'],
            'version'                   => $newVersion,
            'status'                    => Payslip::STATUS_ISSUED,
            'pay_period_type'           => $periodType,
            'period_label'              => $periodLabel,
            'gross_pay'                 => $snap['gross_pay'],
            'total_deductions'          => $snap['total_deductions'],
            'net_pay'                   => $snap['net_pay'],
            'snapshot_json'             => json_encode($snap, JSON_UNESCAPED_UNICODE),
            'issued_at'                 => date('Y-m-d H:i:s'),
            'issued_by'                 => Auth::id(),
            'corrected_from_payslip_id' => $originalId,
            'correction_reason'         => $reason,
        ]);

        AuditLog::record(
            'CORRECT_PAYSLIP', 'payslip', $newId,
            ['original_id' => $originalId, 'version' => (int) $original['version']],
            ['new_id' => $newId, 'version' => $newVersion],
            $reason
        );

        flash('success', "정정 급여명세서(버전 {$original['version']}→{$newVersion})가 발급되었습니다.");
        redirect(url('payslip', 'show', ['id' => $newId]));
    }

    /** 취소 처리 (POST) */
    public function cancel(): void
    {
        Auth::requireOwner();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(url('payslip', 'index'));
        }
        verify_csrf();

        $id     = (int) ($_POST['payslip_id'] ?? 0);
        $reason = trim($_POST['cancellation_reason'] ?? '');

        if ($reason === '') {
            flash('error', '취소 사유를 필수 입력해 주세요.');
            redirect(url('payslip', 'show', ['id' => $id]));
            return;
        }

        $payslip = Payslip::findById($id, Auth::ownerId());
        if (!$payslip || $payslip['status'] !== Payslip::STATUS_ISSUED) {
            flash('error', '취소 가능한 급여명세서가 아닙니다.');
            redirect(url('payslip', 'index'));
            return;
        }

        Payslip::updateStatus($id, Auth::ownerId(), Payslip::STATUS_CANCELLED, [
            'cancelled_at'        => date('Y-m-d H:i:s'),
            'cancelled_by'        => Auth::id(),
            'cancellation_reason' => $reason,
        ]);

        AuditLog::record('CANCEL_PAYSLIP', 'payslip', $id, $payslip['status'], Payslip::STATUS_CANCELLED, $reason);

        flash('success', '급여명세서가 취소 처리되었습니다.');
        redirect(url('payslip', 'show', ['id' => $id]));
    }

    /** 급여명세서 상세 (사장 / 알바 공용) */
    public function show(): void
    {
        Auth::requireLogin();
        $id = (int) ($_GET['id'] ?? 0);

        if (Auth::isEmployee()) {
            // 알바: 본인 employee_id 검증 + ISSUED 상태만 열람 가능
            $payslip = DB::fetchOne(
                'SELECT p.* FROM payslips p
                   JOIN store_members sm ON sm.id = p.employee_id
                  WHERE p.id=? AND sm.user_id=? AND p.status=?',
                [$id, Auth::id(), Payslip::STATUS_ISSUED]
            );
            if (!$payslip) {
                http_response_code(403);
                echo '접근 권한이 없습니다.';
                exit;
            }

            AuditLog::record('VIEW_PAYSLIP', 'payslip', $id, null, null, '직원 열람');
            AccessLog::record(Auth::id(), Auth::user()['role'] ?? 'unknown', 'payslip', $id, 'VIEW_PAYSLIP');

            $snapshot = json_decode($payslip['snapshot_json'] ?? '[]', true) ?: [];
            render_employee('employee/payslip_show', [
                'title'    => '급여명세서',
                'payslip'  => $payslip,
                'snapshot' => $snapshot,
                'member'   => StoreMember::find(Auth::storeMemberId(), Auth::storeId()),
            ]);
            return;
        }

        // 사장: owner_id 검증
        $payslip = Payslip::findById($id, Auth::ownerId());
        if (!$payslip) {
            http_response_code(403);
            exit;
        }

        AccessLog::record(Auth::id(), Auth::user()['role'] ?? 'unknown', 'payslip', $id, 'VIEW_PAYSLIP');

        $snapshot = json_decode($payslip['snapshot_json'] ?? '[]', true) ?: [];
        $history  = Payslip::allByEmployee((int) $payslip['store_id'], (int) $payslip['employee_id']);

        render('payslip/show', [
            'title'    => '급여명세서 상세',
            'payslip'  => $payslip,
            'snapshot' => $snapshot,
            'history'  => $history,
        ]);
    }

    /** 연·월 → [해당 월 첫날, 마지막날] */
    private function monthPeriod(int $year, int $month): array
    {
        $start = sprintf('%04d-%02d-01', $year, $month);
        $end   = date('Y-m-t', strtotime($start));
        return [$start, $end];
    }

    /**
     * 해당 월 기간에 속하는 주간 payroll_results를 조회·검증한다.
     * @return array{0:bool, 1:array, 2:?string} [성공여부, 주간 rows, 에러메시지]
     */
    private function collectMonthlyRows(int $employeeId, string $periodStart, string $periodEnd): array
    {
        $weeklyRows = DB::fetchAll(
            'SELECT * FROM payroll_results
              WHERE employee_id=? AND owner_id=? AND period_end >= ? AND period_end <= ?
              ORDER BY period_start ASC',
            [$employeeId, Auth::ownerId(), $periodStart, $periodEnd]
        );

        if (empty($weeklyRows)) {
            return [false, [], '이 달에 확정된 급여 내역이 없어 명세서를 발급할 수 없습니다.'];
        }

        // 고용보험 공제 여부 미확정(NEEDS_CHECK) 주간이 있으면 발급 차단
        foreach ($weeklyRows as $row) {
            if (($row['employment_insurance_deduction_status'] ?? 'APPLIED') === 'NEEDS_CHECK') {
                return [false, [], '고용보험 공제 여부가 확인되지 않은 주간이 있습니다. 모든 주간의 고용보험 공제 여부를 먼저 확정해 주세요.'];
            }
        }

        return [true, $weeklyRows, null];
    }

    /**
     * 한 직원의 한 달치 주간 rows → 월 단위 발급 스냅샷.
     * 4대보험 공제는 월간 합산 세전급여 기준으로 계산하여 동결.
     * 한 주라도 고용보험 EXCLUDED면 월 전체 고용보험을 제외한다.
     */
    private function buildMonthlySnapshot(
        array $employee, array $weeklyRows, int $year, int $month,
        string $periodStart, string $periodEnd, ?string $paymentDate,
        string $periodType = Payslip::PERIOD_MONTHLY, ?string $periodLabel = null
    ): array {
        $storeId  = Auth::storeId();
        $store    = Store::findOwned($storeId, Auth::ownerId());
        $settings = Setting::getByStoreId($storeId);

        // 월간 세전급여 = 주간 total_pay 합산
        $grossPay = 0;
        $insExcluded = false;
        foreach ($weeklyRows as $row) {
            $grossPay += (float) ($row['total_pay'] ?? 0);
            if (($row['employment_insurance_deduction_status'] ?? 'APPLIED') === 'EXCLUDED') {
                $insExcluded = true;
            }
        }
        $grossPay = (int) round($grossPay);

        // 4대보험 공제: 월간 합산액 기준 1회 계산
        $deductions = calculateInsuranceDeductions($grossPay, $settings);
        $insStatus  = $insExcluded ? 'EXCLUDED' : 'APPLIED';
        if ($insExcluded) {
            $deductions['total']               -= $deductions['employment_insurance'];
            $deductions['employment_insurance'] = 0;
        }
        $totalDeductions = (int) $deductions['total'];
        $netPay          = $grossPay - $totalDeductions;

        $issuer = Auth::user();

        $periodLabel = $periodLabel ?? Payslip::periodLabel($periodType, $periodStart, $periodEnd);

        return [
            'pay_period_type'  => $periodType,
            'period_label'     => $periodLabel,
            'period'           => ['start' => $periodStart, 'end' => $periodEnd, 'year' => $year, 'month' => $month, 'payment_date' => $paymentDate],
            'employee'         => $employee,
            'store'            => $store,
            'deductions'       => $deductions,
            'ins_status'       => $insStatus,
            'gross_pay'        => $grossPay,
            'total_deductions' => $totalDeductions,
            'net_pay'          => $netPay,
            'monthly'          => [
                'gross_pay'        => $grossPay,
                'total_deductions' => $totalDeductions,
                'net_pay'          => $netPay,
                'week_count'       => count($weeklyRows),
            ],
            'weekly_rows'      => $weeklyRows,
            'payment_date'     => $paymentDate,
            'issued_by_name'   => $issuer['name'] ?? '',
            'issued_at'        => date('Y-m-d H:i:s'),
        ];
    }
}
