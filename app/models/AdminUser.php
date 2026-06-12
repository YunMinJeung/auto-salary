<?php
class AdminUser
{
    public static function list(array $filters = [], int $limit = 100): array
    {
        $sql = "SELECT u.*,
                       GROUP_CONCAT(s.store_name SEPARATOR ', ') AS store_names
                  FROM users u
                  LEFT JOIN stores s ON s.owner_id = u.id
                 WHERE 1=1";
        $params = [];

        if (!empty($filters['role'])) {
            $sql .= " AND u.role = ?";
            $params[] = $filters['role'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (u.name LIKE ? OR u.email LIKE ?)";
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }

        $sql .= " GROUP BY u.id ORDER BY u.created_at DESC LIMIT " . (int)$limit;
        return DB::fetchAll($sql, $params);
    }

    public static function find(int $id): ?array
    {
        return DB::fetchOne(
            "SELECT u.*, GROUP_CONCAT(s.store_name SEPARATOR ', ') AS store_names
               FROM users u
               LEFT JOIN stores s ON s.owner_id = u.id
              WHERE u.id = ?
              GROUP BY u.id",
            [$id]
        );
    }

    public static function suspend(int $id, string $reason = ''): void
    {
        $before = User::find($id);
        DB::query("UPDATE users SET account_status = 'SUSPENDED' WHERE id = ?", [$id]);
        AuditLog::record('USER_SUSPEND', 'user', $id,
            ['account_status' => $before['account_status'] ?? 'ACTIVE'],
            ['account_status' => 'SUSPENDED'],
            $reason
        );
    }

    public static function reactivate(int $id, string $reason = ''): void
    {
        $before = User::find($id);
        DB::query("UPDATE users SET account_status = 'ACTIVE' WHERE id = ?", [$id]);
        AuditLog::record('USER_REACTIVATE', 'user', $id,
            ['account_status' => $before['account_status'] ?? 'SUSPENDED'],
            ['account_status' => 'ACTIVE'],
            $reason
        );
    }

    public static function updateMemo(int $id, string $memo): void
    {
        DB::query("UPDATE users SET admin_memo = ? WHERE id = ?", [$memo, $id]);
    }

    public static function totalCount(): int
    {
        $r = DB::fetchOne("SELECT COUNT(*) AS cnt FROM users");
        return (int)($r['cnt'] ?? 0);
    }

    public static function countByRole(string $role): int
    {
        $r = DB::fetchOne("SELECT COUNT(*) AS cnt FROM users WHERE role = ?", [$role]);
        return (int)($r['cnt'] ?? 0);
    }
}
