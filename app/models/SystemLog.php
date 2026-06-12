<?php
class SystemLog
{
    public static function write(
        string $type,
        string $action,
        bool $success = true,
        ?string $errorMsg = null,
        ?string $targetData = null
    ): void {
        DB::query(
            "INSERT INTO system_logs
               (log_type, user_id, store_id, action, target_data, ip_address, user_agent, is_success, error_message)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $type,
                Auth::check() ? Auth::id()      : null,
                Auth::check() ? Auth::storeId() : null,
                $action,
                $targetData,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $success ? 1 : 0,
                $errorMsg,
            ]
        );
    }

    public static function list(array $filters = [], int $limit = 100): array
    {
        $sql    = "SELECT sl.*, u.name AS user_name, s.store_name
                     FROM system_logs sl
                     LEFT JOIN users u  ON u.id  = sl.user_id
                     LEFT JOIN stores s ON s.id  = sl.store_id
                    WHERE 1=1";
        $params = [];

        if (!empty($filters['type'])) {
            $sql .= " AND sl.log_type = ?";
            $params[] = $filters['type'];
        }
        if (!empty($filters['store_id'])) {
            $sql .= " AND sl.store_id = ?";
            $params[] = (int)$filters['store_id'];
        }
        if (!empty($filters['user_id'])) {
            $sql .= " AND sl.user_id = ?";
            $params[] = (int)$filters['user_id'];
        }
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(sl.created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(sl.created_at) <= ?";
            $params[] = $filters['date_to'];
        }
        if (isset($filters['is_success']) && $filters['is_success'] !== '') {
            $sql .= " AND sl.is_success = ?";
            $params[] = (int)$filters['is_success'];
        }

        $sql .= " ORDER BY sl.created_at DESC LIMIT " . (int)$limit;
        return DB::fetchAll($sql, $params);
    }

    public static function countRecent(string $type, int $hours = 24): int
    {
        $row = DB::fetchOne(
            "SELECT COUNT(*) AS cnt FROM system_logs WHERE log_type = ? AND created_at >= NOW() - INTERVAL ? HOUR",
            [$type, $hours]
        );
        return (int)($row['cnt'] ?? 0);
    }
}
