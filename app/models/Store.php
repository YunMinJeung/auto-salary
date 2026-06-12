<?php
class Store
{
    public static function findByOwner(int $ownerId): ?array
    {
        return DB::fetchOne('SELECT * FROM stores WHERE owner_id = ?', [$ownerId]);
    }

    public static function find(int $id): ?array
    {
        return DB::fetchOne('SELECT * FROM stores WHERE id = ?', [$id]);
    }

    /** 점주의 모든 매장 목록 (생성 순) */
    public static function allByOwner(int $ownerId): array
    {
        return DB::fetchAll(
            'SELECT * FROM stores WHERE owner_id = ? ORDER BY id ASC',
            [$ownerId]
        );
    }

    /** 소유권 검증 포함 조회 */
    public static function findOwned(int $id, int $ownerId): ?array
    {
        return DB::fetchOne(
            'SELECT * FROM stores WHERE id = ? AND owner_id = ?',
            [$id, $ownerId]
        );
    }

    public static function create(array $data): int
    {
        DB::query(
            'INSERT INTO stores (owner_id, store_name, business_number, representative_name, address, employee_count, pay_day, employee_count_type, minimum_wage, business_category, business_phone, payroll_manager_name, payroll_manager_phone, weekly_holiday_pay_policy) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['owner_id'],
                $data['store_name'],
                $data['business_number']     ?? null,
                $data['representative_name']  ?? null,
                $data['address']              ?? null,
                $data['employee_count']       ?? null,
                $data['pay_day']              ?? null,
                $data['employee_count_type'] ?? 'under5',
                $data['minimum_wage']        ?? DEFAULT_MIN_WAGE,
                ($data['business_category']     ?? null) ?: null,
                ($data['business_phone']        ?? null) ?: null,
                ($data['payroll_manager_name']  ?? null) ?: null,
                ($data['payroll_manager_phone'] ?? null) ?: null,
                $data['weekly_holiday_pay_policy'] ?? 'AUTO_CHECK',
            ]
        );
        return DB::lastInsertId();
    }

    public static function update(int $id, int $ownerId, array $data): void
    {
        DB::query(
            'UPDATE stores SET store_name=?, employee_count_type=?, minimum_wage=?, notice=?, employee_pay_visibility=?, business_pay_period_type=?, business_category=?, business_phone=?, payroll_manager_name=?, payroll_manager_phone=?, weekly_holiday_pay_policy=?, latitude=?, longitude=?, gps_radius=?, gps_required=? WHERE id=? AND owner_id=?',
            [
                $data['store_name'],
                $data['employee_count_type'],
                (int)$data['minimum_wage'],
                $data['notice'] ?? null,
                $data['employee_pay_visibility'] ?? 'ESTIMATED_TOTAL_ONLY',
                $data['business_pay_period_type'] ?? 'MONTHLY',
                ($data['business_category']     ?? null) ?: null,
                ($data['business_phone']        ?? null) ?: null,
                ($data['payroll_manager_name']  ?? null) ?: null,
                ($data['payroll_manager_phone'] ?? null) ?: null,
                $data['weekly_holiday_pay_policy'] ?? 'AUTO_CHECK',
                $data['latitude']     ?? null,
                $data['longitude']    ?? null,
                (int)($data['gps_radius'] ?? 200),
                !empty($data['gps_required']) ? 1 : 0,
                $id,
                $ownerId,
            ]
        );
    }
}
