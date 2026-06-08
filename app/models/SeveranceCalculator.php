<?php
/**
 * 퇴직금 계산 (근로자퇴직급여 보장법 제8조)
 *
 * 퇴직금 = 평균임금 × 30일 × (재직일수 ÷ 365)
 *
 * 평균임금 = 퇴직일 전날 기준 소급 3개월 총임금 ÷ 3개월 달력일수
 * 평균임금 < 통상임금 일급 → 통상임금으로 대체 (근기법 제2조 제2항)
 */
class SeveranceCalculator
{
    public function calculate(array $employee, string $retireDate, array $settings): array
    {
        $startDT  = new DateTime($employee['employment_start_date']);
        $retireDT = new DateTime($retireDate);

        $diff        = $startDT->diff($retireDT);
        $serviceDays = (int)$diff->days;

        // ─── 적격 판정 ────────────────────────────────────────
        $ineligible = [];
        if ($serviceDays < 365) {
            $ineligible[] = "계속근무기간 {$serviceDays}일 (1년 365일 미만)";
        }
        if ((float)$employee['weekly_scheduled_hours'] < 15) {
            $ineligible[] = "주 소정근로시간 {$employee['weekly_scheduled_hours']}시간 (15시간 미만)";
        }

        // ─── 평균임금 산정 기간 ────────────────────────────────
        // 퇴직일 당일은 근무일이 아니므로 "퇴직일 전날"부터 소급 3개월
        $periodEnd   = (clone $retireDT)->modify('-1 day');
        $periodStart = (clone $periodEnd)->modify('-3 months')->modify('+1 day');
        $calendarDays = $periodStart->diff($periodEnd)->days + 1;

        // ─── 3개월 임금 합산 ──────────────────────────────────
        $wageData    = $this->sumWages($employee, $periodStart, $periodEnd, $settings);
        $totalWage3m = $wageData['total'];
        $noLogData   = empty($wageData['weeks']);

        $avgDailyWage = $calendarDays > 0 ? $totalWage3m / $calendarDays : 0.0;

        // ─── 통상임금 일급 (시급 × 1일 소정근로시간) ─────────
        $ordinaryDailyWage = $this->calcOrdinaryDailyWage($employee);

        $usedOrdinary      = $avgDailyWage < $ordinaryDailyWage;
        $effectiveDailyWage = $usedOrdinary ? $ordinaryDailyWage : $avgDailyWage;

        // ─── 퇴직금 ───────────────────────────────────────────
        $severancePay = $effectiveDailyWage * 30 * ($serviceDays / 365);

        return [
            'employee'            => $employee,
            'retire_date'         => $retireDate,
            'start_date'          => $employee['employment_start_date'],
            'service_years'       => $diff->y,
            'service_months'      => $diff->m,
            'service_day_rem'     => $diff->d,
            'service_days'        => $serviceDays,
            'eligible'            => empty($ineligible),
            'ineligible_reasons'  => $ineligible,
            'period_start'        => $periodStart->format('Y-m-d'),
            'period_end'          => $periodEnd->format('Y-m-d'),
            'calendar_days'       => $calendarDays,
            'total_wage_3m'       => $totalWage3m,
            'no_log_data'         => $noLogData,
            'avg_daily_wage'      => $avgDailyWage,
            'ordinary_daily_wage' => $ordinaryDailyWage,
            'used_ordinary'       => $usedOrdinary,
            'effective_daily_wage'=> $effectiveDailyWage,
            'severance_pay'       => $severancePay,
            'weeks'               => $wageData['weeks'],
        ];
    }

    /**
     * 통상임금 일급 = 시급 × (주 소정근로시간 ÷ 주 소정근로일수)
     */
    private function calcOrdinaryDailyWage(array $employee): float
    {
        $wage       = (int)$employee['hourly_wage'];
        $weekHours  = (float)$employee['weekly_scheduled_hours'];
        $weekDays   = max(1, (int)$employee['weekly_scheduled_days']);
        return $wage * ($weekHours / $weekDays);
    }

    /**
     * 기간 내 work_logs를 주 단위로 묶어 임금 합산.
     * PayrollCalculator를 그대로 재사용해 가산수당·주휴수당 반영.
     */
    private function sumWages(
        array $employee,
        DateTime $periodStart,
        DateTime $periodEnd,
        array $settings
    ): array {
        $calc      = new PayrollCalculator();
        $weeks     = [];
        $total     = 0.0;
        $pStartStr = $periodStart->format('Y-m-d');
        $pEndStr   = $periodEnd->format('Y-m-d');

        [$wStartStr] = getWeekRange($pStartStr);
        $cursor      = new DateTime($wStartStr);

        while ($cursor->format('Y-m-d') <= $pEndStr) {
            $wStart = $cursor->format('Y-m-d');
            $wEnd   = (clone $cursor)->modify('+6 days')->format('Y-m-d');

            $logs = WorkLog::forPeriod($employee['id'], $wStart, $wEnd);
            $logs = array_filter(
                $logs,
                fn($l) => $l['work_date'] >= $pStartStr && $l['work_date'] <= $pEndStr
            );

            if (!empty($logs)) {
                $w       = $calc->calculateWeeklyPayroll(
                    $employee, array_values($logs), $settings, $wStart, $wEnd
                );
                $weeks[] = $w;
                $total  += $w['total_pay'];
            }

            $cursor->modify('+7 days');
        }

        return ['weeks' => $weeks, 'total' => $total];
    }
}
