<?php
/**
 * 노무 리스크 자동 감지 엔진.
 *
 * 입력된 근무기록과 급여 계산 결과를 기준으로 노무 리스크를 감지하여
 * LaborRiskAlert 로 저장한다. 실제 법적 판단을 대체하지 않으며,
 * "입력된 조건 기준 참고용 안내"임을 메시지에 명시한다.
 */
class LaborRiskEngine
{
    /**
     * 근무기록 1건에 대한 리스크 감지.
     *
     * @param int        $logId     근무기록 id
     * @param array      $log       수정 후(또는 신규) 근무기록 데이터 (work_date, start_time, end_time, break_minutes, memo, is_absent ...)
     * @param array      $employee  employees 행 (store_member_id 포함 가능)
     * @param int        $ownerId
     * @param bool       $isEdit    점주 수정 여부
     * @param array|null $beforeLog 수정 전 근무기록 (isEdit=true 일 때)
     */
    public static function detectForWorkLog(
        int $logId,
        array $log,
        array $employee,
        int $ownerId,
        bool $isEdit = false,
        ?array $beforeLog = null
    ): void {
        $storeMemberId = isset($employee['store_member_id']) ? (int) $employee['store_member_id'] : null;

        $isAbsent = isset($log['is_absent']) && $log['is_absent'];

        // ── 1. BREAK_TIME_MISSING (결근이 아닐 때만) ──────────────────
        if (!$isAbsent && !empty($log['start_time']) && !empty($log['end_time'])) {
            $calc     = new PayrollCalculator();
            $workMin  = $calc->calculateWorkMinutes(
                $log['work_date'],
                $log['start_time'],
                $log['end_time']
            );
            if (!empty($log['break_auto'])) {
                if ($workMin >= 480)      $breakMin = 60;
                elseif ($workMin >= 240)  $breakMin = 30;
                else                      $breakMin = 0;
            } else {
                $breakMin = (int) ($log['break_minutes'] ?? 0);
            }

            $required = 0;
            if ($workMin >= 480) {
                $required = 60;
            } elseif ($workMin >= 240) {
                $required = 30;
            }

            // ── 4. EXCESSIVE_WORK_HOURS (유급근무시간 > 600분=10시간) ──
            $paidMin = $calc->calculatePaidWorkMinutes($workMin, $breakMin);
            if ($paidMin > 600) {
                LaborRiskAlert::upsertByCode(
                    $ownerId,
                    $storeMemberId,
                    'EXCESSIVE_WORK_HOURS',
                    'warning',
                    'employee_visible_required',
                    '과도한 근무시간',
                    '입력된 근무시간이 10시간을 초과합니다. 5인 이상 사업장의 경우 연장근로(법정근무 8시간 초과)는 '
                        . '주 12시간을 한도로 하며, 근로자 동의가 필요합니다.',
                    '오늘 근무기록의 근무시간이 10시간을 초과합니다. 연장근로 동의 여부를 확인해 주세요.',
                    '근로기준법 제53조',
                    'work_log',
                    $logId
                );
            }

            if ($required > 0 && $breakMin < $required) {
                $workHours = self::fmtHours($workMin);
                $message = sprintf(
                    '입력된 근로시간(%s시간) 기준 휴게시간이 %d분입니다. '
                    . '근로기준법상 4시간 근로 시 30분, 8시간 근로 시 60분 이상 부여해야 합니다. '
                    . '(입력된 조건 기준이며, 실제 부여 여부는 별도 확인이 필요합니다.)',
                    $workHours,
                    $breakMin
                );
                $empMsg = sprintf(
                    '오늘 근무기록의 휴게시간을 확인해 주세요. (기준 %d분, 입력 %d분)',
                    $required,
                    $breakMin
                );

                LaborRiskAlert::upsertByCode(
                    $ownerId,
                    $storeMemberId,
                    'BREAK_TIME_MISSING',
                    'warning',
                    'employee_visible_required',
                    '휴게시간 부족 가능성',
                    $message,
                    $empMsg,
                    '근로기준법 제54조',
                    'work_log',
                    $logId
                );
            }
        }

        // ── 2. ATTENDANCE_EDITED_BY_OWNER (점주 수정 + 실제 변경된 경우만) ──
        if ($isEdit && $beforeLog !== null) {
            $bStart = (string) ($beforeLog['start_time'] ?? '');
            $bEnd   = (string) ($beforeLog['end_time'] ?? '');
            $bBreak = (int) ($beforeLog['break_minutes'] ?? 0);

            $aStart = (string) ($log['start_time'] ?? '');
            $aEnd   = (string) ($log['end_time'] ?? '');
            $aBreak = (int) ($log['break_minutes'] ?? 0);

            $changed = (self::hm($bStart) !== self::hm($aStart))
                    || (self::hm($bEnd)   !== self::hm($aEnd))
                    || ($bBreak !== $aBreak);

            if ($changed) {
                $reason = trim((string) ($log['memo'] ?? '')) ?: '사유 미입력';

                $message = sprintf(
                    '점주가 근무기록을 수정했습니다. '
                    . '수정 전: %s~%s (휴게 %d분) → 수정 후: %s~%s (휴게 %d분). 사유: %s',
                    self::hm($bStart), self::hm($bEnd), $bBreak,
                    self::hm($aStart), self::hm($aEnd), $aBreak,
                    $reason
                );
                $empMsg = sprintf(
                    '사장님이 근무기록을 수정했습니다. '
                    . '수정 전: %s~%s (휴게 %d분) → 수정 후: %s~%s (휴게 %d분). 사유: %s',
                    self::hm($bStart), self::hm($bEnd), $bBreak,
                    self::hm($aStart), self::hm($aEnd), $aBreak,
                    $reason
                );

                LaborRiskAlert::insert(
                    $ownerId,
                    $storeMemberId,
                    'ATTENDANCE_EDITED_BY_OWNER',
                    'info',
                    'employee_visible_required',
                    '근무기록 점주 수정',
                    $message,
                    $empMsg,
                    '근로기준법 제48조',
                    'work_log',
                    $logId
                );
            }
        }
    }

    /**
     * 주간 급여 계산 결과에 대한 리스크 감지.
     *
     * @return array 감지된 알림 목록 (각: alert_id, alert_code, severity, title, message)
     */
    public static function detectForPayroll(
        array $result,
        array $employee,
        int $ownerId,
        string $weekStart
    ): array {
        $storeMemberId = isset($employee['store_member_id']) ? (int) $employee['store_member_id'] : null;
        $detected = [];

        // ── WEEKLY_HOLIDAY_PAY_POSSIBLY_REQUIRED ──────────────────────
        $scheduledHours  = (float) ($employee['weekly_scheduled_hours'] ?? 0);
        $absentDays      = (int) ($result['absent_days'] ?? 0);
        $paidMinutes     = (int) ($result['paid_work_minutes'] ?? 0);
        $weeklyHolidayPay = (float) ($result['weekly_holiday_pay'] ?? 0);
        $isPerfect       = !empty($result['is_perfect_attendance']);

        if ($scheduledHours >= 15
            && $absentDays === 0
            && $paidMinutes > 0
            && $weeklyHolidayPay == 0
            && $isPerfect === true
        ) {
            $title   = '주휴수당 미지급 가능성';
            $message = sprintf(
                '이 직원의 소정근로시간(%s시간)이 주 15시간 이상이고 해당 주 개근(결근 0일)으로 입력되어 있으나, '
                . '현재 계산 결과의 주휴수당이 0원입니다. 근로기준법 제55조에 따라 주 15시간 이상 근로하고 '
                . '소정근로일을 개근한 경우 주휴수당이 발생할 수 있습니다. 직원의 주휴수당 설정(주휴 적용 여부)과 '
                . '소정근로시간 입력값을 확인하세요. (입력된 근무조건 기준 참고용 안내이며, 실제 지급 여부는 '
                . '근로계약 및 실제 근무형태에 따라 달라질 수 있습니다.)',
                self::fmtHours((int) round($scheduledHours * 60))
            );
            $empMsg = '이번 주 주휴수당 계산 상태를 확인해 주세요. '
                    . '입력된 근무조건 기준으로 주휴수당 발생 가능성이 있습니다.';

            $alertId = LaborRiskAlert::upsertByCode(
                $ownerId,
                $storeMemberId,
                'WEEKLY_HOLIDAY_PAY_POSSIBLY_REQUIRED',
                'danger',
                'employee_visible_required',
                $title,
                $message,
                $empMsg,
                '근로기준법 제55조',
                'payroll_week',
                (int) $employee['id']
            );

            $detected[] = [
                'alert_id'   => $alertId,
                'alert_code' => 'WEEKLY_HOLIDAY_PAY_POSSIBLY_REQUIRED',
                'severity'   => 'danger',
                'title'      => $title,
                'message'    => $message,
            ];
        }

        return $detected;
    }

    /**
     * 직원 정보 저장 시 리스크 감지 (최저임금·4대보험·수습기간).
     *
     * @param array $member   store_members 행 (id, hourly_wage, trial_end_date ...)
     * @param int   $ownerId
     * @param int   $storeId  EmployeeInsuranceSetting 조회용
     * @param array $settings settings 행 (minimum_wage 포함)
     */
    public static function detectForMember(array $member, int $ownerId, int $storeId, array $settings): void
    {
        $storeMemberId = (int) ($member['id'] ?? 0);
        if ($storeMemberId <= 0) {
            return;
        }

        // ── 1. BELOW_MIN_WAGE ─────────────────────────────────────────
        $hourlyWage = (int) ($member['hourly_wage'] ?? 0);
        $minWage    = (int) ($settings['minimum_wage'] ?? 0);
        if ($minWage > 0 && $hourlyWage > 0 && $hourlyWage < $minWage) {
            $message = sprintf(
                '입력된 시급(%s원)이 법정 최저임금(%s원)에 미달합니다. '
                . '최저임금법에 따라 최저임금 미만의 임금 지급은 효력이 없으며, 차액 지급 의무가 발생할 수 있습니다.',
                number_format($hourlyWage),
                number_format($minWage)
            );
            $empMsg = sprintf(
                '등록된 시급(%s원)이 최저임금(%s원)에 미달합니다. 사장님께 확인해 주세요.',
                number_format($hourlyWage),
                number_format($minWage)
            );
            LaborRiskAlert::upsertByCode(
                $ownerId,
                $storeMemberId,
                'BELOW_MIN_WAGE',
                'danger',
                'employee_visible_required',
                '최저임금 미달',
                $message,
                $empMsg,
                '근로기준법 제6조 / 최저임금법 제6조',
                'store_member',
                $storeMemberId
            );
        }

        // ── 2. INSURANCE_NOT_ENROLLED ─────────────────────────────────
        $ins = EmployeeInsuranceSetting::findByMember($storeId, $storeMemberId);
        if (!empty($ins)
            && ($ins['user_selected_status'] ?? '') === 'not_enrolled'
            && (int) ($ins['warning_acknowledged'] ?? 0) === 0
        ) {
            LaborRiskAlert::upsertByCode(
                $ownerId,
                $storeMemberId,
                'INSURANCE_NOT_ENROLLED',
                'warning',
                'owner_only',
                '4대보험 미가입 확인 필요',
                '이 직원의 4대보험 관리 상태가 "미가입"으로 설정되어 있으나 리스크 확인이 기록되지 않았습니다. '
                    . '가입 의무 대상 여부를 확인하세요. 의무 대상을 미가입 처리하면 과태료 및 소급 처리 등의 불이익이 발생할 수 있습니다.',
                null,
                '국민연금법/건강보험법/고용보험법',
                'store_member',
                $storeMemberId
            );
        }

        // ── 3. TRIAL_EXCEEDED (수습 종료일이 오늘 기준 3개월 초과) ──────
        $trialEnd = $member['trial_end_date'] ?? null;
        if (!empty($trialEnd)) {
            $threshold = date('Y-m-d', strtotime('-3 months'));
            if ($trialEnd < $threshold) {
                LaborRiskAlert::upsertByCode(
                    $ownerId,
                    $storeMemberId,
                    'TRIAL_EXCEEDED',
                    'info',
                    'owner_only',
                    '수습기간 종료 가능성',
                    sprintf(
                        '수습 종료일(%s)이 3개월 이상 경과했습니다. 수습기간 종료 후에도 수습 시급이 적용되고 있지 않은지 확인하세요. '
                            . '(수습 감액은 계약기간 1년 이상 + 수습 3개월 이내일 때만 적용 가능합니다.)',
                        $trialEnd
                    ),
                    null,
                    '근로기준법 제35조',
                    'store_member',
                    $storeMemberId
                );
            }
        }
    }

    /**
     * 점주의 전체 직원·최근 90일 근무기록을 일괄 재스캔하여 리스크를 갱신한다.
     * work_logs.employee_id 는 store_members.id 를 참조하므로 store_members 와 조인한다.
     *
     * @return array ['member_count' => int, 'log_count' => int]
     */
    public static function scanAllForOwner(int $ownerId, int $storeId): array
    {
        $settings = Setting::get();
        $members  = StoreMember::allForStore($storeId);
        $scanned  = 0;

        // 직원별 최저임금·4대보험·수습기간 리스크 스캔
        foreach ($members as $member) {
            self::detectForMember($member, $ownerId, $storeId, $settings);
            $scanned++;
        }

        // 최근 90일 근무기록 스캔 (과도한 근무시간, 휴게 부족)
        $since = date('Y-m-d', strtotime('-90 days'));
        $logs  = DB::fetchAll(
            "SELECT wl.*, sm.id AS member_id, sm.name
               FROM work_logs wl
               JOIN store_members sm ON sm.id = wl.employee_id
              WHERE wl.owner_id = ? AND wl.store_id = ? AND wl.work_date >= ? AND wl.is_absent = 0",
            [$ownerId, $storeId, $since]
        );
        foreach ($logs as $log) {
            if (empty($log['start_time']) || empty($log['end_time'])) continue;
            $emp = ['id' => (int)$log['member_id'], 'store_member_id' => (int)$log['member_id'], 'name' => $log['name']];
            self::detectForWorkLog((int)$log['id'], $log, $emp, $ownerId, false, null);
        }

        return ['member_count' => $scanned, 'log_count' => count($logs)];
    }

    /** 분 → 소수 시간 문자열 (예: 510 → "8.5"). */
    private static function fmtHours(int $minutes): string
    {
        $h = $minutes / 60;
        return rtrim(rtrim(number_format($h, 2, '.', ''), '0'), '.');
    }

    /** "HH:MM:SS" / "HH:MM" → "HH:MM" 정규화 (비교/표시용). */
    private static function hm(string $time): string
    {
        if ($time === '') {
            return '';
        }
        $parts = explode(':', $time);
        if (count($parts) >= 2) {
            return sprintf('%02d:%02d', (int) $parts[0], (int) $parts[1]);
        }
        return $time;
    }
}
