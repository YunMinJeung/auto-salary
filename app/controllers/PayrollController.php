<?php
class PayrollController
{
    public function weekly(): void
    {
        $employees = Employee::all();
        $settings  = Setting::get();
        $result    = null;
        $errors    = [];

        $employeeId = (int) ($_GET['employee_id'] ?? 0);
        $weekDate   = $_GET['week_date'] ?? date('Y-m-d');

        [$periodStart, $periodEnd] = getWeekRange($weekDate);

        if ($employeeId) {
            $employee = Employee::find($employeeId);
            if (!$employee) {
                flash('error', '직원을 찾을 수 없습니다.');
                redirect(url('payroll', 'weekly'));
            }

            $workLogs = WorkLog::forPeriod($employeeId, $periodStart, $periodEnd);
            $calc     = new PayrollCalculator();
            $result   = $calc->calculateWeeklyPayroll(
                $employee, $workLogs, $settings, $periodStart, $periodEnd
            );
        }

        // 저장 요청
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save']) && $result) {
            verify_csrf();
            $this->saveResult($result);
            flash('success', '급여 계산 결과가 저장되었습니다.');
            redirect(url('payroll', 'weekly', [
                'employee_id' => $employeeId,
                'week_date'   => $weekDate,
            ]));
        }

        // 근무 기간 연도 기준 최저시급 조회 (최저임금 위반 경고용)
        $periodMinWage = MinimumWage::effectiveHourlyWage($periodStart);

        render('payroll/weekly', [
            'title'         => '주간 급여 계산',
            'employees'     => $employees,
            'employeeId'    => $employeeId,
            'weekDate'      => $weekDate,
            'periodStart'   => $periodStart,
            'periodEnd'     => $periodEnd,
            'result'        => $result,
            'settings'      => $settings,
            'periodMinWage' => $periodMinWage,
        ]);
    }

    public function monthly(): void
    {
        $employees = Employee::all();
        $settings  = Setting::get();
        $data      = null;

        $employeeId = (int) ($_GET['employee_id'] ?? 0);
        $year       = (int) ($_GET['year']  ?? date('Y'));
        $month      = (int) ($_GET['month'] ?? date('n'));

        if ($employeeId) {
            $employee = Employee::find($employeeId);
            if (!$employee) {
                flash('error', '직원을 찾을 수 없습니다.');
                redirect(url('payroll', 'monthly'));
            }

            $calc = new PayrollCalculator();
            $data = $calc->calculateMonthlyPayroll($employee, $year, $month, $settings);
        }

        render('payroll/monthly', [
            'title'      => '월간 급여 요약',
            'employees'  => $employees,
            'employeeId' => $employeeId,
            'year'       => $year,
            'month'      => $month,
            'data'       => $data,
            'settings'   => $settings,
        ]);
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
            DB::query('
                INSERT INTO payroll_results
                  (owner_id, employee_id, period_start, period_end,
                   total_work_minutes, break_minutes, paid_work_minutes,
                   night_minutes, overtime_minutes, holiday_minutes,
                   base_pay, weekly_holiday_hours, weekly_holiday_pay,
                   night_premium, overtime_premium, holiday_premium,
                   total_pay, calculation_detail_json)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
            ', array_merge(
                [$ownerId, $result['employee']['id'], $result['period_start'], $result['period_end']],
                $params
            ));
        }
    }
}
