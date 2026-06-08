<?php
class Setting
{
    public static function get(): array
    {
        $row = DB::fetchOne('SELECT * FROM settings ORDER BY id ASC LIMIT 1');
        if (!$row) {
            DB::query("INSERT INTO settings (business_name) VALUES ('내 사업장')");
            $row = DB::fetchOne('SELECT * FROM settings ORDER BY id ASC LIMIT 1');
        }
        return $row;
    }

    public static function update(int $id, array $data): void
    {
        DB::query('
            UPDATE settings SET
                business_name             = ?,
                employee_count_type       = ?,
                minimum_wage_year         = ?,
                minimum_wage              = ?,
                apply_overtime_premium    = ?,
                apply_night_premium       = ?,
                apply_holiday_premium     = ?,
                auto_break_enabled        = ?,
                auto_weekly_holiday_enabled = ?
            WHERE id = ?
        ', [
            $data['business_name'],
            $data['employee_count_type'],
            (int) $data['minimum_wage_year'],
            (int) $data['minimum_wage'],
            isset($data['apply_overtime_premium'])     ? 1 : 0,
            isset($data['apply_night_premium'])        ? 1 : 0,
            isset($data['apply_holiday_premium'])      ? 1 : 0,
            isset($data['auto_break_enabled'])         ? 1 : 0,
            isset($data['auto_weekly_holiday_enabled'])? 1 : 0,
            $id,
        ]);
    }
}
