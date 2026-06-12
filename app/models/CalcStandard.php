<?php
class CalcStandard
{
    public static function all(): array
    {
        return DB::fetchAll("SELECT * FROM calc_standards ORDER BY year DESC");
    }

    public static function find(int $id): ?array
    {
        return DB::fetchOne("SELECT * FROM calc_standards WHERE id = ?", [$id]);
    }

    public static function findByYear(int $year): ?array
    {
        return DB::fetchOne("SELECT * FROM calc_standards WHERE year = ?", [$year]);
    }

    public static function create(array $data): int
    {
        DB::query(
            "INSERT INTO calc_standards
               (year, min_hourly_wage, night_start_time, night_end_time,
                insurance_national_pension_rate, insurance_health_rate,
                insurance_long_term_care_rate, insurance_employment_rate,
                description, applies_from, applies_to, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                (int)$data['year'],
                (int)$data['min_hourly_wage'],
                $data['night_start_time'] ?? '22:00:00',
                $data['night_end_time']   ?? '06:00:00',
                (float)($data['insurance_national_pension_rate'] ?? 0.0450),
                (float)($data['insurance_health_rate']           ?? 0.0354),
                (float)($data['insurance_long_term_care_rate']   ?? 0.1281),
                (float)($data['insurance_employment_rate']       ?? 0.0090),
                $data['description']  ?? null,
                $data['applies_from'],
                $data['applies_to']   ?? null,
                isset($data['is_active']) ? 1 : 0,
            ]
        );
        $id = DB::lastInsertId();
        AuditLog::record('CALC_STANDARD_CREATE', 'calc_standard', $id, null, $data);
        return $id;
    }

    public static function update(int $id, array $data): void
    {
        $before = self::find($id);
        DB::query(
            "UPDATE calc_standards SET
               year = ?, min_hourly_wage = ?, night_start_time = ?, night_end_time = ?,
               insurance_national_pension_rate = ?, insurance_health_rate = ?,
               insurance_long_term_care_rate = ?, insurance_employment_rate = ?,
               description = ?, applies_from = ?, applies_to = ?, is_active = ?
             WHERE id = ?",
            [
                (int)$data['year'],
                (int)$data['min_hourly_wage'],
                $data['night_start_time'] ?? '22:00:00',
                $data['night_end_time']   ?? '06:00:00',
                (float)($data['insurance_national_pension_rate'] ?? 0.0450),
                (float)($data['insurance_health_rate']           ?? 0.0354),
                (float)($data['insurance_long_term_care_rate']   ?? 0.1281),
                (float)($data['insurance_employment_rate']       ?? 0.0090),
                $data['description']  ?? null,
                $data['applies_from'],
                $data['applies_to']   ?? null,
                isset($data['is_active']) ? 1 : 0,
                $id,
            ]
        );
        AuditLog::record('CALC_STANDARD_UPDATE', 'calc_standard', $id, $before, $data);
    }
}
