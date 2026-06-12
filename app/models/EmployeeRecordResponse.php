<?php
/**
 * 직원의 기록 확인/이의제기 응답 모델.
 * 노무 리스크 알림 등 직원에게 공개된 기록에 대한 직원의 응답을 저장한다.
 */
class EmployeeRecordResponse
{
    public static function create(array $d): int
    {
        DB::query(
            "INSERT INTO employee_record_responses
                (owner_id, store_member_id, related_type, related_id, response_type, message, responded_at)
             VALUES (?,?,?,?,?,?,NOW())",
            [
                (int) $d['owner_id'],
                (int) $d['store_member_id'],
                $d['related_type'],
                isset($d['related_id']) ? (int) $d['related_id'] : null,
                $d['response_type'],
                $d['message'] ?? null,
            ]
        );
        return DB::lastInsertId();
    }

    public static function forMember(int $storeMemberId, int $ownerId): array
    {
        return DB::fetchAll(
            "SELECT * FROM employee_record_responses
              WHERE store_member_id = ? AND owner_id = ?
              ORDER BY created_at DESC
              LIMIT 20",
            [$storeMemberId, $ownerId]
        );
    }

    /** 미처리(submitted) 이의제기 건수. */
    public static function pendingObjections(int $ownerId): int
    {
        $row = DB::fetchOne(
            "SELECT COUNT(*) AS cnt FROM employee_record_responses
              WHERE owner_id = ? AND response_type = 'objection' AND status = 'submitted'",
            [$ownerId]
        );
        return (int) ($row['cnt'] ?? 0);
    }

    /** 미처리 이의제기 목록 (직원명 포함). */
    public static function openObjections(int $ownerId): array
    {
        return DB::fetchAll(
            "SELECT err.*, sm.name AS employee_name
               FROM employee_record_responses err
               LEFT JOIN store_members sm ON sm.id = err.store_member_id
              WHERE err.owner_id = ?
                AND err.response_type = 'objection'
                AND err.status = 'submitted'
              ORDER BY err.created_at DESC",
            [$ownerId]
        );
    }
}
