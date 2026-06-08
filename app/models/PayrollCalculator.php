<?php
/**
 * 급여 계산 핵심 클래스.
 * 모든 시간 단위는 '분(minute)'. 화면 표시 시 시간 단위로 변환.
 * 각 메서드는 순수 함수(pure function)에 가깝게 작성.
 */
class PayrollCalculator
{
    // ─── 기본 근무시간 계산 ──────────────────────────────────

    /**
     * 총 근무 분 계산. 마감시간 < 시작시간이면 익일 퇴근으로 처리.
     */
    public function calculateWorkMinutes(string $workDate, string $startTime, string $endTime): int
    {
        $start = new DateTime($workDate . ' ' . $startTime);
        $end   = new DateTime($workDate . ' ' . $endTime);

        if ($end <= $start) {
            $end->modify('+1 day');
        }

        return (int) (($end->getTimestamp() - $start->getTimestamp()) / 60);
    }

    /**
     * 근로기준법 기준 자동 휴게시간 (분).
     * 4시간 미만: 0분 / 4시간 이상 8시간 미만: 30분 / 8시간 이상: 60분
     */
    public function calculateAutoBreakMinutes(int $totalWorkMinutes): int
    {
        if ($totalWorkMinutes >= 480) return 60;
        if ($totalWorkMinutes >= 240) return 30;
        return 0;
    }

    /**
     * 유급 근무 분 (총 근무 - 휴게).
     */
    public function calculatePaidWorkMinutes(int $totalWorkMinutes, int $breakMinutes): int
    {
        return max(0, $totalWorkMinutes - $breakMinutes);
    }

    // ─── 야간근로 시간 계산 (22:00 ~ 06:00) ─────────────────

    /**
     * 주어진 근무 구간에서 야간근로 구간(22:00~06:00)과 겹치는 분 계산.
     */
    public function calculateNightMinutes(DateTime $startDT, DateTime $endDT): int
    {
        $workStart = $startDT->getTimestamp();
        $workEnd   = $endDT->getTimestamp();

        // 해당 근무와 겹칠 수 있는 야간 구간 3개 체크 (전날 밤 포함)
        $nightMinutes = 0;
        $checkDate = clone $startDT;
        $checkDate->setTime(0, 0, 0);
        $checkDate->modify('-1 day');

        for ($i = 0; $i < 3; $i++) {
            $nightStart = clone $checkDate;
            $nightStart->setTime(NIGHT_START_HOUR, 0, 0);

            $nightEnd = clone $checkDate;
            $nightEnd->modify('+1 day');
            $nightEnd->setTime(NIGHT_END_HOUR, 0, 0);

            $overlapStart = max($workStart, $nightStart->getTimestamp());
            $overlapEnd   = min($workEnd,   $nightEnd->getTimestamp());

            if ($overlapEnd > $overlapStart) {
                $nightMinutes += (int) (($overlapEnd - $overlapStart) / 60);
            }

            $checkDate->modify('+1 day');
        }

        return $nightMinutes;
    }

    // ─── 연장근로 시간 계산 ──────────────────────────────────

    /**
     * 1일 연장근로 분 (유급 근무 > 8시간 초과분).
     */
    public function calculateDailyOvertimeMinutes(int $paidWorkMinutes): int
    {
        return max(0, $paidWorkMinutes - 480);
    }

    // ─── 주휴수당 ────────────────────────────────────────────

    /**
     * 주휴수당 시간 계산.
     * 조건: 1주 소정근로시간 15시간 이상 + 개근
     * 공식: min(소정근로시간, 40) / 40 × 8
     */
    public function calculateWeeklyHolidayHours(float $weeklyScheduledHours, bool $isPerfectAttendance): float
    {
        if (!$isPerfectAttendance) return 0.0;
        if ($weeklyScheduledHours < 15) return 0.0;
        return round(min($weeklyScheduledHours, 40) / 40 * 8, 4);
    }

    // ─── 급여 계산 ───────────────────────────────────────────

    public function calculateBasePay(int $paidWorkMinutes, int $hourlyWage): float
    {
        return round($paidWorkMinutes / 60 * $hourlyWage, 2);
    }

    /** 가산수당: minutes / 60 × 시급 × rate (야간·연장·휴일 모두 50%) */
    public function calculatePremiumPay(int $minutes, int $hourlyWage, float $rate = 0.5): float
    {
        return round($minutes / 60 * $hourlyWage * $rate, 2);
    }

    // ─── 주간 급여 통합 계산 ─────────────────────────────────

    /**
     * @param array $employee   DB employees 레코드
     * @param array $workLogs   해당 기간 work_logs 레코드 배열
     * @param array $settings   DB settings 레코드
     * @return array            계산 결과 전체 (화면·저장에 공통 사용)
     */
    public function calculateWeeklyPayroll(
        array $employee,
        array $workLogs,
        array $settings,
        string $periodStart,
        string $periodEnd
    ): array {
        $totalWorkMin    = 0;
        $totalBreakMin   = 0;
        $totalPaidMin    = 0;
        $totalNightMin   = 0;
        $totalHolidayMin = 0;
        $sumDailyOvertime = 0;
        $absentDays      = 0;
        $dailyDetails    = [];

        foreach ($workLogs as $log) {
            // 결근 처리
            if ($log['is_absent']) {
                $absentDays++;
                $dailyDetails[] = [
                    'date'          => $log['work_date'],
                    'day_ko'        => dayOfWeekKo($log['work_date']),
                    'is_absent'     => true,
                    'is_holiday'    => (bool)$log['is_holiday'],
                ];
                continue;
            }

            $workMin = $this->calculateWorkMinutes(
                $log['work_date'], $log['start_time'], $log['end_time']
            );

            $breakMin = $log['break_auto']
                ? $this->calculateAutoBreakMinutes($workMin)
                : (int) $log['break_minutes'];

            // 휴게시간은 총 근무시간을 초과할 수 없음
            $breakMin = min($breakMin, $workMin);

            $paidMin = $this->calculatePaidWorkMinutes($workMin, $breakMin);

            $startDT = new DateTime($log['work_date'] . ' ' . $log['start_time']);
            $endDT   = new DateTime($log['work_date'] . ' ' . $log['end_time']);
            if ($endDT <= $startDT) {
                $endDT->modify('+1 day');
            }

            $nightMin        = $this->calculateNightMinutes($startDT, $endDT);
            $dailyOvertimeMin = $this->calculateDailyOvertimeMinutes($paidMin);

            $totalWorkMin    += $workMin;
            $totalBreakMin   += $breakMin;
            $totalPaidMin    += $paidMin;
            $totalNightMin   += $nightMin;
            $sumDailyOvertime += $dailyOvertimeMin;

            if ($log['is_holiday']) {
                $totalHolidayMin += $paidMin;
            }

            $dailyDetails[] = [
                'date'          => $log['work_date'],
                'day_ko'        => dayOfWeekKo($log['work_date']),
                'start'         => $log['start_time'],
                'end'           => $log['end_time'],
                'is_absent'     => false,
                'is_holiday'    => (bool) $log['is_holiday'],
                'is_late'       => (bool) $log['is_late'],
                'is_early_leave'=> (bool) $log['is_early_leave'],
                'work_min'      => $workMin,
                'break_min'     => $breakMin,
                'break_auto'    => (bool) $log['break_auto'],
                'paid_min'      => $paidMin,
                'night_min'     => $nightMin,
                'daily_overtime'=> $dailyOvertimeMin,
                'memo'          => $log['memo'] ?? '',
            ];
        }

        // 주간 연장: max(일별 합산, 주 40h 초과분) - 중복 가산 방지
        $weeklyOvertimeMin = max(0, $totalPaidMin - 2400);
        $overtimeMin       = max($sumDailyOvertime, $weeklyOvertimeMin);

        // 주휴수당 개근 판정:
        // 결근 기록 없음 + 실제 근무일 >= 소정근로일 모두 충족해야 개근
        $actualWorkDays      = count(array_filter($dailyDetails, fn($d) => !$d['is_absent']));
        $scheduledDays       = (int) $employee['weekly_scheduled_days'];
        $isPerfectAttendance = ($absentDays === 0) && ($actualWorkDays >= $scheduledDays);
        $holidayEnabled      = $employee['weekly_holiday_enabled'] && $settings['auto_weekly_holiday_enabled'];

        $weeklyHolidayHours = 0.0;
        $weeklyHolidayPay   = 0.0;
        if ($holidayEnabled) {
            $weeklyHolidayHours = $this->calculateWeeklyHolidayHours(
                (float) $employee['weekly_scheduled_hours'],
                $isPerfectAttendance
            );
            $weeklyHolidayPay = $this->calculateBasePay(
                (int) round($weeklyHolidayHours * 60),
                (int) $employee['hourly_wage']
            );
        }

        // 기본급 및 가산수당
        $basePay        = $this->calculateBasePay($totalPaidMin, (int) $employee['hourly_wage']);
        $nightPremium   = $settings['apply_night_premium']
                          ? $this->calculatePremiumPay($totalNightMin, (int) $employee['hourly_wage'])
                          : 0.0;
        $overtimePremium = $settings['apply_overtime_premium']
                          ? $this->calculatePremiumPay($overtimeMin, (int) $employee['hourly_wage'])
                          : 0.0;
        $holidayPremium = $settings['apply_holiday_premium']
                          ? $this->calculatePremiumPay($totalHolidayMin, (int) $employee['hourly_wage'])
                          : 0.0;

        $totalPay = $basePay + $weeklyHolidayPay + $nightPremium + $overtimePremium + $holidayPremium;

        // 계산 사유 (화면 표시용)
        $reasons = $this->buildReasons(
            $employee, $settings, $isPerfectAttendance,
            $weeklyHolidayHours, $absentDays, $actualWorkDays, $scheduledDays
        );

        return [
            'employee'            => $employee,
            'period_start'        => $periodStart,
            'period_end'          => $periodEnd,
            'total_work_minutes'  => $totalWorkMin,
            'break_minutes'       => $totalBreakMin,
            'paid_work_minutes'   => $totalPaidMin,
            'night_minutes'       => $totalNightMin,
            'overtime_minutes'    => $overtimeMin,
            'holiday_minutes'     => $totalHolidayMin,
            'base_pay'            => $basePay,
            'weekly_holiday_hours'=> $weeklyHolidayHours,
            'weekly_holiday_pay'  => $weeklyHolidayPay,
            'night_premium'       => $nightPremium,
            'overtime_premium'    => $overtimePremium,
            'holiday_premium'     => $holidayPremium,
            'total_pay'           => $totalPay,
            'is_perfect_attendance' => $isPerfectAttendance,
            'absent_days'         => $absentDays,
            'holiday_enabled'     => $holidayEnabled,
            'reasons'             => $reasons,
            'details'             => $dailyDetails,
            'settings'            => $settings,
        ];
    }

    private function buildReasons(
        array $employee,
        array $settings,
        bool  $isPerfectAttendance,
        float $weeklyHolidayHours,
        int   $absentDays,
        int   $actualWorkDays = 0,
        int   $scheduledDays  = 0
    ): array {
        $reasons = [];
        $sched   = (float) $employee['weekly_scheduled_hours'];

        if (!$employee['weekly_holiday_enabled']) {
            $reasons[] = ['type' => 'info', 'text' => '해당 직원은 주휴수당 계산 대상에서 제외되어 있습니다.'];
        } elseif (!$settings['auto_weekly_holiday_enabled']) {
            $reasons[] = ['type' => 'info', 'text' => '사업장 설정에서 주휴수당 자동 계산이 꺼져 있습니다.'];
        } elseif ($sched < 15) {
            $reasons[] = ['type' => 'warning', 'text' => "주 소정근로시간({$sched}시간)이 15시간 미만이어서 주휴수당 대상이 아닙니다."];
        } elseif ($absentDays > 0) {
            $reasons[] = ['type' => 'warning', 'text' => "결근 기록 {$absentDays}건이 있어 주휴수당이 발생하지 않습니다."];
        } elseif ($actualWorkDays < $scheduledDays) {
            $reasons[] = ['type' => 'warning', 'text' => "실제 근무일({$actualWorkDays}일)이 소정근로일({$scheduledDays}일)에 미달하여 주휴수당이 발생하지 않습니다."];
        } else {
            $reasons[] = ['type' => 'success', 'text' => "주 소정근로시간 {$sched}시간 이상이고 소정근로일({$scheduledDays}일) 개근하여 주휴수당이 발생합니다."];
            $reasons[] = ['type' => 'info',    'text' => "주휴수당 시간 = min({$sched}, 40) ÷ 40 × 8 = {$weeklyHolidayHours}시간"];
        }

        if ($settings['apply_night_premium']) {
            $reasons[] = ['type' => 'info', 'text' => '사업장 설정에 따라 야간근로 가산수당(22:00~06:00, 50%)을 적용합니다.'];
        } else {
            $reasons[] = ['type' => 'muted', 'text' => '야간근로 가산수당이 설정에서 꺼져 있습니다.'];
        }

        if ($settings['apply_overtime_premium']) {
            $reasons[] = ['type' => 'info', 'text' => '사업장 설정에 따라 연장근로 가산수당(50%)을 적용합니다.'];
        }

        return $reasons;
    }

    // ─── 월간 급여 집계 ──────────────────────────────────────

    /**
     * 월간 급여: 한 달을 주별로 나눠 합산.
     * @return array  각 주별 결과 배열 + 월 합계
     */
    public function calculateMonthlyPayroll(
        array $employee,
        int   $year,
        int   $month,
        array $settings
    ): array {
        $firstDay = new DateTime(sprintf('%04d-%02d-01', $year, $month));
        $lastDay  = clone $firstDay;
        $lastDay->modify('last day of this month');

        // 월의 첫 주 월요일 ~ 마지막 주 일요일을 단위로 반복
        [$weekStart] = getWeekRange($firstDay->format('Y-m-d'));
        $cursor  = new DateTime($weekStart);
        $weeks   = [];

        while ($cursor <= $lastDay) {
            $wStart = $cursor->format('Y-m-d');
            $wEnd   = (clone $cursor)->modify('+6 days')->format('Y-m-d');

            $logs   = WorkLog::forPeriod($employee['id'], $wStart, $wEnd);
            // 해당 월에 포함되는 날의 로그만 사용
            $logs   = array_filter($logs, fn($l) =>
                $l['work_date'] >= $firstDay->format('Y-m-d') &&
                $l['work_date'] <= $lastDay->format('Y-m-d')
            );

            if (!empty($logs)) {
                $weeks[] = $this->calculateWeeklyPayroll(
                    $employee, array_values($logs), $settings, $wStart, $wEnd
                );
            }

            $cursor->modify('+7 days');
        }

        // 월 합계
        $monthly = [
            'total_work_minutes'   => 0,
            'paid_work_minutes'    => 0,
            'night_minutes'        => 0,
            'overtime_minutes'     => 0,
            'base_pay'             => 0.0,
            'weekly_holiday_pay'   => 0.0,
            'night_premium'        => 0.0,
            'overtime_premium'     => 0.0,
            'holiday_premium'      => 0.0,
            'total_pay'            => 0.0,
        ];
        foreach ($weeks as $w) {
            foreach (array_keys($monthly) as $k) {
                $monthly[$k] += $w[$k];
            }
        }

        return ['weeks' => $weeks, 'monthly' => $monthly, 'employee' => $employee];
    }
}
