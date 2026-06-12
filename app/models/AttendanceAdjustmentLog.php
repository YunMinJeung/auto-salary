<?php
class AttendanceAdjustmentLog
{
    public static function create(array $data): int
    {
        DB::query(
            'INSERT INTO attendance_adjustment_logs
             (attendance_log_id, store_id, store_member_id,
              changed_by_user_id, changed_by_role,
              before_clock_in_at, before_clock_out_at,
              after_clock_in_at,  after_clock_out_at,
              before_break_minutes, after_break_minutes,
              reason, change_type, employee_visible)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [
                $data['attendance_log_id'],
                $data['store_id'],
                $data['store_member_id'],
                $data['changed_by_user_id'],
                $data['changed_by_role'],
                $data['before_clock_in_at']   ?? null,
                $data['before_clock_out_at']  ?? null,
                $data['after_clock_in_at']    ?? null,
                $data['after_clock_out_at']   ?? null,
                $data['before_break_minutes'] ?? 0,
                $data['after_break_minutes']  ?? 0,
                $data['reason'],
                $data['change_type'],
                $data['employee_visible'] ?? 1,
            ]
        );
        return DB::lastInsertId();
    }

    /** 특정 출퇴근 기록의 수정 이력 전체 (시간순) */
    public static function forAttendanceLog(int $attendanceLogId, int $storeId): array
    {
        return DB::fetchAll(
            'SELECT aadj.*, u.name AS changed_by_name
             FROM attendance_adjustment_logs aadj
             LEFT JOIN users u ON u.id = aadj.changed_by_user_id
             WHERE aadj.attendance_log_id = ? AND aadj.store_id = ?
             ORDER BY aadj.created_at ASC',
            [$attendanceLogId, $storeId]
        );
    }

    /** 직원에게 보여줄 수정 이력 (employee_visible = 1) */
    public static function forMember(int $storeMemberId): array
    {
        return DB::fetchAll(
            'SELECT aadj.*,
                    al.original_clock_in_at, al.original_clock_out_at,
                    u.name AS changed_by_name
             FROM attendance_adjustment_logs aadj
             JOIN attendance_logs al ON al.id = aadj.attendance_log_id
             LEFT JOIN users u ON u.id = aadj.changed_by_user_id
             WHERE aadj.store_member_id = ? AND aadj.employee_visible = 1
             ORDER BY aadj.created_at DESC
             LIMIT 30',
            [$storeMemberId]
        );
    }
}
