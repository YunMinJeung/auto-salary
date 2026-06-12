<?php
class StoreQrToken
{
    /**
     * 새 QR 토큰 발급.
     * $plainToken은 호출 측에서 생성해 전달한다 (bin2hex(random_bytes(32))).
     * DB에는 hash('sha256', $plainToken) 만 저장한다.
     */
    public static function issue(int $storeId, string $plainToken, int $createdBy): int
    {
        // 기존 활성 토큰 전부 폐기
        DB::query(
            "UPDATE store_qr_tokens SET is_active = 0, revoked_at = NOW()
             WHERE store_id = ? AND is_active = 1",
            [$storeId]
        );

        DB::query(
            'INSERT INTO store_qr_tokens (store_id, token_hash, is_active, created_by_user_id)
             VALUES (?, ?, 1, ?)',
            [$storeId, hash('sha256', $plainToken), $createdBy]
        );
        return DB::lastInsertId();
    }

    public static function findActiveByHash(string $hash): ?array
    {
        return DB::fetchOne(
            "SELECT * FROM store_qr_tokens
             WHERE token_hash = ? AND is_active = 1
               AND (expires_at IS NULL OR expires_at > NOW())",
            [$hash]
        ) ?: null;
    }

    public static function findActiveForStore(int $storeId): ?array
    {
        return DB::fetchOne(
            "SELECT * FROM store_qr_tokens
             WHERE store_id = ? AND is_active = 1
             ORDER BY created_at DESC LIMIT 1",
            [$storeId]
        ) ?: null;
    }

    public static function revoke(int $id, int $storeId): void
    {
        DB::query(
            "UPDATE store_qr_tokens SET is_active = 0, revoked_at = NOW()
             WHERE id = ? AND store_id = ?",
            [$id, $storeId]
        );
    }

    public static function history(int $storeId, int $limit = 10): array
    {
        return DB::fetchAll(
            "SELECT * FROM store_qr_tokens WHERE store_id = ?
             ORDER BY created_at DESC LIMIT {$limit}",
            [$storeId]
        );
    }
}
