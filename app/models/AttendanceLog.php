<?php
class AttendanceLog
{
    /** 출근 기록 생성 (서버 시간 기준) */
    public static function clockIn(int $storeId, int $storeMemberId, string $source = 'mobile_web'): int
    {
        DB::query(
            'INSERT INTO attendance_logs (store_id, store_member_id, clock_in_at, status, source)
             VALUES (?, ?, NOW(), \'working\', ?)',
            [$storeId, $storeMemberId, $source]
        );
        return DB::lastInsertId();
    }

    /** 퇴근 처리 (서버 시간 기준) */
    public static function clockOut(int $id, int $storeId, int $storeMemberId): bool
    {
        $log = self::find($id, $storeId);
        if (!$log || (int)$log['store_member_id'] !== $storeMemberId) {
            return false;
        }
        DB::query(
            "UPDATE attendance_logs
             SET clock_out_at=NOW(), status='completed', updated_at=NOW()
             WHERE id=? AND store_id=? AND status='working'",
            [$id, $storeId]
        );
        return true;
    }

    /** 현재 출근 중인 레코드 (알바생용) */
    public static function currentlyWorking(int $storeMemberId): ?array
    {
        return DB::fetchOne(
            "SELECT * FROM attendance_logs
             WHERE store_member_id=? AND status='working'
             ORDER BY clock_in_at DESC LIMIT 1",
            [$storeMemberId]
        );
    }

    /** 오늘 특정 사업장의 전체 출퇴근 현황 (점주 대시보드) */
    public static function todayForStore(int $storeId, string $date): array
    {
        return DB::fetchAll(
            "SELECT al.*,
                    sm.name AS member_name, sm.hourly_wage,
                    TIMESTAMPDIFF(MINUTE, al.clock_in_at, IFNULL(al.clock_out_at, NOW())) AS duration_minutes
             FROM attendance_logs al
             JOIN store_members sm ON sm.id = al.store_member_id
             WHERE al.store_id=? AND DATE(al.clock_in_at)=?
             ORDER BY al.clock_in_at DESC",
            [$storeId, $date]
        );
    }

    /** 특정 멤버의 최근 기록 */
    public static function recentForMember(int $storeMemberId, int $limit = 10): array
    {
        return DB::fetchAll(
            "SELECT *,
                    TIMESTAMPDIFF(MINUTE, clock_in_at, clock_out_at) AS duration_minutes
             FROM attendance_logs
             WHERE store_member_id=?
             ORDER BY clock_in_at DESC
             LIMIT ?",
            [$storeMemberId, $limit]
        );
    }

    /** 이번 주 합계 (알바생 대시보드용) */
    public static function weekSummary(int $storeMemberId, string $weekStart): array
    {
        $weekEnd = date('Y-m-d', strtotime($weekStart . ' +6 days'));
        $row = DB::fetchOne(
            "SELECT
               COUNT(*) AS work_days,
               SUM(TIMESTAMPDIFF(MINUTE, clock_in_at, clock_out_at)) AS total_minutes
             FROM attendance_logs
             WHERE store_member_id=?
               AND status='completed'
               AND DATE(clock_in_at) BETWEEN ? AND ?",
            [$storeMemberId, $weekStart, $weekEnd]
        );
        return [
            'work_days'     => (int)($row['work_days'] ?? 0),
            'total_minutes' => (int)($row['total_minutes'] ?? 0),
        ];
    }

    /** 이번 달 합계 */
    public static function monthSummary(int $storeMemberId, string $year, string $month): array
    {
        $row = DB::fetchOne(
            "SELECT
               COUNT(*) AS work_days,
               SUM(TIMESTAMPDIFF(MINUTE, clock_in_at, clock_out_at)) AS total_minutes
             FROM attendance_logs
             WHERE store_member_id=?
               AND status IN ('completed','approved')
               AND YEAR(clock_in_at)=? AND MONTH(clock_in_at)=?",
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
            'SELECT * FROM attendance_logs WHERE id=? AND store_id=?',
            [$id, $storeId]
        );
    }

    /** 점주의 수동 수정 */
    public static function ownerUpdate(int $id, int $storeId, string $clockIn, ?string $clockOut): void
    {
        DB::query(
            "UPDATE attendance_logs
             SET clock_in_at=?, clock_out_at=?,
                 status=IF(?='', 'working', 'approved'),
                 source='owner_manual', updated_at=NOW()
             WHERE id=? AND store_id=?",
            [$clockIn, $clockOut ?: null, $clockOut ?: '', $id, $storeId]
        );
    }

    /** 점주의 수동 기록 추가 */
    public static function ownerCreate(int $storeId, int $storeMemberId, string $clockIn, ?string $clockOut): int
    {
        $status = $clockOut ? 'approved' : 'working';
        DB::query(
            'INSERT INTO attendance_logs
             (store_id, store_member_id, clock_in_at, clock_out_at, status, source)
             VALUES (?, ?, ?, ?, ?, \'owner_manual\')',
            [$storeId, $storeMemberId, $clockIn, $clockOut ?: null, $status]
        );
        return DB::lastInsertId();
    }
}
