<?php
class WorkLog
{
    // ─── 조회 ─────────────────────────────────────────────────

    public static function forEmployee(int $employeeId, int $limit = 30): array
    {
        return DB::fetchAll(
            'SELECT w.*, e.name AS employee_name
             FROM work_logs w
             JOIN employees e ON e.id = w.employee_id
             WHERE w.employee_id = ? AND w.owner_id = ?
             ORDER BY w.work_date DESC, w.start_time DESC
             LIMIT ?',
            [$employeeId, Auth::ownerId(), $limit]
        );
    }

    public static function forPeriod(int $employeeId, string $start, string $end): array
    {
        return DB::fetchAll(
            'SELECT * FROM work_logs
             WHERE employee_id = ? AND owner_id = ?
               AND work_date BETWEEN ? AND ?
             ORDER BY work_date ASC, start_time ASC',
            [$employeeId, Auth::ownerId(), $start, $end]
        );
    }

    public static function recentAll(int $limit = 50): array
    {
        return DB::fetchAll(
            'SELECT w.*, e.name AS employee_name
             FROM work_logs w
             JOIN employees e ON e.id = w.employee_id
             WHERE w.owner_id = ?
             ORDER BY w.work_date DESC, w.start_time DESC
             LIMIT ?',
            [Auth::ownerId(), $limit]
        );
    }

    /**
     * 단건 조회 — owner_id 불일치 시 null 반환.
     * URL의 id를 변조해도 타 점주 데이터에 접근 불가.
     */
    public static function find(int $id): ?array
    {
        return DB::fetchOne(
            'SELECT w.*, e.name AS employee_name
             FROM work_logs w
             JOIN employees e ON e.id = w.employee_id
             WHERE w.id = ? AND w.owner_id = ?',
            [$id, Auth::ownerId()]
        );
    }

    // ─── 생성 (owner_id 세션 자동 주입) ──────────────────────

    public static function create(array $data): int
    {
        DB::query('
            INSERT INTO work_logs
              (owner_id, employee_id, work_date, start_time, end_time,
               break_minutes, break_auto, is_holiday, is_absent, is_late, is_early_leave, memo)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ', [
            Auth::ownerId(),
            (int) $data['employee_id'],
            $data['work_date'],
            $data['start_time'],
            $data['end_time'],
            (int) ($data['break_minutes'] ?? 0),
            isset($data['break_auto'])     ? 1 : 0,
            isset($data['is_holiday'])     ? 1 : 0,
            isset($data['is_absent'])      ? 1 : 0,
            isset($data['is_late'])        ? 1 : 0,
            isset($data['is_early_leave']) ? 1 : 0,
            $data['memo'] ?? null,
        ]);
        return DB::lastInsertId();
    }

    // ─── 수정 (WHERE에 owner_id 포함) ─────────────────────────

    public static function update(int $id, array $data): void
    {
        DB::query('
            UPDATE work_logs SET
                employee_id    = ?,
                work_date      = ?,
                start_time     = ?,
                end_time       = ?,
                break_minutes  = ?,
                break_auto     = ?,
                is_holiday     = ?,
                is_absent      = ?,
                is_late        = ?,
                is_early_leave = ?,
                memo           = ?
            WHERE id = ? AND owner_id = ?
        ', [
            (int) $data['employee_id'],
            $data['work_date'],
            $data['start_time'],
            $data['end_time'],
            (int) ($data['break_minutes'] ?? 0),
            isset($data['break_auto'])     ? 1 : 0,
            isset($data['is_holiday'])     ? 1 : 0,
            isset($data['is_absent'])      ? 1 : 0,
            isset($data['is_late'])        ? 1 : 0,
            isset($data['is_early_leave']) ? 1 : 0,
            $data['memo'] ?? null,
            $id,
            Auth::ownerId(),
        ]);
    }

    // ─── 삭제 (WHERE에 owner_id 포함) ─────────────────────────

    public static function delete(int $id): void
    {
        DB::query(
            'DELETE FROM work_logs WHERE id = ? AND owner_id = ?',
            [$id, Auth::ownerId()]
        );
    }
}
