<?php
class StoreMember
{
    public static function allForStore(int $storeId, bool $activeOnly = false): array
    {
        $sql = 'SELECT sm.*, u.email AS user_email
                FROM store_members sm
                LEFT JOIN users u ON u.id = sm.user_id
                WHERE sm.store_id = ?';
        $params = [$storeId];
        if ($activeOnly) {
            $sql .= ' AND sm.is_active = 1';
        }
        $sql .= ' ORDER BY sm.name';
        return DB::fetchAll($sql, $params);
    }

    public static function find(int $id, int $storeId): ?array
    {
        return DB::fetchOne(
            'SELECT sm.*, u.email AS user_email, s.store_name, s.owner_id, s.notice AS store_notice
             FROM store_members sm
             LEFT JOIN users u ON u.id = sm.user_id
             LEFT JOIN stores s ON s.id = sm.store_id
             WHERE sm.id = ? AND sm.store_id = ?',
            [$id, $storeId]
        );
    }

    /** 알바생 로그인 후 본인 레코드 조회 */
    public static function findByUserId(int $userId): ?array
    {
        return DB::fetchOne(
            'SELECT sm.*, s.store_name, s.owner_id, s.notice AS store_notice
             FROM store_members sm
             JOIN stores s ON s.id = sm.store_id
             WHERE sm.user_id = ? ORDER BY sm.id ASC LIMIT 1',
            [$userId]
        );
    }

    /** 알바가 속한 모든 활성 매장 목록 (다중 매장 선택용) */
    public static function allByUserId(int $userId): array
    {
        return DB::fetchAll(
            'SELECT sm.*, s.store_name, s.owner_id, s.notice AS store_notice
             FROM store_members sm
             JOIN stores s ON s.id = sm.store_id
             WHERE sm.user_id = ? AND sm.is_active = 1
             ORDER BY s.store_name ASC',
            [$userId]
        );
    }

    public static function create(array $data): int
    {
        DB::query(
            'INSERT INTO store_members
             (store_id, user_id, employee_id, member_role, name, phone,
              hourly_wage, weekly_scheduled_hours, weekly_scheduled_days, weekly_holiday_enabled,
              night_premium_type, night_premium_value,
              overtime_premium_type, overtime_premium_value,
              holiday_premium_type, holiday_premium_value,
              employment_start_date, employment_end_date, is_active, memo, is_minor, date_of_birth,
              work_start_time, work_end_time, daily_break_minutes,
              trial_end_date, trial_hourly_wage, account_status, employment_status, created_by_user_id,
              works_at_other_business, other_business_insurance_enrolled, health_insurance_type,
              employee_code, employment_type, other_business_employment_insurance,
              national_pension_status, health_insurance_status, long_term_care_insurance_status,
              employment_insurance_status, industrial_accident_insurance_status,
              income_tax_method, dependent_count, has_non_taxable_items)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            self::buildParams($data)
        );
        return DB::lastInsertId();
    }

    public static function update(int $id, int $storeId, array $data): void
    {
        $params   = self::buildParams($data);
        $params[] = $id;
        $params[] = $storeId;
        DB::query(
            'UPDATE store_members SET
             store_id=?, user_id=?, employee_id=?, member_role=?, name=?, phone=?,
             hourly_wage=?, weekly_scheduled_hours=?, weekly_scheduled_days=?, weekly_holiday_enabled=?,
             night_premium_type=?, night_premium_value=?,
             overtime_premium_type=?, overtime_premium_value=?,
             holiday_premium_type=?, holiday_premium_value=?,
             employment_start_date=?, employment_end_date=?, is_active=?, memo=?, is_minor=?, date_of_birth=?,
             work_start_time=?, work_end_time=?, daily_break_minutes=?,
             trial_end_date=?, trial_hourly_wage=?,
             account_status=?, employment_status=?,
             created_by_user_id=COALESCE(created_by_user_id, ?),
             works_at_other_business=?, other_business_insurance_enrolled=?, health_insurance_type=?,
             employee_code=?, employment_type=?, other_business_employment_insurance=?,
             national_pension_status=?, health_insurance_status=?, long_term_care_insurance_status=?,
             employment_insurance_status=?, industrial_accident_insurance_status=?,
             income_tax_method=?, dependent_count=?, has_non_taxable_items=?
             WHERE id=? AND store_id=?',
            $params
        );
    }

    public static function delete(int $id, int $storeId): void
    {
        DB::query('DELETE FROM store_members WHERE id=? AND store_id=?', [$id, $storeId]);
    }

    /** 로그인 계정 연결 */
    public static function linkUser(int $id, int $storeId, int $userId): void
    {
        DB::query(
            'UPDATE store_members SET user_id=? WHERE id=? AND store_id=?',
            [$userId, $id, $storeId]
        );
    }

    private static function buildParams(array $d): array
    {
        return [
            $d['store_id'],
            ($d['user_id']    ?? null) ?: null,
            ($d['employee_id'] ?? null) ?: null,
            $d['member_role']       ?? 'employee',
            $d['name'],
            ($d['phone']      ?? null) ?: null,
            (int)($d['hourly_wage']               ?? DEFAULT_MIN_WAGE),
            (float)($d['weekly_scheduled_hours']  ?? 40),
            (int)($d['weekly_scheduled_days']     ?? 5),
            (int)(bool)($d['weekly_holiday_enabled'] ?? 1),
            $d['night_premium_type']    ?? 'global',
            ($d['night_premium_value']    ?? null) ?: null,
            $d['overtime_premium_type'] ?? 'global',
            ($d['overtime_premium_value'] ?? null) ?: null,
            $d['holiday_premium_type']  ?? 'global',
            ($d['holiday_premium_value']  ?? null) ?: null,
            ($d['employment_start_date']  ?? null) ?: null,
            ($d['employment_end_date']    ?? null) ?: null,
            (int)(bool)($d['is_active'] ?? 1),
            ($d['memo']       ?? null) ?: null,
            (int)(bool)($d['is_minor'] ?? 0),
            ($d['date_of_birth'] ?? null) ?: null,
            ($d['work_start_time']     ?? null) ?: null,
            ($d['work_end_time']       ?? null) ?: null,
            isset($d['daily_break_minutes']) && $d['daily_break_minutes'] !== '' ? (int)$d['daily_break_minutes'] : null,
            ($d['trial_end_date']      ?? null) ?: null,
            isset($d['trial_hourly_wage']) && $d['trial_hourly_wage'] !== '' ? (int)$d['trial_hourly_wage'] : null,
            $d['account_status']     ?? 'no_account',
            $d['employment_status']  ?? 'active',
            isset($d['created_by_user_id']) ? (int)$d['created_by_user_id'] : null,
            in_array($d['works_at_other_business'] ?? '', ['NO','YES','UNKNOWN'], true) ? $d['works_at_other_business'] : 'UNKNOWN',
            in_array($d['other_business_insurance_enrolled'] ?? '', ['NO','YES','UNKNOWN'], true) ? $d['other_business_insurance_enrolled'] : 'UNKNOWN',
            in_array($d['health_insurance_type'] ?? '', ['LOCAL','EMPLOYEE','DEPENDENT','UNKNOWN'], true) ? $d['health_insurance_type'] : 'UNKNOWN',
            ($d['employee_code']   ?? null) ?: null,
            in_array($d['employment_type'] ?? '', ['PART_TIME','FULL_TIME','TEMPORARY','DAILY'], true) ? $d['employment_type'] : null,
            in_array($d['other_business_employment_insurance'] ?? '', ['NO','YES','UNKNOWN'], true) ? $d['other_business_employment_insurance'] : 'UNKNOWN',
            $d['national_pension_status']              ?? 'NEEDS_CHECK',
            $d['health_insurance_status']              ?? 'NEEDS_CHECK',
            $d['long_term_care_insurance_status']      ?? 'NEEDS_CHECK',
            $d['employment_insurance_status']          ?? 'NEEDS_CHECK',
            $d['industrial_accident_insurance_status'] ?? 'NEEDS_CHECK',
            $d['income_tax_method']                    ?? 'NEEDS_CHECK',
            isset($d['dependent_count']) && $d['dependent_count'] !== '' ? (int)$d['dependent_count'] : null,
            (int)(bool)($d['has_non_taxable_items'] ?? 0),
        ];
    }
}
