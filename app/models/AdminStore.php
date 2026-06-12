<?php
class AdminStore
{
    public static function list(array $filters = [], int $limit = 100): array
    {
        $sql = "SELECT s.*,
                       u.name AS owner_name, u.email AS owner_email,
                       COUNT(DISTINCT sm.id) AS member_count,
                       (SELECT COUNT(*) FROM work_logs wl
                         WHERE wl.store_id = s.id
                           AND wl.work_date >= DATE_FORMAT(NOW(),'%Y-%m-01')) AS monthly_log_count
                  FROM stores s
                  LEFT JOIN users u ON u.id = s.owner_id
                  LEFT JOIN store_members sm ON sm.store_id = s.id AND sm.is_active = 1
                 WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND s.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (s.store_name LIKE ? OR u.email LIKE ?)";
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }

        $sql .= " GROUP BY s.id ORDER BY s.created_at DESC LIMIT " . (int)$limit;
        return DB::fetchAll($sql, $params);
    }

    public static function find(int $id): ?array
    {
        return DB::fetchOne(
            "SELECT s.*, u.name AS owner_name, u.email AS owner_email
               FROM stores s
               LEFT JOIN users u ON u.id = s.owner_id
              WHERE s.id = ?",
            [$id]
        );
    }

    public static function updateStatus(int $id, string $status, string $reason = ''): void
    {
        $before = self::find($id);
        DB::query("UPDATE stores SET status = ? WHERE id = ?", [$status, $id]);
        AuditLog::record('STORE_STATUS_CHANGE', 'store', $id,
            ['status' => $before['status'] ?? ''],
            ['status' => $status],
            $reason
        );
    }

    public static function updatePlan(int $id, string $plan, string $reason = ''): void
    {
        $before = self::find($id);
        DB::query("UPDATE stores SET plan = ? WHERE id = ?", [$plan, $id]);
        AuditLog::record('STORE_PLAN_CHANGE', 'store', $id,
            ['plan' => $before['plan'] ?? ''],
            ['plan' => $plan],
            $reason
        );
    }

    public static function updateMemo(int $id, string $memo): void
    {
        DB::query("UPDATE stores SET admin_memo = ? WHERE id = ?", [$memo, $id]);
    }

    public static function members(int $storeId): array
    {
        return DB::fetchAll(
            "SELECT sm.*, u.email FROM store_members sm
               LEFT JOIN users u ON u.id = sm.user_id
              WHERE sm.store_id = ? ORDER BY sm.name ASC",
            [$storeId]
        );
    }

    public static function recentWorkLogs(int $storeId, int $limit = 20): array
    {
        return DB::fetchAll(
            "SELECT wl.*, sm.name AS employee_name
               FROM work_logs wl
               LEFT JOIN store_members sm ON sm.id = wl.employee_id
              WHERE wl.store_id = ?
              ORDER BY wl.work_date DESC LIMIT " . (int)$limit,
            [$storeId]
        );
    }

    public static function totalCount(): int
    {
        $r = DB::fetchOne("SELECT COUNT(*) AS cnt FROM stores");
        return (int)($r['cnt'] ?? 0);
    }

    public static function activeCount(): int
    {
        $r = DB::fetchOne("SELECT COUNT(*) AS cnt FROM stores WHERE status = 'ACTIVE'");
        return (int)($r['cnt'] ?? 0);
    }

    public static function recentlyJoined(int $limit = 5): array
    {
        return DB::fetchAll(
            "SELECT s.*, u.name AS owner_name, u.email AS owner_email
               FROM stores s
               LEFT JOIN users u ON u.id = s.owner_id
              ORDER BY s.created_at DESC LIMIT " . (int)$limit
        );
    }
}
