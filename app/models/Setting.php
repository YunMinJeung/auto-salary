<?php
class Setting
{
    public static function get(): array
    {
        $storeId = Auth::storeId();
        $ownerId = Auth::ownerId();

        if (!$storeId) {
            // storeId 미확정 엣지케이스 — owner 기준 폴백
            return DB::fetchOne('SELECT * FROM settings WHERE owner_id = ? LIMIT 1', [$ownerId]) ?? [];
        }

        $row = DB::fetchOne('SELECT * FROM settings WHERE store_id = ? LIMIT 1', [$storeId]);
        if (!$row) {
            // 새 매장 — 기존 설정 복사 또는 기본값으로 생성
            $src = DB::fetchOne('SELECT * FROM settings WHERE owner_id = ? LIMIT 1', [$ownerId]);
            $storeName = DB::fetchOne(
                'SELECT store_name FROM stores WHERE id = ? AND owner_id = ?',
                [$storeId, $ownerId]
            )['store_name'] ?? '내 사업장';
            DB::query(
                "INSERT INTO settings
                 (owner_id, store_id, business_name, employee_count_type,
                  minimum_wage_year, minimum_wage,
                  apply_overtime_premium, apply_night_premium, apply_holiday_premium,
                  auto_break_enabled, auto_weekly_holiday_enabled,
                  show_pay_to_employee,
                  apply_national_pension, apply_health_insurance, apply_employment_insurance)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                [
                    $ownerId, $storeId, $storeName,
                    $src['employee_count_type']          ?? 'under5',
                    $src['minimum_wage_year']            ?? (int)date('Y'),
                    $src['minimum_wage']                 ?? DEFAULT_MIN_WAGE,
                    $src['apply_overtime_premium']       ?? 0,
                    $src['apply_night_premium']          ?? 0,
                    $src['apply_holiday_premium']        ?? 0,
                    $src['auto_break_enabled']           ?? 1,
                    $src['auto_weekly_holiday_enabled']  ?? 1,
                    $src['show_pay_to_employee']         ?? 1,
                    $src['apply_national_pension']       ?? 1,
                    $src['apply_health_insurance']       ?? 1,
                    $src['apply_employment_insurance']   ?? 1,
                ]
            );
            $row = DB::fetchOne('SELECT * FROM settings WHERE store_id = ? LIMIT 1', [$storeId]);
        }
        return $row;
    }

    public static function update(int $id, array $data): void
    {
        // owner_id 조건 포함 — 타 점주의 설정을 수정할 수 없음
        DB::query('
            UPDATE settings SET
                business_name               = ?,
                employee_count_type         = ?,
                minimum_wage_year           = ?,
                minimum_wage                = ?,
                apply_overtime_premium      = ?,
                apply_night_premium         = ?,
                apply_holiday_premium       = ?,
                auto_break_enabled          = ?,
                auto_weekly_holiday_enabled = ?,
                show_pay_to_employee        = ?,
                apply_national_pension      = ?,
                apply_health_insurance      = ?,
                apply_employment_insurance  = ?
            WHERE id = ? AND owner_id = ?
        ', [
            $data['business_name'],
            $data['employee_count_type'],
            (int) $data['minimum_wage_year'],
            (int) $data['minimum_wage'],
            isset($data['apply_overtime_premium'])      ? 1 : 0,
            isset($data['apply_night_premium'])         ? 1 : 0,
            isset($data['apply_holiday_premium'])       ? 1 : 0,
            isset($data['auto_break_enabled'])          ? 1 : 0,
            isset($data['auto_weekly_holiday_enabled']) ? 1 : 0,
            isset($data['show_pay_to_employee'])        ? 1 : 0,
            isset($data['apply_national_pension'])      ? 1 : 0,
            isset($data['apply_health_insurance'])      ? 1 : 0,
            isset($data['apply_employment_insurance'])  ? 1 : 0,
            $id,
            Auth::ownerId(),
        ]);
    }

    /** 직원 앱에서 매장 ID로 설정 조회 (읽기 전용) */
    public static function getByStoreId(int $storeId): array
    {
        return DB::fetchOne('SELECT * FROM settings WHERE store_id = ? LIMIT 1', [$storeId]) ?? [];
    }

    /** @deprecated getByStoreId 사용 권장 */
    public static function getByOwnerId(int $ownerId): array
    {
        return DB::fetchOne('SELECT * FROM settings WHERE owner_id = ? LIMIT 1', [$ownerId]) ?? [];
    }
}
