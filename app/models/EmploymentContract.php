<?php
class EmploymentContract
{
    public static function create(int $storeId, int $memberId, array $formData, int $createdBy): int
    {
        DB::query(
            'INSERT INTO employment_contracts
             (store_id, store_member_id, form_data, contract_start_date, contract_end_date, created_by_user_id)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                $storeId,
                $memberId,
                json_encode($formData, JSON_UNESCAPED_UNICODE),
                $formData['contract_start_date'] ?: null,
                $formData['contract_end_date']   ?: null,
                $createdBy,
            ]
        );
        return DB::lastInsertId();
    }

    public static function find(int $id, int $storeId): ?array
    {
        $row = DB::fetchOne(
            'SELECT * FROM employment_contracts WHERE id = ? AND store_id = ?',
            [$id, $storeId]
        );
        if ($row) {
            $row['form_data'] = json_decode($row['form_data'], true) ?? [];
        }
        return $row ?: null;
    }

    public static function allForMember(int $storeId, int $memberId): array
    {
        $rows = DB::fetchAll(
            'SELECT id, contract_start_date, contract_end_date, pdf_downloaded, created_at
             FROM employment_contracts
             WHERE store_id = ? AND store_member_id = ?
             ORDER BY created_at DESC',
            [$storeId, $memberId]
        );
        return $rows;
    }

    public static function latestFormData(int $storeId, int $memberId): ?array
    {
        $row = DB::fetchOne(
            'SELECT form_data FROM employment_contracts
             WHERE store_id = ? AND store_member_id = ?
             ORDER BY created_at DESC LIMIT 1',
            [$storeId, $memberId]
        );
        return $row ? (json_decode($row['form_data'], true) ?? null) : null;
    }

    public static function markDownloaded(int $id, int $storeId): void
    {
        DB::query(
            'UPDATE employment_contracts SET pdf_downloaded = 1, pdf_downloaded_at = NOW()
             WHERE id = ? AND store_id = ? AND pdf_downloaded = 0',
            [$id, $storeId]
        );
    }
}
