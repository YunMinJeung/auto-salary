<?php
class SupportTicket
{
    public static function allForAdmin(array $filters = [], int $limit = 100): array
    {
        $sql = "SELECT t.*, u.name AS user_name, u.email AS user_email,
                       s.store_name
                  FROM support_tickets t
                  LEFT JOIN users u  ON u.id  = t.user_id
                  LEFT JOIN stores s ON s.id  = t.store_id
                 WHERE 1=1";
        $params = [];
        if (!empty($filters['status'])) {
            $sql .= " AND t.status = ?";
            $params[] = $filters['status'];
        }
        $sql .= " ORDER BY FIELD(t.status,'OPEN','IN_PROGRESS','HOLD','ANSWERED','CLOSED'), t.created_at DESC LIMIT " . (int)$limit;
        return DB::fetchAll($sql, $params);
    }

    public static function find(int $id): ?array
    {
        return DB::fetchOne(
            "SELECT t.*, u.name AS user_name, u.email AS user_email, s.store_name
               FROM support_tickets t
               LEFT JOIN users u  ON u.id  = t.user_id
               LEFT JOIN stores s ON s.id  = t.store_id
              WHERE t.id = ?",
            [$id]
        );
    }

    public static function countOpen(): int
    {
        $r = DB::fetchOne("SELECT COUNT(*) AS cnt FROM support_tickets WHERE status IN ('OPEN','IN_PROGRESS')");
        return (int)($r['cnt'] ?? 0);
    }

    public static function recent(int $limit = 5): array
    {
        return DB::fetchAll(
            "SELECT t.*, u.name AS user_name, s.store_name
               FROM support_tickets t
               LEFT JOIN users u  ON u.id  = t.user_id
               LEFT JOIN stores s ON s.id  = t.store_id
              ORDER BY t.created_at DESC LIMIT " . (int)$limit
        );
    }

    public static function updateStatus(int $id, string $status): void
    {
        DB::query("UPDATE support_tickets SET status = ? WHERE id = ?", [$status, $id]);
    }

    public static function reply(int $id, string $reply, string $memo = ''): void
    {
        DB::query(
            "UPDATE support_tickets SET admin_reply = ?, admin_memo = ?, status = 'ANSWERED', replied_at = NOW() WHERE id = ?",
            [$reply, $memo ?: null, $id]
        );
    }

    public static function create(array $data): int
    {
        DB::query(
            "INSERT INTO support_tickets (user_id, store_id, ticket_type, title, content)
             VALUES (?, ?, ?, ?, ?)",
            [
                $data['user_id']     ?? null,
                $data['store_id']    ?? null,
                $data['ticket_type'] ?? 'ETC',
                $data['title'],
                $data['content'],
            ]
        );
        return DB::lastInsertId();
    }
}
