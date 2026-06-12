<?php
class AttendanceLog
{
    /**
     * 출근 기록 생성 — 서버 시간을 original 필드에 저장.
     * 이미 퇴근하지 않은(working) 기록이 있으면 중복 출근을 거부하고 0을 반환한다.
     */
    public static function clockIn(int $storeId, int $storeMemberId, string $source = 'mobile_web', ?int $employeeUserId = null): int
    {
        $open = DB::fetchOne(
            "SELECT id FROM attendance_logs
             WHERE store_member_id = ? AND original_clock_out_at IS NULL AND status = 'working'
             LIMIT 1",
            [$storeMemberId]
        );
        if ($open) {
            return 0; // 이미 출근 중 — 중복 거부
        }

        DB::query(
            "INSERT INTO attendance_logs
             (store_id, store_member_id, employee_user_id, original_clock_in_at, status, source)
             VALUES (?, ?, ?, NOW(), 'working', ?)",
            [$storeId, $storeMemberId, $employeeUserId, $source]
        );
        return DB::lastInsertId();
    }

    /** 퇴근 처리 — 서버 시간을 original_clock_out_at 에 저장 */
    public static function clockOut(int $id, int $storeId, int $storeMemberId): bool
    {
        $log = self::find($id, $storeId);
        if (!$log || (int)$log['store_member_id'] !== $storeMemberId) {
            return false;
        }
        $stmt = DB::query(
            "UPDATE attendance_logs
             SET original_clock_out_at = NOW(), status = 'completed', updated_at = NOW()
             WHERE id = ? AND store_id = ? AND status = 'working'",
            [$id, $storeId]
        );
        // 영향 행이 0이면 이미 퇴근 처리된 기록 — 중복 퇴근 거부
        return $stmt->rowCount() > 0;
    }

    /** 현재 출근 중인 레코드 (알바생용) */
    public static function currentlyWorking(int $storeMemberId): ?array
    {
        return DB::fetchOne(
            "SELECT *,
                    COALESCE(adjusted_clock_in_at, original_clock_in_at) AS effective_clock_in_at
             FROM attendance_logs
             WHERE store_member_id = ? AND status = 'working'
             ORDER BY original_clock_in_at DESC LIMIT 1",
            [$storeMemberId]
        );
    }

    /** 오늘 특정 사업장 전체 출퇴근 현황 (점주 대시보드) */
    public static function todayForStore(int $storeId, string $date): array
    {
        return DB::fetchAll(
            "SELECT al.*,
                    COALESCE(al.adjusted_clock_in_at,  al.original_clock_in_at)  AS effective_clock_in_at,
                    COALESCE(al.adjusted_clock_out_at, al.original_clock_out_at) AS effective_clock_out_at,
                    sm.name AS member_name, sm.hourly_wage,
                    TIMESTAMPDIFF(MINUTE,
                        COALESCE(al.adjusted_clock_in_at,  al.original_clock_in_at),
                        IFNULL(COALESCE(al.adjusted_clock_out_at, al.original_clock_out_at), NOW())
                    ) AS duration_minutes,
                    (SELECT COUNT(*) FROM attendance_adjustment_logs aadj
                     WHERE aadj.attendance_log_id = al.id) AS adjustment_count
             FROM attendance_logs al
             JOIN store_members sm ON sm.id = al.store_member_id
             WHERE al.store_id = ? AND DATE(al.original_clock_in_at) = ?
             ORDER BY al.original_clock_in_at DESC",
            [$storeId, $date]
        );
    }

    /** 특정 멤버의 최근 기록 */
    public static function recentForMember(int $storeMemberId, int $limit = 10): array
    {
        return DB::fetchAll(
            "SELECT *,
                    COALESCE(adjusted_clock_in_at,  original_clock_in_at)  AS effective_clock_in_at,
                    COALESCE(adjusted_clock_out_at, original_clock_out_at) AS effective_clock_out_at,
                    TIMESTAMPDIFF(MINUTE,
                        COALESCE(adjusted_clock_in_at, original_clock_in_at),
                        COALESCE(adjusted_clock_out_at, original_clock_out_at)
                    ) AS duration_minutes,
                    (adjusted_clock_in_at IS NOT NULL OR adjusted_clock_out_at IS NOT NULL) AS is_adjusted
             FROM attendance_logs
             WHERE store_member_id = ?
             ORDER BY original_clock_in_at DESC
             LIMIT ?",
            [$storeMemberId, $limit]
        );
    }

    /** 이번 주 합계 — 유효 시각(adjusted 우선) 기준 */
    public static function weekSummary(int $storeMemberId, string $weekStart): array
    {
        $weekEnd = date('Y-m-d', strtotime($weekStart . ' +6 days'));
        $row = DB::fetchOne(
            "SELECT
               COUNT(*) AS work_days,
               SUM(TIMESTAMPDIFF(MINUTE,
                   COALESCE(adjusted_clock_in_at,  original_clock_in_at),
                   COALESCE(adjusted_clock_out_at, original_clock_out_at)
               )) AS total_minutes
             FROM attendance_logs
             WHERE store_member_id = ?
               AND status IN ('completed', 'corrected')
               AND DATE(original_clock_in_at) BETWEEN ? AND ?",
            [$storeMemberId, $weekStart, $weekEnd]
        );
        return [
            'work_days'     => (int)($row['work_days'] ?? 0),
            'total_minutes' => (int)($row['total_minutes'] ?? 0),
        ];
    }

    /** 이번 달 합계 — 유효 시각(adjusted 우선) 기준 */
    public static function monthSummary(int $storeMemberId, string $year, string $month): array
    {
        $row = DB::fetchOne(
            "SELECT
               COUNT(*) AS work_days,
               SUM(TIMESTAMPDIFF(MINUTE,
                   COALESCE(adjusted_clock_in_at,  original_clock_in_at),
                   COALESCE(adjusted_clock_out_at, original_clock_out_at)
               )) AS total_minutes
             FROM attendance_logs
             WHERE store_member_id = ?
               AND status IN ('completed', 'corrected')
               AND YEAR(original_clock_in_at)  = ?
               AND MONTH(original_clock_in_at) = ?",
            [$storeMemberId, (int)$year, (int)$month]
        );
        return [
            'work_days'     => (int)($row['work_days'] ?? 0),
            'total_minutes' => (int)($row['total_minutes'] ?? 0),
        ];
    }

    public static function find(int $id, int $storeId): ?array
    {
        return DB::fetchOne(
            'SELECT * FROM attendance_logs WHERE id = ? AND store_id = ?',
            [$id, $storeId]
        );
    }

    /**
     * GPS 스냅샷 저장. clockIn/clockOut 직후 호출.
     * $gps는 gps_validate() 반환값 + 'source' 키(QR|APP_BUTTON|MANUAL).
     */
    public static function saveGpsSnapshot(int $logId, array $gps): void
    {
        if (!$logId) return;
        DB::query(
            "UPDATE attendance_logs
             SET employee_latitude       = ?,
                 employee_longitude      = ?,
                 gps_accuracy            = ?,
                 biz_lat_snapshot        = ?,
                 biz_lng_snapshot        = ?,
                 allowed_radius_snapshot = ?,
                 distance_meters         = ?,
                 geo_status              = ?,
                 geo_error_code          = ?,
                 attendance_source       = ?
             WHERE id = ?",
            [
                $gps['employee_lat'],
                $gps['employee_lng'],
                $gps['accuracy'],
                $gps['biz_lat'],
                $gps['biz_lng'],
                $gps['allowed_radius'],
                $gps['distance_meters'],
                $gps['geo_status'],
                $gps['geo_error_code'],
                $gps['source'],
                $logId,
            ]
        );
    }

    /** 점주 수동 기록 추가 — original 필드에 저장 (신규 생성이므로 이력 불필요) */
    public static function ownerCreate(int $storeId, int $storeMemberId, string $clockIn, ?string $clockOut): int
    {
        $status = $clockOut ? 'completed' : 'working';
        DB::query(
            "INSERT INTO attendance_logs
             (store_id, store_member_id,
              original_clock_in_at, original_clock_out_at,
              status, source, created_by_user_id)
             VALUES (?, ?, ?, ?, ?, 'owner_manual', ?)",
            [$storeId, $storeMemberId, $clockIn, $clockOut ?: null, $status, Auth::ownerId()]
        );
        return DB::lastInsertId();
    }

    /**
     * 점주 정정 — adjusted 필드에만 저장. original(원본)은 절대 변경하지 않는다.
     * 수정 이력(AttendanceAdjustmentLog)은 반드시 호출 측에서 먼저 기록해야 한다.
     */
    public static function ownerAdjust(int $id, int $storeId, ?string $adjClockIn, ?string $adjClockOut): void
    {
        DB::query(
            "UPDATE attendance_logs
             SET adjusted_clock_in_at  = ?,
                 adjusted_clock_out_at = ?,
                 status                = 'corrected',
                 updated_at            = NOW()
             WHERE id = ? AND store_id = ?
               AND status NOT IN ('payroll_confirmed', 'payroll_paid')",
            [$adjClockIn, $adjClockOut, $id, $storeId]
        );
    }

    /**
     * 수정 요청을 직원 검토 대기 상태로 표시.
     * adjusted_* 는 직원 수락/강제확정 시점에 반영되므로 여기서는 건드리지 않는다.
     */
    public static function markPending(int $logId, int $storeId, int $requestId): void
    {
        DB::query(
            "UPDATE attendance_logs
             SET record_status = 'pending_employee_review',
                 active_change_request_id = ?,
                 updated_at = NOW()
             WHERE id = ? AND store_id = ?",
            [$requestId, $logId, $storeId]
        );
    }

    /**
     * 수정 잠금 여부.
     * - 급여 확정/지급 완료 상태
     * - 이미 직원 검토 대기 중인 수정 요청이 있는 경우 (중복 수정 방지)
     */
    public static function isLocked(array $log): bool
    {
        if (in_array($log['status'], ['payroll_confirmed', 'payroll_paid'], true)) {
            return true;
        }
        return ($log['record_status'] ?? 'original') === 'pending_employee_review';
    }

    /**
     * 최근 N주의 주별 실제 근무시간.
     * 계약상 소정근로시간 vs 실제 비교용.
     */
    public static function recentWeeklyActualHours(int $memberId, int $weeksBack = 4): array
    {
        $today = date('Y-m-d');
        [$currentWeekStart] = getWeekRange($today);

        $cursor = new DateTime($currentWeekStart);
        $cursor->modify('-' . ($weeksBack - 1) . ' weeks');

        $weeks = [];
        for ($i = 0; $i < $weeksBack; $i++) {
            $wStart = $cursor->format('Y-m-d');
            $wEnd   = (clone $cursor)->modify('+6 days')->format('Y-m-d');

            $row = DB::fetchOne(
                "SELECT COALESCE(SUM(TIMESTAMPDIFF(MINUTE,
                         COALESCE(adjusted_clock_in_at, original_clock_in_at),
                         COALESCE(adjusted_clock_out_at, original_clock_out_at)
                     )), 0) AS total_minutes
                 FROM attendance_logs
                 WHERE store_member_id = ?
                   AND status IN ('completed','corrected','payroll_confirmed','payroll_paid')
                   AND DATE(COALESCE(adjusted_clock_in_at, original_clock_in_at)) BETWEEN ? AND ?
                   AND COALESCE(adjusted_clock_out_at, original_clock_out_at) IS NOT NULL",
                [$memberId, $wStart, $wEnd]
            );

            $minutes = (int)($row['total_minutes'] ?? 0);
            $weeks[] = [
                'week_start'     => $wStart,
                'week_end'       => $wEnd,
                'actual_minutes' => $minutes,
                'actual_hours'   => round($minutes / 60, 1),
            ];
            $cursor->modify('+7 days');
        }
        return $weeks;
    }

    /**
     * 매장 전체 직원의 최근 4주 평균 + 당월 실제 근무시간 일괄 조회.
     * index 페이지 경고 뱃지용 (쿼리 2회로 전원 처리).
     */
    public static function batchActualHoursSummary(int $storeId): array
    {
        $since = date('Y-m-d', strtotime('-28 days'));
        $year  = (int)date('Y');
        $month = (int)date('n');

        $result = [];

        foreach (DB::fetchAll(
            "SELECT store_member_id,
                    SUM(TIMESTAMPDIFF(MINUTE,
                        COALESCE(adjusted_clock_in_at, original_clock_in_at),
                        COALESCE(adjusted_clock_out_at, original_clock_out_at)
                    )) AS total_minutes
             FROM attendance_logs
             WHERE store_id = ?
               AND status IN ('completed','corrected','payroll_confirmed','payroll_paid')
               AND DATE(COALESCE(adjusted_clock_in_at, original_clock_in_at)) >= ?
               AND COALESCE(adjusted_clock_out_at, original_clock_out_at) IS NOT NULL
             GROUP BY store_member_id",
            [$storeId, $since]
        ) as $row) {
            $id  = (int)$row['store_member_id'];
            $min = (int)($row['total_minutes'] ?? 0);
            $result[$id]['avg_weekly_hours'] = round($min / 60 / 4, 1);
            $result[$id]['has_data']         = $min > 0;
        }

        foreach (DB::fetchAll(
            "SELECT store_member_id,
                    SUM(TIMESTAMPDIFF(MINUTE,
                        COALESCE(adjusted_clock_in_at, original_clock_in_at),
                        COALESCE(adjusted_clock_out_at, original_clock_out_at)
                    )) AS total_minutes
             FROM attendance_logs
             WHERE store_id = ?
               AND status IN ('completed','corrected','payroll_confirmed','payroll_paid')
               AND YEAR(COALESCE(adjusted_clock_in_at, original_clock_in_at))  = ?
               AND MONTH(COALESCE(adjusted_clock_in_at, original_clock_in_at)) = ?
               AND COALESCE(adjusted_clock_out_at, original_clock_out_at) IS NOT NULL
             GROUP BY store_member_id",
            [$storeId, $year, $month]
        ) as $row) {
            $id  = (int)$row['store_member_id'];
            $result[$id]['current_month_hours'] = round((int)($row['total_minutes'] ?? 0) / 60, 1);
        }

        return $result;
    }

    /** 해당 월 매장 전체 예상 인건비 합계 (사장 뷰용) */
    public static function monthLaborCostForStore(int $storeId, int $year, int $month): int
    {
        $rows = DB::fetchAll(
            "SELECT sm.hourly_wage,
                    COALESCE(SUM(TIMESTAMPDIFF(MINUTE,
                        COALESCE(al.adjusted_clock_in_at,  al.original_clock_in_at),
                        COALESCE(al.adjusted_clock_out_at, al.original_clock_out_at)
                    )), 0) AS total_minutes
             FROM attendance_logs al
             JOIN store_members sm ON sm.id = al.store_member_id
             WHERE al.store_id = ?
               AND al.status IN ('completed','corrected','payroll_confirmed','payroll_paid')
               AND YEAR(COALESCE(al.adjusted_clock_in_at, al.original_clock_in_at))  = ?
               AND MONTH(COALESCE(al.adjusted_clock_in_at, al.original_clock_in_at)) = ?
             GROUP BY al.store_member_id, sm.hourly_wage",
            [$storeId, $year, $month]
        );
        $total = 0;
        foreach ($rows as $row) {
            $total += (int) round((float)$row['total_minutes'] / 60 * (int)$row['hourly_wage']);
        }
        return $total;
    }
}
