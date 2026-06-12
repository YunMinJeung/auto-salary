<?php
/**
 * 급여명세서. 한 번 발급(ISSUED)되면 수정 불가.
 * 정정 시: 원본을 CORRECTED로 변경 + 새 버전 ISSUED 발급.
 */
class Payslip
{
    const STATUS_DRAFT     = 'DRAFT';
    const STATUS_CONFIRMED = 'CONFIRMED';
    const STATUS_ISSUED    = 'ISSUED';
    const STATUS_CORRECTED = 'CORRECTED';
    const STATUS_CANCELLED = 'CANCELLED';

    /** 정산 주기 유형 */
    const PERIOD_MONTHLY = 'MONTHLY';
    const PERIOD_WEEKLY  = 'WEEKLY';   // 향후 확장용
    const PERIOD_CUSTOM  = 'CUSTOM';   // 향후 확장용

    /** 지원되는 정산 주기 (MVP: MONTHLY만) */
    const SUPPORTED_PERIOD_TYPES = ['MONTHLY'];

    /** 정산 주기 → 한글 레이블 */
    const PERIOD_TYPE_LABELS = [
        'MONTHLY' => '월급',
        'WEEKLY'  => '주급',
        'CUSTOM'  => '사용자 지정',
    ];

    /** 수정 불가(잠금) 상태 */
    const LOCKED_STATUSES = ['ISSUED', 'CORRECTED', 'CANCELLED'];

    /** 상태 → 한글 레이블 */
    const STATUS_LABELS = [
        'DRAFT'     => '초안',
        'CONFIRMED' => '확인완료',
        'ISSUED'    => '발급완료',
        'CORRECTED' => '정정됨',
        'CANCELLED' => '취소됨',
    ];

    /** 상태 → Bootstrap 배지 클래스 */
    const STATUS_BADGES = [
        'DRAFT'     => 'bg-secondary',
        'CONFIRMED' => 'bg-info',
        'ISSUED'    => 'bg-success',
        'CORRECTED' => 'bg-warning text-dark',
        'CANCELLED' => 'bg-danger',
    ];

    /**
     * 정산 주기 유형과 기간으로 사람이 읽기 좋은 레이블 생성.
     * MVP에서는 MONTHLY만 구현. WEEKLY/CUSTOM은 향후 확장.
     * 주급 정산은 실제 지급 단위별 명세서 발급, 주휴수당, 공제,
     * 월간 요약 리포트가 추가로 필요하므로 MVP에서는 미지원.
     */
    public static function periodLabel(string $periodType, string $periodStart, string $periodEnd): string
    {
        switch ($periodType) {
            case self::PERIOD_MONTHLY:
                $dt = DateTime::createFromFormat('Y-m-d', $periodStart);
                return $dt ? $dt->format('Y') . '년 ' . ltrim($dt->format('m'), '0') . '월 급여' : $periodStart . ' ~ ' . $periodEnd;
            case self::PERIOD_WEEKLY:
                // TODO(WEEKLY): 주급 정산 지원 시 구현 (예: "2026년 6월 2주차 급여")
                return $periodStart . ' ~ ' . $periodEnd . ' 급여';
            default:
                return $periodStart . ' ~ ' . $periodEnd . ' 급여';
        }
    }

    /**
     * MVP 지원 정산 주기 검증. 미지원 주기면 예외.
     * TODO(WEEKLY): SUPPORTED_PERIOD_TYPES에 'WEEKLY' 추가 시 자동 허용됨.
     */
    public static function assertPeriodTypeSupported(string $periodType): void
    {
        if (!in_array($periodType, self::SUPPORTED_PERIOD_TYPES, true)) {
            // 주급/사용자 지정 정산은 MVP 미지원
            throw new RuntimeException(
                "{$periodType} 정산은 아직 지원하지 않습니다. 현재는 월급(MONTHLY) 정산만 지원합니다."
            );
        }
    }

    public static function create(array $data): int
    {
        $periodType = $data['pay_period_type'] ?? self::PERIOD_MONTHLY;
        // period_label이 없으면 정산 주기·기간으로 자동 생성
        $periodLabel = $data['period_label']
            ?? self::periodLabel($periodType, $data['period_start'], $data['period_end']);

        DB::query(
            'INSERT INTO payslips
               (store_id, owner_id, employee_id, payroll_result_id,
                period_start, period_end, payment_date,
                version, status, pay_period_type, period_label,
                gross_pay, total_deductions, net_pay,
                snapshot_json,
                issued_at, issued_by,
                corrected_from_payslip_id, correction_reason)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [
                $data['store_id'],
                $data['owner_id'],
                $data['employee_id'],
                $data['payroll_result_id'] ?? null,
                $data['period_start'],
                $data['period_end'],
                $data['payment_date'] ?? null,
                $data['version'] ?? 1,
                $data['status'] ?? self::STATUS_DRAFT,
                $periodType,
                $periodLabel,
                $data['gross_pay'] ?? 0,
                $data['total_deductions'] ?? 0,
                $data['net_pay'] ?? 0,
                $data['snapshot_json'],
                $data['issued_at'] ?? null,
                $data['issued_by'] ?? null,
                $data['corrected_from_payslip_id'] ?? null,
                $data['correction_reason'] ?? null,
            ]
        );
        return (int) DB::lastInsertId();
    }

    /** owner_id 검증 필수 */
    public static function findById(int $id, int $ownerId): ?array
    {
        return DB::fetchOne(
            'SELECT * FROM payslips WHERE id=? AND owner_id=?',
            [$id, $ownerId]
        ) ?: null;
    }

    /** 같은 기간의 최신 버전 1건 */
    public static function latestByEmployeePeriod(
        int $storeId, int $employeeId, string $periodStart, string $periodEnd
    ): ?array {
        return DB::fetchOne(
            'SELECT * FROM payslips
              WHERE store_id=? AND employee_id=? AND period_start=? AND period_end=?
              ORDER BY version DESC
              LIMIT 1',
            [$storeId, $employeeId, $periodStart, $periodEnd]
        ) ?: null;
    }

    /** 한 직원의 전체 발급 이력 (최신순) */
    public static function allByEmployee(int $storeId, int $employeeId): array
    {
        return DB::fetchAll(
            'SELECT * FROM payslips
              WHERE store_id=? AND employee_id=?
              ORDER BY period_start DESC, version DESC',
            [$storeId, $employeeId]
        );
    }

    /**
     * 사장용 매장 전체 목록.
     * filters: status, employee_id, year, month
     */
    public static function allByStore(int $storeId, array $filters = []): array
    {
        $sql = 'SELECT p.*, sm.name AS employee_name
                  FROM payslips p
                  LEFT JOIN store_members sm ON sm.id = p.employee_id
                 WHERE p.store_id = ?';
        $params = [$storeId];

        if (!empty($filters['status'])) {
            $sql .= ' AND p.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['employee_id'])) {
            $sql .= ' AND p.employee_id = ?';
            $params[] = (int) $filters['employee_id'];
        }
        if (!empty($filters['year']) && !empty($filters['month'])) {
            $monthStart = sprintf('%04d-%02d-01', (int) $filters['year'], (int) $filters['month']);
            $monthEnd   = date('Y-m-t', strtotime($monthStart));
            // 기간이 해당 월과 겹치는 명세서
            $sql .= ' AND p.period_start <= ? AND p.period_end >= ?';
            $params[] = $monthEnd;
            $params[] = $monthStart;
        }

        $sql .= ' ORDER BY p.period_start DESC, p.employee_id ASC, p.version DESC';
        return DB::fetchAll($sql, $params);
    }

    public static function isLocked(array $payslip): bool
    {
        return in_array($payslip['status'], self::LOCKED_STATUSES, true);
    }

    /**
     * 상태 변경. 대상이 이미 잠금 상태면 예외.
     * $extra: cancelled_at, cancelled_by, cancellation_reason 등 추가 컬럼.
     */
    public static function updateStatus(int $id, int $ownerId, string $status, array $extra = []): void
    {
        $current = self::findById($id, $ownerId);
        if (!$current) {
            throw new RuntimeException('급여명세서를 찾을 수 없습니다.');
        }
        if (self::isLocked($current) && $status !== self::STATUS_CORRECTED && $status !== self::STATUS_CANCELLED) {
            throw new RuntimeException('잠금 상태의 급여명세서는 상태를 변경할 수 없습니다.');
        }

        $sets   = ['status = ?'];
        $params = [$status];

        $allowedExtra = ['cancelled_at', 'cancelled_by', 'cancellation_reason'];
        foreach ($allowedExtra as $col) {
            if (array_key_exists($col, $extra)) {
                $sets[]   = "$col = ?";
                $params[] = $extra[$col];
            }
        }

        $params[] = $id;
        $params[] = $ownerId;

        DB::query(
            'UPDATE payslips SET ' . implode(', ', $sets) . ' WHERE id=? AND owner_id=?',
            $params
        );
    }
}
