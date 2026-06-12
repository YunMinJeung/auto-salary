<?php
/**
 * attendance_logs → work_logs 동기화 서비스.
 *
 * QR/PWA 출퇴근 및 점주 정정 확정 결과를 급여 계산의 입력 소스인 work_logs에 반영한다.
 * attendance_log 1건을 받아 대응하는 work_log를 INSERT(없으면) 또는 UPDATE(있으면)한다.
 *
 * - 유효 시각은 adjusted_* 를 우선하고 없으면 original_* 사용.
 * - 퇴근(out)이 없는 진행 중 기록은 동기화하지 않는다(퇴근 완료 후 호출할 것).
 * - work_log 식별 키: (owner_id, store_id, employee_id, work_date).
 */
class AttendanceSyncService
{
    /**
     * attendance_log 1건을 work_logs에 반영.
     * @param int $attendanceLogId attendance_logs.id
     */
    public static function sync(int $attendanceLogId): void
    {
        $row = DB::fetchOne(
            'SELECT al.id,
                    al.store_id,
                    al.store_member_id,
                    COALESCE(al.adjusted_clock_in_at,  al.original_clock_in_at)  AS clock_in_at,
                    COALESCE(al.adjusted_clock_out_at, al.original_clock_out_at) AS clock_out_at,
                    al.break_minutes,
                    al.source,
                    s.owner_id AS owner_id
             FROM attendance_logs al
             JOIN stores s ON s.id = al.store_id
             WHERE al.id = ?',
            [$attendanceLogId]
        );

        if (!$row) {
            return;
        }

        // 퇴근 미완: 동기화하지 않는다.
        if (empty($row['clock_in_at']) || empty($row['clock_out_at'])) {
            return;
        }

        $ownerId   = (int)$row['owner_id'];
        $storeId   = (int)$row['store_id'];
        $empId     = (int)$row['store_member_id'];
        $workDate  = date('Y-m-d', strtotime($row['clock_in_at']));
        $startTime = date('H:i:s', strtotime($row['clock_in_at']));
        $endTime   = date('H:i:s', strtotime($row['clock_out_at']));
        $breakMin  = (int)($row['break_minutes'] ?? 0);
        $source    = self::mapSource($row['source'] ?? '');

        $existing = DB::fetchOne(
            'SELECT id FROM work_logs
             WHERE owner_id = ? AND store_id = ? AND employee_id = ? AND work_date = ?',
            [$ownerId, $storeId, $empId, $workDate]
        );

        if ($existing) {
            DB::query(
                'UPDATE work_logs
                 SET start_time = ?, end_time = ?, break_minutes = ?
                 WHERE id = ? AND owner_id = ? AND store_id = ?',
                [$startTime, $endTime, $breakMin, (int)$existing['id'], $ownerId, $storeId]
            );
        } else {
            DB::query(
                'INSERT INTO work_logs
                   (owner_id, store_id, employee_id, work_date, start_time, end_time, break_minutes, break_auto)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 1)',
                [$ownerId, $storeId, $empId, $workDate, $startTime, $endTime, $breakMin]
            );
        }
    }

    /** attendance_logs.source → work_logs 동기화 출처 라벨. (현재 work_logs에 source 컬럼이 없어 분류 용도로만 사용) */
    private static function mapSource(string $attendanceSource): string
    {
        return match ($attendanceSource) {
            'qr'                       => 'qr',
            'owner_manual', 'admin_adjusted' => 'correction',
            default                    => 'manual',
        };
    }
}
