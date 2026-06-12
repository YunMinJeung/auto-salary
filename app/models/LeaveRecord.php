<?php
/**
 * LeaveRecord — 연차/휴가 기록.
 * employee_id 는 store_members.id 를 참조한다.
 *
 * 연차 발생 기준(근로기준법 제60조 참고값):
 *  - 근속 1년 미만: 1개월 개근 시 1일씩 발생 (최대 11일).
 *  - 근속 1년 이상: 15일.
 *  - 3년 이상 매 2년마다 1일 가산은 단순화를 위해 미반영 (안내 문구로 보완).
 *  - 5인 미만 사업장은 법정 연차 의무 대상 아님 → 약정 연차로만 인정.
 * 실제 부여 일수는 사업장 규모·계약·소정근로일에 따라 달라질 수 있어 참고용이다.
 */
class LeaveRecord
{
    public static function allForEmployee(int $ownerId, int $employeeId): array
    {
        return DB::fetchAll(
            'SELECT * FROM leave_records
              WHERE owner_id = ? AND employee_id = ?
              ORDER BY start_date DESC',
            [$ownerId, $employeeId]
        );
    }

    public static function find(int $id, int $ownerId): ?array
    {
        return DB::fetchOne(
            'SELECT * FROM leave_records WHERE id = ? AND owner_id = ?',
            [$id, $ownerId]
        );
    }

    public static function create(array $data): int
    {
        DB::query(
            'INSERT INTO leave_records
               (owner_id, store_id, employee_id, leave_type, start_date, end_date, days, status, memo)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                Auth::ownerId(),
                Auth::storeId(),
                (int) $data['employee_id'],
                $data['leave_type'] ?? 'annual',
                $data['start_date'],
                $data['end_date'],
                (float) ($data['days'] ?? 1.0),
                $data['status'] ?? 'approved',
                ($data['memo'] ?? null) ?: null,
            ]
        );
        return DB::lastInsertId();
    }

    public static function update(int $id, int $ownerId, array $data): void
    {
        DB::query(
            'UPDATE leave_records SET
                employee_id = ?,
                leave_type  = ?,
                start_date  = ?,
                end_date    = ?,
                days        = ?,
                status      = ?,
                memo        = ?
             WHERE id = ? AND owner_id = ?',
            [
                (int) $data['employee_id'],
                $data['leave_type'] ?? 'annual',
                $data['start_date'],
                $data['end_date'],
                (float) ($data['days'] ?? 1.0),
                $data['status'] ?? 'approved',
                ($data['memo'] ?? null) ?: null,
                $id,
                $ownerId,
            ]
        );
    }

    public static function delete(int $id, int $ownerId): void
    {
        DB::query('DELETE FROM leave_records WHERE id = ? AND owner_id = ?', [$id, $ownerId]);
    }

    /**
     * 연차 잔여 계산 (근기법 제60조 참고값).
     *
     * @return array ['granted' => float, 'used' => float, 'remaining' => float]
     */
    public static function annualBalance(int $ownerId, int $employeeId): array
    {
        $member = StoreMember::find($employeeId, Auth::storeId());
        $startDate = $member['employment_start_date'] ?? null;

        $granted = 0.0;
        if (!empty($startDate)) {
            $start = new DateTime($startDate);
            $now   = new DateTime('today');
            if ($now >= $start) {
                $diff   = $start->diff($now);
                $months = $diff->y * 12 + $diff->m;
                if ($diff->y >= 1) {
                    $granted = 15.0; // 1년 이상: 기본 15일 (가산 미반영)
                } else {
                    $granted = (float) min(11, $months); // 1년 미만: 개근 가정 월 1일, 최대 11일
                }
            }
        }

        // 사용: 'annual' 타입 'approved' 합산
        $usedRow = DB::fetchOne(
            "SELECT COALESCE(SUM(days), 0) AS used
               FROM leave_records
              WHERE owner_id = ? AND employee_id = ?
                AND leave_type = 'annual' AND status = 'approved'",
            [$ownerId, $employeeId]
        );
        $used = (float) ($usedRow['used'] ?? 0);

        return [
            'granted'   => $granted,
            'used'      => $used,
            'remaining' => $granted - $used,
        ];
    }
}
