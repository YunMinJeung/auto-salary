<?php
class AuditLog
{
    public static function record(
        string $action,
        string $targetType,
        ?int $targetId,
        $beforeValue,
        $afterValue,
        string $reason = ''
    ): void {
        DB::query(
            "INSERT INTO audit_logs
               (actor_user_id, actor_role, action, target_type, target_id,
                before_value, after_value, reason, ip_address, user_agent)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                Auth::id(),
                Auth::user()['role'] ?? 'unknown',
                $action,
                $targetType,
                $targetId,
                $beforeValue !== null ? json_encode($beforeValue, JSON_UNESCAPED_UNICODE) : null,
                $afterValue  !== null ? json_encode($afterValue,  JSON_UNESCAPED_UNICODE) : null,
                $reason,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]
        );
    }

    public static function recent(int $limit = 50): array
    {
        return DB::fetchAll(
            "SELECT al.*, u.name AS actor_name, u.email AS actor_email
               FROM audit_logs al
               LEFT JOIN users u ON u.id = al.actor_user_id
              ORDER BY al.created_at DESC
              LIMIT " . (int)$limit
        );
    }

    public static function forTarget(string $type, int $id): array
    {
        return DB::fetchAll(
            "SELECT al.*, u.name AS actor_name
               FROM audit_logs al
               LEFT JOIN users u ON u.id = al.actor_user_id
              WHERE al.target_type = ? AND al.target_id = ?
              ORDER BY al.created_at DESC",
            [$type, $id]
        );
    }
}
