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
            'SELECT sm.*, u.email AS user_email, s.store_name
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
            'SELECT sm.*, s.store_name, s.owner_id
             FROM store_members sm
             JOIN stores s ON s.id = sm.store_id
             WHERE sm.user_id = ?',
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
              employment_start_date, employment_end_date, is_active, memo)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
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
             employment_start_date=?, employment_end_date=?, is_active=?, memo=?
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
            $d['user_id']           ?: null,
            $d['employee_id']       ?: null,
            $d['member_role']       ?? 'employee',
            $d['name'],
            $d['phone']             ?: null,
            (int)($d['hourly_wage']               ?? DEFAULT_MIN_WAGE),
            (float)($d['weekly_scheduled_hours']  ?? 40),
            (int)($d['weekly_scheduled_days']     ?? 5),
            (int)(bool)($d['weekly_holiday_enabled'] ?? 1),
            $d['night_premium_type']    ?? 'global',
            $d['night_premium_value']   ?: null,
            $d['overtime_premium_type'] ?? 'global',
            $d['overtime_premium_value'] ?: null,
            $d['holiday_premium_type']  ?? 'global',
            $d['holiday_premium_value'] ?: null,
            $d['employment_start_date'] ?: null,
            $d['employment_end_date']   ?: null,
            (int)(bool)($d['is_active'] ?? 1),
            $d['memo']              ?: null,
        ];
    }
}
