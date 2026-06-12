<?php
/**
 * 노무 리스크 알림 모델.
 * 입력된 근무기록/설정값 기준으로 감지된 노무 리스크를 저장·조회한다.
 * 모든 조회는 owner_id로 격리한다.
 */
class LaborRiskAlert
{
    /**
     * 동일 alert_code + related_type + related_id 에 대해 open/acknowledged 상태가
     * 이미 있으면 기존 id를 반환하고, 없으면 새로 INSERT 후 id 반환.
     */
    public static function upsertByCode(
        int $ownerId,
        ?int $storeMemberId,
        string $alertCode,
        string $severity,
        string $scope,
        string $title,
        string $message,
        ?string $empMsg,
        ?string $legal,
        string $relType,
        ?int $relId
    ): int {
        $existing = DB::fetchOne(
            "SELECT id FROM labor_risk_alerts
             WHERE owner_id = ? AND alert_code = ? AND related_type = ?
               AND related_id <=> ?
               AND status IN ('open','acknowledged')
             LIMIT 1",
            [$ownerId, $alertCode, $relType, $relId]
        );
        if ($existing) {
            return (int) $existing['id'];
        }

        return self::insert(
            $ownerId, $storeMemberId, $alertCode, $severity, $scope,
            $title, $message, $empMsg, $legal, $relType, $relId
        );
    }

    /** 항상 새 행 삽입. */
    public static function insert(
        int $ownerId,
        ?int $storeMemberId,
        string $alertCode,
        string $severity,
        string $scope,
        string $title,
        string $message,
        ?string $empMsg,
        ?string $legal,
        string $relType,
        ?int $relId
    ): int {
        DB::query(
            "INSERT INTO labor_risk_alerts
                (owner_id, store_member_id, related_type, related_id, alert_code,
                 severity, visibility_scope, title, message, employee_message, legal_basis)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)",
            [
                $ownerId, $storeMemberId, $relType, $relId, $alertCode,
                $severity, $scope, $title, $message, $empMsg, $legal,
            ]
        );
        return DB::lastInsertId();
    }

    public static function find(int $id, int $ownerId): ?array
    {
        return DB::fetchOne(
            'SELECT * FROM labor_risk_alerts WHERE id = ? AND owner_id = ?',
            [$id, $ownerId]
        );
    }

    public static function acknowledge(int $id, int $userId): void
    {
        DB::query(
            "UPDATE labor_risk_alerts
                SET status = 'acknowledged',
                    acknowledged_by_user_id = ?,
                    acknowledged_at = NOW()
             WHERE id = ? AND status IN ('open','acknowledged')",
            [$userId, $id]
        );
    }

    public static function resolve(int $id, int $ownerId): void
    {
        DB::query(
            "UPDATE labor_risk_alerts
                SET status = 'resolved', resolved_at = NOW()
             WHERE id = ? AND owner_id = ?",
            [$id, $ownerId]
        );
    }

    public static function ignore(int $id, int $ownerId): void
    {
        DB::query(
            "UPDATE labor_risk_alerts
                SET status = 'ignored', ignored_at = NOW()
             WHERE id = ? AND owner_id = ?",
            [$id, $ownerId]
        );
    }

    /** 등급별 미처리(open/acknowledged) 건수. */
    public static function countOpen(int $ownerId): array
    {
        $rows = DB::fetchAll(
            "SELECT severity, COUNT(*) AS cnt
               FROM labor_risk_alerts
              WHERE owner_id = ? AND status IN ('open','acknowledged')
              GROUP BY severity",
            [$ownerId]
        );
        $out = ['danger' => 0, 'warning' => 0, 'info' => 0];
        foreach ($rows as $r) {
            $out[$r['severity']] = (int) $r['cnt'];
        }
        return $out;
    }

    /**
     * 점주용 알림 목록.
     * filters: ['status' => 'active'|'all', 'severity' => 'danger'|'warning'|'info']
     */
    public static function forOwner(int $ownerId, array $filters = [], int $limit = 60): array
    {
        $sql = "SELECT lra.*, sm.name AS employee_name
                  FROM labor_risk_alerts lra
                  LEFT JOIN store_members sm ON sm.id = lra.store_member_id
                 WHERE lra.owner_id = ?";
        $params = [$ownerId];

        $statusFilter = $filters['status'] ?? 'active';
        if ($statusFilter === 'all') {
            // 전체 — 추가 조건 없음
        } else {
            $sql .= " AND lra.status IN ('open','acknowledged')";
        }

        if (!empty($filters['severity']) && in_array($filters['severity'], ['danger', 'warning', 'info'], true)) {
            $sql .= " AND lra.severity = ?";
            $params[] = $filters['severity'];
        }

        $sql .= " ORDER BY FIELD(lra.severity,'danger','warning','info'), lra.created_at DESC
                  LIMIT " . (int) $limit;

        return DB::fetchAll($sql, $params);
    }

    /** 직원에게 공개되는 알림 — 직원이 아직 응답하지 않은 것만 반환. */
    public static function forMember(int $storeMemberId, int $ownerId): array
    {
        return DB::fetchAll(
            "SELECT lra.* FROM labor_risk_alerts lra
              WHERE lra.store_member_id = ? AND lra.owner_id = ?
                AND lra.visibility_scope = 'employee_visible_required'
                AND lra.status IN ('open','acknowledged')
                AND NOT EXISTS (
                    SELECT 1 FROM employee_record_responses err
                    WHERE err.related_type = 'labor_risk_alert'
                      AND err.related_id = lra.id
                      AND err.store_member_id = lra.store_member_id
                )
              ORDER BY lra.created_at DESC
              LIMIT 30",
            [$storeMemberId, $ownerId]
        );
    }
}
