<?php
/**
 * Schedule — 주간 근무 스케줄(근무표).
 * employee_id 는 store_members.id 를 참조한다.
 */
class Schedule
{
    /** 주간(월~일) 스케줄 조회 — 직원명 JOIN. */
    public static function allForWeek(int $ownerId, int $storeId, string $weekStart): array
    {
        $weekEnd = date('Y-m-d', strtotime($weekStart . ' +6 days'));
        return DB::fetchAll(
            "SELECT s.*, sm.name AS employee_name
               FROM schedules s
               JOIN store_members sm ON sm.id = s.employee_id
              WHERE s.owner_id = ? AND s.store_id = ?
                AND s.schedule_date BETWEEN ? AND ?
              ORDER BY s.schedule_date ASC, s.start_time ASC",
            [$ownerId, $storeId, $weekStart, $weekEnd]
        );
    }

    public static function find(int $id, int $ownerId): ?array
    {
        return DB::fetchOne(
            'SELECT * FROM schedules WHERE id = ? AND owner_id = ?',
            [$id, $ownerId]
        );
    }

    public static function create(array $data): int
    {
        DB::query(
            'INSERT INTO schedules
               (owner_id, store_id, employee_id, schedule_date, start_time, end_time, break_minutes, memo)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                Auth::ownerId(),
                Auth::storeId(),
                (int) $data['employee_id'],
                $data['schedule_date'],
                $data['start_time'],
                $data['end_time'],
                (int) ($data['break_minutes'] ?? 0),
                ($data['memo'] ?? null) ?: null,
            ]
        );
        return DB::lastInsertId();
    }

    public static function update(int $id, int $ownerId, array $data): void
    {
        DB::query(
            'UPDATE schedules SET
                employee_id   = ?,
                schedule_date = ?,
                start_time    = ?,
                end_time      = ?,
                break_minutes = ?,
                memo          = ?
             WHERE id = ? AND owner_id = ?',
            [
                (int) $data['employee_id'],
                $data['schedule_date'],
                $data['start_time'],
                $data['end_time'],
                (int) ($data['break_minutes'] ?? 0),
                ($data['memo'] ?? null) ?: null,
                $id,
                $ownerId,
            ]
        );
    }

    public static function delete(int $id, int $ownerId): void
    {
        DB::query('DELETE FROM schedules WHERE id = ? AND owner_id = ?', [$id, $ownerId]);
    }

    /**
     * 예정 인건비 계산 — 직원별 시급 × 예정 유급 근무시간 합산.
     * 주휴수당·가산수당은 제외한 단순 예정치(참고용).
     *
     * @return array [
     *   'rows'  => [['employee_id','name','minutes','amount'], ...],
     *   'total_minutes' => int,
     *   'total_amount'  => int,
     * ]
     */
    public static function estimatedWeeklyPayroll(int $ownerId, int $storeId, string $weekStart): array
    {
        $schedules = self::allForWeek($ownerId, $storeId, $weekStart);

        // 직원 시급 맵
        $wageMap = [];
        foreach (StoreMember::allForStore($storeId) as $m) {
            $wageMap[(int) $m['id']] = (int) ($m['hourly_wage'] ?? 0);
        }

        $byEmp = [];
        foreach ($schedules as $s) {
            $empId = (int) $s['employee_id'];
            $start = strtotime('2000-01-01 ' . $s['start_time']);
            $end   = strtotime('2000-01-01 ' . $s['end_time']);
            if ($end <= $start) continue; // 익일 근무는 예정치 단순화 위해 제외
            $paidMin = (int) (($end - $start) / 60) - (int) $s['break_minutes'];
            if ($paidMin < 0) $paidMin = 0;

            if (!isset($byEmp[$empId])) {
                $byEmp[$empId] = ['employee_id' => $empId, 'name' => $s['employee_name'], 'minutes' => 0];
            }
            $byEmp[$empId]['minutes'] += $paidMin;
        }

        $rows = [];
        $totalMinutes = 0;
        $totalAmount  = 0;
        foreach ($byEmp as $row) {
            $wage   = $wageMap[$row['employee_id']] ?? 0;
            $amount = (int) round($row['minutes'] / 60 * $wage);
            $row['amount'] = $amount;
            $rows[] = $row;
            $totalMinutes += $row['minutes'];
            $totalAmount  += $amount;
        }

        return [
            'rows'          => $rows,
            'total_minutes' => $totalMinutes,
            'total_amount'  => $totalAmount,
        ];
    }
}
