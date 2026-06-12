<?php
class WageHistory
{
    /**
     * 특정 날짜 기준 적용 시급 조회.
     * 해당 날짜 이전 이력이 없으면(입사일이 주 시작일보다 늦은 경우 등)
     * 가장 오래된 이력을 반환. 이력 자체가 없으면 null.
     */
    public static function wageAt(int $memberId, string $date): ?int
    {
        $row = DB::fetchOne(
            'SELECT hourly_wage FROM wage_history
             WHERE store_member_id = ? AND effective_from <= ?
             ORDER BY effective_from DESC LIMIT 1',
            [$memberId, $date]
        );
        if ($row) return (int) $row['hourly_wage'];

        // 날짜 이전 이력 없음 → 가장 오래된 이력으로 fallback (입사일 > 주 시작일 엣지케이스)
        $oldest = DB::fetchOne(
            'SELECT hourly_wage FROM wage_history
             WHERE store_member_id = ?
             ORDER BY effective_from ASC LIMIT 1',
            [$memberId]
        );
        return $oldest ? (int) $oldest['hourly_wage'] : null;
    }

    /** 직원의 시급 이력 전체 (최신순) */
    public static function forMember(int $memberId): array
    {
        return DB::fetchAll(
            'SELECT * FROM wage_history WHERE store_member_id = ?
             ORDER BY effective_from DESC',
            [$memberId]
        );
    }

    /** 시급 변경 이력 추가 */
    public static function record(
        int $memberId, int $storeId, int $ownerId,
        int $wage, string $effectiveFrom, string $memo = ''
    ): void {
        DB::query(
            'INSERT INTO wage_history (store_member_id, store_id, owner_id, hourly_wage, effective_from, memo)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$memberId, $storeId, $ownerId, $wage, $effectiveFrom, $memo]
        );
    }
}
