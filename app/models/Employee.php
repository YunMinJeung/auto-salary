<?php
class Employee
{
    // ─── 조회 (모두 owner_id 스코프 적용) ────────────────────

    public static function all(): array
    {
        return DB::fetchAll(
            'SELECT * FROM employees
             WHERE owner_id = ?
               AND (employment_end_date IS NULL OR employment_end_date >= CURDATE())
             ORDER BY name ASC',
            [Auth::ownerId()]
        );
    }

    public static function allIncludeRetired(): array
    {
        return DB::fetchAll(
            'SELECT * FROM employees WHERE owner_id = ? ORDER BY name ASC',
            [Auth::ownerId()]
        );
    }

    /**
     * ID로 단건 조회.
     * owner_id 미일치 시 null 반환 → 컨트롤러에서 404 처리.
     * 이를 통해 타 점주 데이터 접근을 원천 차단.
     */
    public static function find(int $id): ?array
    {
        return DB::fetchOne(
            'SELECT * FROM employees WHERE id = ? AND owner_id = ?',
            [$id, Auth::ownerId()]
        );
    }

    // ─── 생성 (owner_id는 세션에서 자동 주입) ─────────────────

    public static function create(array $data): int
    {
        DB::query('
            INSERT INTO employees
              (owner_id, name, hourly_wage, employment_start_date, employment_end_date,
               weekly_scheduled_days, weekly_scheduled_hours, weekly_holiday_enabled, memo)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ', [
            Auth::ownerId(),          // 프론트 입력값 사용 금지 — 세션 기준
            $data['name'],
            (int)   $data['hourly_wage'],
            $data['employment_start_date'],
            $data['employment_end_date'] ?: null,
            (int)   $data['weekly_scheduled_days'],
            (float) $data['weekly_scheduled_hours'],
            isset($data['weekly_holiday_enabled']) ? 1 : 0,
            $data['memo'] ?? null,
        ]);
        return DB::lastInsertId();
    }

    // ─── 수정 (WHERE에 owner_id 포함) ─────────────────────────

    public static function update(int $id, array $data): void
    {
        DB::query('
            UPDATE employees SET
                name                   = ?,
                hourly_wage            = ?,
                employment_start_date  = ?,
                employment_end_date    = ?,
                weekly_scheduled_days  = ?,
                weekly_scheduled_hours = ?,
                weekly_holiday_enabled = ?,
                memo                   = ?
            WHERE id = ? AND owner_id = ?
        ', [
            $data['name'],
            (int)   $data['hourly_wage'],
            $data['employment_start_date'],
            $data['employment_end_date'] ?: null,
            (int)   $data['weekly_scheduled_days'],
            (float) $data['weekly_scheduled_hours'],
            isset($data['weekly_holiday_enabled']) ? 1 : 0,
            $data['memo'] ?? null,
            $id,
            Auth::ownerId(),
        ]);
    }

    // ─── 삭제 (WHERE에 owner_id 포함) ─────────────────────────

    public static function delete(int $id): void
    {
        DB::query(
            'DELETE FROM employees WHERE id = ? AND owner_id = ?',
            [$id, Auth::ownerId()]
        );
    }
}
