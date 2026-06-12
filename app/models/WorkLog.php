<?php
class WorkLog
{
    // ─── 조회 ─────────────────────────────────────────────────

    public static function forEmployee(int $employeeId, int $limit = 30): array
    {
        return DB::fetchAll(
            'SELECT w.*, sm.name AS employee_name
             FROM work_logs w
             JOIN store_members sm ON sm.id = w.employee_id
             WHERE w.employee_id = ? AND w.owner_id = ? AND w.store_id = ?
             ORDER BY w.work_date DESC, w.start_time DESC
             LIMIT ?',
            [$employeeId, Auth::ownerId(), Auth::storeId(), $limit]
        );
    }

    public static function forPeriod(int $employeeId, string $start, string $end): array
    {
        return DB::fetchAll(
            'SELECT * FROM work_logs
             WHERE employee_id = ? AND owner_id = ? AND store_id = ?
               AND work_date BETWEEN ? AND ?
             ORDER BY work_date ASC, start_time ASC',
            [$employeeId, Auth::ownerId(), Auth::storeId(), $start, $end]
        );
    }

    /**
     * 매장 전체 직원의 기간 내 근무 기록을 한 번에 조회 (N+1 방지).
     * 반환: [employee_id => [log, log, ...]] 형태로 그룹핑.
     * owner_id를 명시적으로 받아 직원(알바) 컨텍스트에서도 사용 가능하게 한다.
     */
    public static function forStorePeriodGrouped(int $storeId, int $ownerId, string $start, string $end): array
    {
        $rows = DB::fetchAll(
            'SELECT * FROM work_logs
             WHERE owner_id = ? AND store_id = ?
               AND work_date BETWEEN ? AND ?
             ORDER BY work_date ASC, start_time ASC',
            [$ownerId, $storeId, $start, $end]
        );

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int)$row['employee_id']][] = $row;
        }
        return $grouped;
    }

    /**
     * CSV 내보내기용 — owner_id 기준 기간 내 근무기록 조회.
     * employeeId 가 주어지면 해당 직원만 필터링한다.
     */
    public static function exportForOwner(int $ownerId, ?int $employeeId, string $dateFrom, string $dateTo): array
    {
        $params = [$ownerId, $dateFrom, $dateTo];
        $empFilter = '';
        if ($employeeId) {
            $empFilter = 'AND wl.employee_id = ?';
            $params[]  = $employeeId;
        }
        return DB::fetchAll("
            SELECT wl.*, sm.name AS employee_name
            FROM work_logs wl
            JOIN store_members sm ON sm.id = wl.employee_id AND sm.store_id = wl.store_id
            WHERE wl.owner_id = ? AND wl.work_date BETWEEN ? AND ?
            $empFilter
            ORDER BY wl.work_date DESC, sm.name
        ", $params);
    }

    public static function forEmployeePeriod(int $employeeId, string $dateFrom, string $dateTo, int $limit = 200): array
    {
        return DB::fetchAll(
            "SELECT w.*, sm.name AS employee_name
               FROM work_logs w
               JOIN store_members sm ON sm.id = w.employee_id
              WHERE w.employee_id = ? AND w.owner_id = ? AND w.store_id = ?
                AND w.work_date BETWEEN ? AND ?
              ORDER BY w.work_date DESC, w.start_time DESC
              LIMIT " . (int)$limit,
            [$employeeId, Auth::ownerId(), Auth::storeId(), $dateFrom, $dateTo]
        );
    }

    public static function forPeriodAll(int $ownerId, string $dateFrom, string $dateTo, int $limit = 300): array
    {
        return DB::fetchAll(
            "SELECT w.*, sm.name AS employee_name
               FROM work_logs w
               JOIN store_members sm ON sm.id = w.employee_id
              WHERE w.owner_id = ? AND w.store_id = ?
                AND w.work_date BETWEEN ? AND ?
              ORDER BY w.work_date DESC, sm.name ASC
              LIMIT " . (int)$limit,
            [$ownerId, Auth::storeId(), $dateFrom, $dateTo]
        );
    }

    public static function recentAll(int $limit = 50): array
    {
        return DB::fetchAll(
            'SELECT w.*, sm.name AS employee_name
             FROM work_logs w
             JOIN store_members sm ON sm.id = w.employee_id
             WHERE w.owner_id = ? AND w.store_id = ?
             ORDER BY w.work_date DESC, w.start_time DESC
             LIMIT ?',
            [Auth::ownerId(), Auth::storeId(), $limit]
        );
    }

    /**
     * 단건 조회 — owner_id 불일치 시 null 반환.
     * URL의 id를 변조해도 타 점주 데이터에 접근 불가.
     */
    public static function find(int $id): ?array
    {
        return DB::fetchOne(
            'SELECT w.*, sm.name AS employee_name
             FROM work_logs w
             JOIN store_members sm ON sm.id = w.employee_id
             WHERE w.id = ? AND w.owner_id = ?',
            [$id, Auth::ownerId()]
        );
    }

    // ─── 생성 (owner_id 세션 자동 주입) ──────────────────────

    public static function create(array $data): int
    {
        DB::query('
            INSERT INTO work_logs
              (owner_id, store_id, employee_id, work_date, start_time, end_time,
               break_minutes, break_auto, is_holiday, is_absent, is_late, is_early_leave,
               is_employer_early_leave, memo)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ', [
            Auth::ownerId(),
            Auth::storeId(),
            (int) $data['employee_id'],
            $data['work_date'],
            $data['start_time'],
            $data['end_time'],
            (int) ($data['break_minutes'] ?? 0),
            isset($data['break_auto'])              ? 1 : 0,
            isset($data['is_holiday'])              ? 1 : 0,
            isset($data['is_absent'])               ? 1 : 0,
            isset($data['is_late'])                 ? 1 : 0,
            isset($data['is_early_leave'])          ? 1 : 0,
            isset($data['is_employer_early_leave']) ? 1 : 0,
            $data['memo'] ?? null,
        ]);
        return DB::lastInsertId();
    }

    // ─── 수정 (WHERE에 owner_id 포함) ─────────────────────────

    public static function update(int $id, array $data): void
    {
        DB::query('
            UPDATE work_logs SET
                employee_id             = ?,
                work_date               = ?,
                start_time              = ?,
                end_time                = ?,
                break_minutes           = ?,
                break_auto              = ?,
                is_holiday              = ?,
                is_absent               = ?,
                is_late                 = ?,
                is_early_leave          = ?,
                is_employer_early_leave = ?,
                memo                    = ?
            WHERE id = ? AND owner_id = ?
        ', [
            (int) $data['employee_id'],
            $data['work_date'],
            $data['start_time'],
            $data['end_time'],
            (int) ($data['break_minutes'] ?? 0),
            isset($data['break_auto'])              ? 1 : 0,
            isset($data['is_holiday'])              ? 1 : 0,
            isset($data['is_absent'])               ? 1 : 0,
            isset($data['is_late'])                 ? 1 : 0,
            isset($data['is_early_leave'])          ? 1 : 0,
            isset($data['is_employer_early_leave']) ? 1 : 0,
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
