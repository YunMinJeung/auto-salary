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

    /** 가산수당: minutes / 60 × 시급 × rate */
    public function calculatePremiumPay(int $minutes, int $hourlyWage, float $rate = 0.5): float
    {
        return round($minutes / 60 * $hourlyWage * $rate, 2);
    }

    /**
     * 직원별 가산수당 해석.
     * type = global   : 사업장 설정 따름 (50% 가산)
     * type = none     : 미적용
     * type = multiplier: 시급 × (value - 1.0) 가산 (예: 1.2배 → 0.2배 가산)
     * type = fixed    : 시간당 value원 고정 가산
     */
    private function resolvePremium(
        string $kind,
        array  $employee,
        array  $settings,
        int    $minutes,
        int    $overrideWage = 0
    ): float {
        if ($minutes === 0) return 0.0;

        $type  = $employee["{$kind}_premium_type"]  ?? 'global';
        $value = (float)($employee["{$kind}_premium_value"] ?? 0);
        $wage  = $overrideWage > 0 ? $overrideWage : (int)$employee['hourly_wage'];

        switch ($type) {
            case 'none':
                return 0.0;
            case 'multiplier':
                return round($minutes / 60 * $wage * max(0.0, $value - 1.0), 2);
            case 'fixed':
                return round($minutes / 60 * $value, 2);
            default: // global
                return $settings["apply_{$kind}_premium"]
                    ? $this->calculatePremiumPay($minutes, $wage)
                    : 0.0;
        }
    }

    /** 가산수당 설정을 사람이 읽기 쉬운 문자열로 변환 */
    private function premiumLabel(string $kind, array $employee, array $settings): string
    {
        $type  = $employee["{$kind}_premium_type"]  ?? 'global';
        $value = $employee["{$kind}_premium_value"];
        switch ($type) {
            case 'none':       return '미적용';
            case 'multiplier': return number_format($value, 2) . '배';
            case 'fixed':      return '+' . number_format((int)$value) . '원/시간';
            default:
                return $settings["apply_{$kind}_premium"] ? '50% 가산 (전역)' : '미적용 (전역)';
        }
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
        $totalFurloughMin = 0; // 사업주 귀책 조기퇴근으로 인한 휴업수당 대상 시간
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

            // 사업주 귀책 조기퇴근: 못 일한 시간 누적 (휴업수당 계산용)
            if (!empty($log['is_employer_early_leave'])) {
                $scheduledDays  = max(1, (int) $employee['weekly_scheduled_days']);
                $dailySchedMin  = (int) round(
                    (float)$employee['weekly_scheduled_hours'] / $scheduledDays * 60
                );
                $totalFurloughMin += max(0, $dailySchedMin - $paidMin);
            }

            $dailyDetails[] = [
                'date'          => $log['work_date'],
                'day_ko'        => dayOfWeekKo($log['work_date']),
                'start'         => $log['start_time'],
                'end'           => $log['end_time'],
                'is_absent'     => false,
                'is_holiday'    => (bool) $log['is_holiday'],
                'is_late'                => (bool) $log['is_late'],
                'is_early_leave'         => (bool) $log['is_early_leave'],
                'is_employer_early_leave'=> !empty($log['is_employer_early_leave']),
                'work_min'      => $workMin,
                'break_min'     => $breakMin,
                'break_auto'    => (bool) $log['break_auto'],
                'paid_min'      => $paidMin,
                'night_min'     => $nightMin,
                'daily_overtime'=> $dailyOvertimeMin,
                'memo'          => $log['memo'] ?? '',
            ];
        }

        // 주간 연장: max(일별 합산, 주 40h 초과분, 단시간 소정 초과분) - 중복 가산 방지
        $weeklyOvertimeMin    = max(0, $totalPaidMin - 2400);
        // 5인 이상 사업장: 단시간근로자의 소정근로시간 초과분도 연장근로 가산 대상
        $scheduledTotalMin    = (int) round((float)$employee['weekly_scheduled_hours'] * 60);
        $scheduledOvertimeMin = (($settings['employee_count_type'] ?? 'under5') === 'over5')
            ? max(0, $totalPaidMin - $scheduledTotalMin)
            : 0;
        $overtimeMin = max($sumDailyOvertime, $weeklyOvertimeMin, $scheduledOvertimeMin);

        // 주휴수당 개근 판정:
        // 결근 기록 없음 + 실제 근무일 >= 소정근로일 모두 충족해야 개근
        $actualWorkDays      = count(array_filter($dailyDetails, fn($d) => !$d['is_absent']));
        $scheduledDays       = (int) $employee['weekly_scheduled_days'];
        $isPerfectAttendance = ($absentDays === 0) && ($actualWorkDays >= $scheduledDays);
        $holidayEnabled      = $employee['weekly_holiday_enabled'] && $settings['auto_weekly_holiday_enabled'];

        // 시급: 주 시작일 기준 해당 시점 시급 (이력 우선, 없으면 현재 시급 fallback)
        $historyWage     = WageHistory::wageAt((int) $employee['id'], $periodStart);
        $effectiveWage   = $historyWage ?? (int) $employee['hourly_wage'];

        // 수습기간 여부 — 주 시작일이 수습 종료일 이전이면 수습 시급 적용
        // 단, 수습 중 시급 인상이 있었으면 높은 쪽 적용 (근로자 유리 원칙)
        $isTrialPeriod   = false;
        if (!empty($employee['trial_end_date']) && !empty($employee['trial_hourly_wage'])
            && $periodStart <= $employee['trial_end_date']) {
            $isTrialPeriod = true;
            $effectiveWage = max((int) $employee['trial_hourly_wage'], $historyWage ?? (int) $employee['hourly_wage']);
        }

        // 최저임금 하한 보정 — 미달 시급은 법정 최저임금으로 자동 대체 (최저임금법 제6조 제3항)
        $legalMinWage  = MinimumWage::effectiveHourlyWage($periodStart);
        $effectiveWage = max($effectiveWage, $legalMinWage);

        $weeklyHolidayHours = 0.0;
        $weeklyHolidayPay   = 0.0;
        if ($holidayEnabled) {
            $scheduledHours  = (float) $employee['weekly_scheduled_hours'];
            $actualHours     = $totalPaidMin / 60;
            // 조퇴 등으로 실제 근로시간이 소정보다 적으면 실제 시간 기준으로 비례 계산
            $effectiveHours  = min($scheduledHours, $actualHours);
            $weeklyHolidayHours = $this->calculateWeeklyHolidayHours(
                $effectiveHours,
                $isPerfectAttendance
            );
            $weeklyHolidayPay = $this->calculateBasePay(
                (int) round($weeklyHolidayHours * 60),
                $effectiveWage
            );
        }

        // 기본급 및 가산수당 (직원별 개별 설정 → 없으면 전역 설정 fallback)
        $basePay         = $this->calculateBasePay($totalPaidMin, $effectiveWage);
        $nightPremium    = $this->resolvePremium('night',    $employee, $settings, $totalNightMin, $effectiveWage);
        $overtimePremium = $this->resolvePremium('overtime', $employee, $settings, $overtimeMin,   $effectiveWage);
        $holidayPremium  = $this->resolvePremium('holiday',  $employee, $settings, $totalHolidayMin, $effectiveWage);

        // 휴업수당: 사업주 귀책 조기퇴근 시 못 일한 시간의 70% — 5인 이상 사업장만 적용
        $furloughPay = 0.0;
        if ($totalFurloughMin > 0 && ($settings['employee_count_type'] ?? 'under5') === 'over5') {
            $furloughPay = round($totalFurloughMin / 60 * $effectiveWage * 0.7, 2);
        }

        $totalPay = $basePay + $weeklyHolidayPay + $nightPremium + $overtimePremium + $holidayPremium + $furloughPay;

        // ── 세전 급여 / 공제 항목 / 실지급액 분리 ───────────────────
        // 세전 급여(gross) = 총 지급액. 공제는 4대보험 기준으로 산출.
        // 소득세·지방소득세는 본 서비스에서 별도 계산하지 않으므로 0.
        $grossPay = $totalPay;
        $ded      = calculateInsuranceDeductions((int) round($grossPay), $settings);

        $dedPension    = (int) $ded['national_pension'];
        $dedHealth     = (int) $ded['health_insurance'];
        $dedCare       = (int) $ded['long_term_care'];
        $dedEmployment = (int) $ded['employment_insurance'];
        $dedIncomeTax  = 0;
        $dedLocalTax   = 0;

        $deductionTotal          = $dedPension + $dedHealth + $dedCare + $dedEmployment + $dedIncomeTax + $dedLocalTax;
        $deductionTotalExcEmpIns = $deductionTotal - $dedEmployment;

        $netPay         = $grossPay - $deductionTotal;
        $netPayExcEmpIns = $grossPay - $deductionTotalExcEmpIns;

        // 고용보험 공제 상태 자동 제안:
        // 다른 사업장에서 고용보험 가입 중으로 표시된 경우 → NEEDS_CHECK (사장 확인 필요)
        $suggestedInsStatus =
            (($employee['works_at_other_business'] ?? '') === 'YES'
                && ($employee['other_business_insurance_enrolled'] ?? '') === 'YES')
            ? 'NEEDS_CHECK'
            : 'APPLIED';

        // 계산 사유 (화면 표시용)
        $reasons = $this->buildReasons(
            $employee, $settings, $isPerfectAttendance,
            $weeklyHolidayHours, $absentDays, $actualWorkDays, $scheduledDays,
            $effectiveHours, $scheduledOvertimeMin, $totalFurloughMin, $furloughPay,
            $isTrialPeriod, $effectiveWage
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
            'furlough_pay'        => $furloughPay,
            'furlough_minutes'    => $totalFurloughMin,
            'is_trial_period'     => $isTrialPeriod,
            'trial_hourly_wage'   => $isTrialPeriod ? $effectiveWage : null,
            'effective_wage'      => $effectiveWage,
            'total_pay'           => $totalPay,
            // ── 세전 / 공제 / 실지급 분리 ──
            'gross_pay'            => $grossPay,
            'deduction_pension'    => $dedPension,
            'deduction_health'     => $dedHealth,
            'deduction_care'       => $dedCare,
            'deduction_employment' => $dedEmployment,
            'deduction_income_tax' => $dedIncomeTax,
            'deduction_local_tax'  => $dedLocalTax,
            'deduction_total'      => $deductionTotal,
            'deduction_total_exc_emp_ins' => $deductionTotalExcEmpIns,
            'net_pay'              => $netPay,
            'net_pay_exc_emp_ins'  => $netPayExcEmpIns,
            'suggested_ins_status' => $suggestedInsStatus,
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
        int   $scheduledDays  = 0,
        float $effectiveHours = 0.0,
        int   $scheduledOvertimeMin = 0,
        int   $furloughMin = 0,
        float $furloughPay = 0.0,
        bool  $isTrialPeriod = false,
        int   $effectiveWage = 0
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
            $reasons[] = ['type' => 'success', 'text' => "소정근로일({$scheduledDays}일) 개근하여 주휴수당이 발생합니다."];
            $eff = $effectiveHours > 0 ? $effectiveHours : $sched;
            if ($eff < $sched) {
                $reasons[] = ['type' => 'warning', 'text' => "조퇴 등으로 실제 근로시간({$eff}h)이 소정({$sched}h)보다 적어 비례 계산합니다."];
            }
            $reasons[] = ['type' => 'info', 'text' => "주휴수당 시간 = min({$eff}, 40) ÷ 40 × 8 = {$weeklyHolidayHours}시간"];
        }

        // 휴업수당 안내
        if ($furloughMin > 0) {
            if ($furloughPay > 0) {
                $reasons[] = ['type' => 'warning', 'text' =>
                    sprintf('사업주 지시 조기퇴근 %d분 — 휴업수당 %s원 (못 일한 시간의 70%%, 5인 이상)',
                        $furloughMin, number_format((int)$furloughPay))];
            } else {
                $reasons[] = ['type' => 'muted', 'text' =>
                    sprintf('사업주 지시 조기퇴근 %d분 — 5인 미만 사업장으로 휴업수당 미적용', $furloughMin)];
            }
        }

        // 단시간 연장근로 가산수당 적용 여부 안내
        if ($scheduledOvertimeMin > 0) {
            $reasons[] = ['type' => 'warning', 'text' =>
                sprintf('소정근로시간 초과 %s분이 연장근로 가산수당 대상에 포함됩니다. (5인 이상 사업장)',
                    $scheduledOvertimeMin)];
        }

        // 수습기간 안내
        if ($isTrialPeriod && $effectiveWage > 0) {
            $reasons[] = ['type' => 'warning', 'text' =>
                sprintf('수습기간 적용 — 수습 시급 %s원으로 계산 (정규 시급 %s원)',
                    number_format($effectiveWage), number_format((int)$employee['hourly_wage']))];
        }

        // 야간·연장·휴일 가산수당 설명
        foreach ([
            ['night',    '야간근로 가산수당(22:00~06:00)'],
            ['overtime', '연장근로 가산수당'],
            ['holiday',  '휴일근로 가산수당'],
        ] as [$kind, $label]) {
            $lbl = $this->premiumLabel($kind, $employee, $settings);
            $effective = $lbl !== '미적용' && $lbl !== '미적용 (전역)';
            $reasons[] = [
                'type' => $effective ? 'info' : 'muted',
                'text' => "{$label}: {$lbl}",
            ];
        }

        return $reasons;
    }

    // ─── 월간 급여 집계 ──────────────────────────────────────

    /**
     * 월간 급여: 한 달을 주별로 나눠 합산.
     *
     * @param array|null $preloadedLogs 이미 조회된 해당 직원의 월간 work_logs 배열.
     *                                  제공되면 주별 DB 재조회를 생략한다 (N+1 방지).
     *                                  null이면 기존처럼 WorkLog::forPeriod()로 조회.
     * @return array  각 주별 결과 배열 + 월 합계
     */
    public function calculateMonthlyPayroll(
        array $employee,
        int   $year,
        int   $month,
        array $settings,
        ?array $preloadedLogs = null
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

            if ($preloadedLogs !== null) {
                // 사전 조회된 로그를 주 범위로 필터링 (DB 재조회 없음)
                $logs = array_filter($preloadedLogs, fn($l) =>
                    $l['work_date'] >= $wStart && $l['work_date'] <= $wEnd
                );
            } else {
                $logs = WorkLog::forPeriod($employee['id'], $wStart, $wEnd);
            }
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
            'furlough_pay'         => 0.0,
            'total_pay'            => 0.0,
            'gross_pay'            => 0.0,
            'deduction_pension'    => 0,
            'deduction_health'     => 0,
            'deduction_care'       => 0,
            'deduction_employment' => 0,
            'deduction_income_tax' => 0,
            'deduction_local_tax'  => 0,
            'deduction_total'      => 0,
            'deduction_total_exc_emp_ins' => 0,
            'net_pay'              => 0.0,
            'net_pay_exc_emp_ins'  => 0.0,
        ];
        foreach ($weeks as $w) {
            foreach (array_keys($monthly) as $k) {
                $monthly[$k] += $w[$k];
            }
        }

        // 월간 고용보험 공제 상태 제안: 한 주라도 NEEDS_CHECK이면 전체 NEEDS_CHECK
        $suggestedInsStatus = 'APPLIED';
        foreach ($weeks as $w) {
            if (($w['suggested_ins_status'] ?? 'APPLIED') === 'NEEDS_CHECK') {
                $suggestedInsStatus = 'NEEDS_CHECK';
                break;
            }
        }

        return [
            'weeks'    => $weeks,
            'monthly'  => $monthly,
            'employee' => $employee,
            'suggested_ins_status' => $suggestedInsStatus,
        ];
    }
}
