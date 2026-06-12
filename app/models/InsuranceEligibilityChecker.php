<?php
/**
 * 4대보험 가입 의무 순수 계산기.
 * DB 접근 없음 — 입력 데이터만으로 판단 반환.
 */
class InsuranceEligibilityChecker
{
    /** 주 소정근로시간 → 월 소정근로시간 (365일/7일/12개월 = 4.345주/월) */
    public function calculateMonthlyScheduledHours(float $weeklyHours): float
    {
        return round($weeklyHours * 4.345, 1);
    }

    /**
     * 입사일·계약 종료일 → 고용 기간 구분.
     * 종료일 없음(기간의 정함 없음) = 장기 고용 전제 → over3m.
     */
    public function calculateDurationFromDates(?string $startDate, ?string $endDate): string
    {
        if (!$startDate) return 'undefined';
        if (!$endDate)   return 'over3m';
        $s    = date_create($startDate);
        $e    = date_create($endDate);
        if (!$s || !$e) return 'undefined';
        $days = (int)date_diff($s, $e)->days;
        if ($days < 30) return 'under1m';
        if ($days < 90) return '1m_to_3m';
        return 'over3m';
    }

    /**
     * 국민연금 가입 의무 판단.
     * 초단시간(주 15시간 미만) 또는 1개월 미만 계약 → 적용 제외
     * 4.345 배수 사용 시 14h×4.345=60.83으로 오판되므로 주 시간 기준 직접 사용.
     */
    public function checkNationalPension(float $weeklyHours, string $duration): string
    {
        if ($duration === 'under1m') return 'possibly_exempt';
        if ($weeklyHours < 15)       return 'possibly_exempt';
        return 'likely_required';
    }

    /**
     * 건강보험 가입 의무 판단 — 국민연금과 동일 기준.
     */
    public function checkHealthInsurance(float $weeklyHours, string $duration): string
    {
        if ($duration === 'under1m') return 'possibly_exempt';
        if ($weeklyHours < 15)       return 'possibly_exempt';
        return 'likely_required';
    }

    /**
     * 고용보험 가입 의무 판단.
     * 기준: 「고용보험법」 시행령 제3조
     *
     * ① 주 15시간 이상 → 의무 가입 (계약기간 무관)
     * ② 주 15시간 미만 + 3개월 이상 계속 근무 → 의무 가입 (예외 적용)
     * ③ 주 15시간 미만 + 3개월 미만 → 원칙적 제외
     * ④ 65세 이후 신규 고용 → 적용 제외
     */
    public function checkEmploymentInsurance(float $weeklyHours, string $duration, bool $over65NewHire = false): string
    {
        if ($over65NewHire)          return 'possibly_exempt';
        if ($weeklyHours >= 15)      return 'likely_required';  // ①
        // 이하 주 15시간 미만
        if ($duration === 'over3m')  return 'likely_required';  // ②
        if ($duration === 'under1m' || $duration === '1m_to_3m') return 'possibly_exempt'; // ③
        return 'needs_review';
    }

    /** 산재보험: 근로자 수·업종 무관 사용자 전액 부담. 근로자 공제 없음. */
    public function checkIndustrialAccident(): string
    {
        return 'required';
    }

    /**
     * 65세 이후 신규 고용 여부 판단.
     * 입사일 기준으로 만 65세 이상이면 true.
     */
    public function isOver65AtHire(?string $dateOfBirth, ?string $employmentStartDate): bool
    {
        if (!$dateOfBirth || !$employmentStartDate) return false;
        $birth = date_create($dateOfBirth);
        $start = date_create($employmentStartDate);
        if (!$birth || !$start) return false;
        return date_diff($birth, $start)->y >= 65;
    }

    /**
     * 전체 판단 실행.
     *
     * @param array $employeeData 최소 필요 키: weekly_scheduled_hours, expected_employment_duration
     *                            선택 키: date_of_birth, employment_start_date (65세 판단용)
     */
    public function checkAll(array $employeeData): array
    {
        $weeklyHours   = (float)($employeeData['weekly_scheduled_hours']     ?? 40);
        $duration      = $this->calculateDurationFromDates(
            $employeeData['employment_start_date'] ?? null,
            $employeeData['employment_end_date']   ?? null
        );
        $empType       = $weeklyHours < 15 ? 'short_hours' : 'regular';
        $monthly       = $this->calculateMonthlyScheduledHours($weeklyHours);
        $over65        = $this->isOver65AtHire(
            $employeeData['date_of_birth']        ?? null,
            $employeeData['employment_start_date'] ?? null
        );

        $np = $this->checkNationalPension($weeklyHours, $duration);
        $hi = $this->checkHealthInsurance($weeklyHours, $duration);
        $ei = $this->checkEmploymentInsurance($weeklyHours, $duration, $over65);
        $ia = $this->checkIndustrialAccident();

        return [
            'weekly_hours'           => $weeklyHours,
            'monthly_hours'          => $monthly,
            'duration'               => $duration,
            'employment_type'        => $empType,
            'over65_new_hire'        => $over65,
            'national_pension'       => $np,
            'health_insurance'       => $hi,
            'employment_insurance'   => $ei,
            'industrial_accident'    => $ia,
            'has_high_risk'          => self::hasHighRisk($np, $hi, $ei),
        ];
    }

    public static function hasHighRisk(string $np, string $hi, string $ei): bool
    {
        return in_array('likely_required', [$np, $hi, $ei], true);
    }
}
