<?php
/**
 * 연도별 법정 최저시급 관리.
 * 글로벌 테이블 — owner_id 없음.
 */
class MinimumWage
{
    public static function all(): array
    {
        return DB::fetchAll('SELECT * FROM minimum_wages ORDER BY year DESC');
    }

    public static function forYear(int $year): ?array
    {
        return DB::fetchOne('SELECT * FROM minimum_wages WHERE year = ?', [$year]);
    }

    /**
     * 특정 날짜(근무일)에 적용되는 시급 반환.
     * 해당 연도 데이터가 없으면 가장 가까운 이전 연도로 fallback.
     * 그것도 없으면 DEFAULT_MIN_WAGE 상수 반환.
     */
    public static function effectiveHourlyWage(string $date): int
    {
        $year = (int) date('Y', strtotime($date));
        $row  = self::forYear($year);

        if (!$row) {
            $row = DB::fetchOne(
                'SELECT * FROM minimum_wages WHERE year <= ? ORDER BY year DESC LIMIT 1',
                [$year]
            );
        }

        return $row ? (int) $row['hourly_wage'] : DEFAULT_MIN_WAGE;
    }

    /** 현재 연도 최저시급 */
    public static function currentHourlyWage(): int
    {
        return self::effectiveHourlyWage(date('Y-m-d'));
    }

    /** 현재 연도 레코드 전체 (없으면 null) */
    public static function current(): ?array
    {
        return self::forYear((int) date('Y'));
    }

    /**
     * 연도 저장 (존재하면 UPDATE, 없으면 INSERT).
     */
    public static function save(array $data): void
    {
        $year         = (int) $data['year'];
        $hourly       = (int) $data['hourly_wage'];
        // 월환산액: 지정하지 않으면 시급 × 209 자동 계산
        $monthly      = isset($data['monthly_wage']) && (int) $data['monthly_wage'] > 0
                        ? (int) $data['monthly_wage']
                        : $hourly * 209;
        $effectiveFrom = $data['effective_from'] ?: "{$year}-01-01";
        $effectiveTo   = $data['effective_to']   ?: "{$year}-12-31";
        $memo          = $data['memo']            ?: null;

        DB::query(
            'INSERT INTO minimum_wages
               (year, hourly_wage, monthly_wage, effective_from, effective_to, memo)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
               hourly_wage=VALUES(hourly_wage),
               monthly_wage=VALUES(monthly_wage),
               effective_from=VALUES(effective_from),
               effective_to=VALUES(effective_to),
               memo=VALUES(memo)',
            [$year, $hourly, $monthly, $effectiveFrom, $effectiveTo, $memo]
        );
    }

    public static function delete(int $id): void
    {
        DB::query('DELETE FROM minimum_wages WHERE id = ?', [$id]);
    }
}
