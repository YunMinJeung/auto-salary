<?php
class Employee
{
    public static function all(): array
    {
        return DB::fetchAll(
            'SELECT * FROM employees WHERE employment_end_date IS NULL OR employment_end_date >= CURDATE()
             ORDER BY name ASC'
        );
    }

    public static function allIncludeRetired(): array
    {
        return DB::fetchAll('SELECT * FROM employees ORDER BY name ASC');
    }

    public static function find(int $id): ?array
    {
        return DB::fetchOne('SELECT * FROM employees WHERE id = ?', [$id]);
    }

    public static function create(array $data): int
    {
        DB::query('
            INSERT INTO employees
              (name, hourly_wage, employment_start_date, employment_end_date,
               weekly_scheduled_days, weekly_scheduled_hours, weekly_holiday_enabled, memo)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ', [
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
            WHERE id = ?
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
        ]);
    }

    public static function delete(int $id): void
    {
        DB::query('DELETE FROM employees WHERE id = ?', [$id]);
    }
}
