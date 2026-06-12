<?php
class MinorConsentForm
{
    public static function create(int $storeId, int $memberId, array $formData, int $createdBy): int
    {
        $dob       = $formData['date_of_birth'] ?: null;
        $ageAtSign = null;
        if ($dob) {
            $birth = new DateTime($dob);
            $now   = new DateTime();
            $ageAtSign = (int)$birth->diff($now)->y;
        }

        DB::query(
            'INSERT INTO minor_consent_forms
             (store_id, store_member_id, form_data, date_of_birth, age_at_signing,
              guardian_name, guardian_relation, created_by_user_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $storeId,
                $memberId,
                json_encode($formData, JSON_UNESCAPED_UNICODE),
                $dob,
                $ageAtSign,
                $formData['guardian_name']     ?? null,
                $formData['guardian_relation'] ?? null,
                $createdBy,
            ]
        );
        return DB::lastInsertId();
    }

    public static function find(int $id, int $storeId): ?array
    {
        $row = DB::fetchOne(
            'SELECT * FROM minor_consent_forms WHERE id = ? AND store_id = ?',
            [$id, $storeId]
        );
        if ($row) {
            $row['form_data'] = json_decode($row['form_data'], true) ?? [];
        }
        return $row ?: null;
    }

    public static function allForMember(int $storeId, int $memberId): array
    {
        return DB::fetchAll(
            'SELECT id, date_of_birth, age_at_signing, guardian_name, guardian_relation,
                    pdf_downloaded, created_at
             FROM minor_consent_forms
             WHERE store_id = ? AND store_member_id = ?
             ORDER BY created_at DESC',
            [$storeId, $memberId]
        );
    }

    public static function markDownloaded(int $id, int $storeId): void
    {
        DB::query(
            'UPDATE minor_consent_forms SET pdf_downloaded = 1, pdf_downloaded_at = NOW()
             WHERE id = ? AND store_id = ? AND pdf_downloaded = 0',
            [$id, $storeId]
        );
    }
}
