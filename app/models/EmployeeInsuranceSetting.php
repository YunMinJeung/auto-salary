<?php
class EmployeeInsuranceSetting
{
    public static function findByMember(int $storeId, int $storeMemberId): array
    {
        return DB::fetchOne(
            'SELECT * FROM employee_insurance_settings WHERE store_id = ? AND store_member_id = ?',
            [$storeId, $storeMemberId]
        ) ?: [];
    }

    public static function allForStore(int $storeId): array
    {
        return DB::fetchAll(
            'SELECT * FROM employee_insurance_settings WHERE store_id = ?',
            [$storeId]
        );
    }

    /**
     * Upsert 방식으로 저장.
     * warning_acknowledged 는 user_selected_status = 'not_enrolled' 일 때만 1로 기록.
     */
    public static function save(
        int $storeId,
        int $storeMemberId,
        array $post,
        array $judgment,
        int $acknowledgedByUserId = 0
    ): void {
        $duration    = $judgment['duration']         ?? 'undefined';
        $empType     = $judgment['employment_type']  ?? 'regular';
        $userStatus  = $post['user_selected_status']         ?? 'needs_review';
        $memo        = trim($post['insurance_memo']          ?? '');
        $monthly     = (float)($judgment['monthly_hours']    ?? 0);

        $np = $judgment['national_pension']     ?? null;
        $hi = $judgment['health_insurance']     ?? null;
        $ei = $judgment['employment_insurance'] ?? null;

        // 경고 확인 여부 — 미가입 상태일 때만 의미
        $acknowledged = ($userStatus === 'not_enrolled')
            ? (int)($post['warning_acknowledged'] ?? 0)
            : 0;

        $existing = self::findByMember($storeId, $storeMemberId);

        // 최초 확인 시각/담당자는 한 번만 기록 (이미 기록된 경우 유지)
        if ($acknowledged && !($existing['warning_acknowledged'] ?? 0)) {
            $acknowledgedAt = date('Y-m-d H:i:s');
            $acknowledgedBy = $acknowledgedByUserId ?: null;
        } else {
            $acknowledgedAt = $existing['warning_acknowledged_at']          ?? null;
            $acknowledgedBy = $existing['warning_acknowledged_by_user_id']  ?? null;
        }

        $jsonStr = json_encode($judgment, JSON_UNESCAPED_UNICODE);

        if ($existing) {
            DB::query(
                'UPDATE employee_insurance_settings SET
                    expected_employment_duration = ?,
                    employment_type = ?,
                    monthly_scheduled_hours = ?,
                    national_pension_status = ?,
                    health_insurance_status = ?,
                    employment_insurance_status = ?,
                    user_selected_status = ?,
                    system_judgment_json = ?,
                    warning_acknowledged = ?,
                    warning_acknowledged_at = ?,
                    warning_acknowledged_by_user_id = ?,
                    memo = ?
                 WHERE store_id = ? AND store_member_id = ?',
                [$duration, $empType, $monthly, $np, $hi, $ei, $userStatus,
                 $jsonStr, $acknowledged, $acknowledgedAt, $acknowledgedBy,
                 $memo, $storeId, $storeMemberId]
            );
        } else {
            DB::query(
                'INSERT INTO employee_insurance_settings
                    (store_id, store_member_id, expected_employment_duration, employment_type,
                     monthly_scheduled_hours, national_pension_status, health_insurance_status,
                     employment_insurance_status, user_selected_status, system_judgment_json,
                     warning_acknowledged, warning_acknowledged_at, warning_acknowledged_by_user_id,
                     memo)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$storeId, $storeMemberId, $duration, $empType, $monthly, $np, $hi, $ei,
                 $userStatus, $jsonStr, $acknowledged, $acknowledgedAt, $acknowledgedBy, $memo]
            );
        }
    }
}
