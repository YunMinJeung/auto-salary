<?php
class WorkLog
{
    public static function forEmployee(int $employeeId, int $limit = 30): array
    {
        return DB::fetchAll(
            'SELECT w.*, e.name AS employee_name
             FROM work_logs w
             JOIN employees e ON e.id = w.employee_id
             WHERE w.employee_id = ?
             ORDER BY w.work_date DESC, w.start_time DESC
             LIMIT ?',
            [$employeeId, $limit]
        );
    }

    public static function forPeriod(int $employeeId, string $start, string $end): array
    {
        return DB::fetchAll(
            'SELECT * FROM work_logs
             WHERE employee_id = ? AND work_date BETWEEN ? AND ?
             ORDER BY work_date ASC, start_time ASC',
            [$employeeId, $start, $end]
        );
    }

    public static function recentAll(int $limit = 50): array
    {
        return DB::fetchAll(
            'SELECT w.*, e.name AS employee_name
             FROM work_logs w
             JOIN employees e ON e.id = w.employee_id
             ORDER BY w.work_date DESC, w.start_time DESC
             LIMIT ?',
            [$limit]
        );
    }

    public static function find(int $id): ?array
    {
        return DB::fetchOne(
            'SELECT w.*, e.name AS employee_name
             FROM work_logs w
             JOIN employees e ON e.id = w.employee_id
             WHERE w.id = ?',
            [$id]
        );
    }

    public static function create(array $data): int
    {
        DB::query('
            INSERT INTO work_logs
              (employee_id, work_date, start_time, end_time, break_minutes,
               break_auto, is_holiday, is_absent, is_late, is_early_leave, memo)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ', [
            (int) $data['employee_id'],
            $data['work_date'],
            $data['start_time'],
            $data['end_time'],
            (int) ($data['break_minutes'] ?? 0),
            isset($data['break_auto'])    ? 1 : 0,
            isset($data['is_holiday'])    ? 1 : 0,
            isset($data['is_absent'])     ? 1 : 0,
            isset($data['is_late'])       ? 1 : 0,
            isset($data['is_early_leave'])? 1 : 0,
            $data['memo'] ?? null,
        ]);
        return DB::lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        DB::query('
            UPDATE work_logs SET
                employee_id   = ?,
                work_date     = ?,
                start_time    = ?,
                end_time      = ?,
                break_minutes = ?,
                break_auto    = ?,
                is_holiday    = ?,
                is_absent     = ?,
                is_late       = ?,
                is_early_leave= ?,
                memo          = ?
            WHERE id = ?
        ', [
            (int) $data['employee_id'],
            $data['work_date'],
            $data['start_time'],
            $data['end_time'],
            (int) ($data['break_minutes'] ?? 0),
            isset($data['break_auto'])    ? 1 : 0,
            isset($data['is_holiday'])    ? 1 : 0,
            isset($data['is_absent'])     ? 1 : 0,
            isset($data['is_late'])       ? 1 : 0,
            isset($data['is_early_leave'])? 1 : 0,
            $data['memo'] ?? null,
            $id,
        ]);
    }

    public static function delete(int $id): void
    {
        DB::query('DELETE FROM work_logs WHERE id = ?', [$id]);
    }
}
