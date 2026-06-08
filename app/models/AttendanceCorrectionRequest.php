<?php
class AttendanceCorrectionRequest
{
    public static function create(array $data): int
    {
        DB::query(
            'INSERT INTO attendance_correction_requests
             (attendance_log_id, store_id, store_member_id,
              requested_clock_in_at, requested_clock_out_at, reason)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                $data['attendance_log_id'] ?: null,
                $data['store_id'],
                $data['store_member_id'],
                $data['requested_clock_in_at']  ?: null,
                $data['requested_clock_out_at'] ?: null,
                $data['reason'],
            ]
        );
        return DB::lastInsertId();
    }

    /** 점주: 사업장의 모든 요청 */
    public static function allForStore(int $storeId, ?string $status = null): array
    {
        $sql    = 'SELECT r.*, sm.name AS member_name,
                          al.clock_in_at AS original_in, al.clock_out_at AS original_out
                   FROM attendance_correction_requests r
                   JOIN store_members sm ON sm.id = r.store_member_id
                   LEFT JOIN attendance_logs al ON al.id = r.attendance_log_id
                   WHERE r.store_id = ?';
        $params = [$storeId];
        if ($status) {
            $sql .= ' AND r.status = ?';
            $params[] = $status;
        }
        $sql .= ' ORDER BY r.created_at DESC';
        return DB::fetchAll($sql, $params);
    }

    /** 알바생: 본인 요청 목록 */
    public static function allForMember(int $storeMemberId): array
    {
        return DB::fetchAll(
            'SELECT r.*,
                    al.clock_in_at AS original_in, al.clock_out_at AS original_out
             FROM attendance_correction_requests r
             LEFT JOIN attendance_logs al ON al.id = r.attendance_log_id
             WHERE r.store_member_id = ?
             ORDER BY r.created_at DESC',
            [$storeMemberId]
        );
    }

    public static function find(int $id, int $storeId): ?array
    {
        return DB::fetchOne(
            'SELECT * FROM attendance_correction_requests WHERE id=? AND store_id=?',
            [$id, $storeId]
        );
    }

    public static function approve(int $id, int $storeId, string $comment = ''): void
    {
        $req = self::find($id, $storeId);
        if (!$req) return;

        DB::query(
            "UPDATE attendance_correction_requests
             SET status='approved', owner_comment=?, updated_at=NOW()
             WHERE id=? AND store_id=?",
            [$comment, $id, $storeId]
        );

        // 실제 출퇴근 기록 수정
        if ($req['attendance_log_id']) {
            DB::query(
                "UPDATE attendance_logs
                 SET clock_in_at=IFNULL(?, clock_in_at),
                     clock_out_at=IFNULL(?, clock_out_at),
                     status='approved', updated_at=NOW()
                 WHERE id=? AND store_id=?",
                [
                    $req['requested_clock_in_at']  ?: null,
                    $req['requested_clock_out_at'] ?: null,
                    $req['attendance_log_id'],
                    $storeId,
                ]
            );
        } else {
            // 누락된 출근 기록 신규 생성
            if ($req['requested_clock_in_at']) {
                AttendanceLog::ownerCreate(
                    $storeId,
                    $req['store_member_id'],
                    $req['requested_clock_in_at'],
                    $req['requested_clock_out_at']
                );
            }
        }
    }

    public static function reject(int $id, int $storeId, string $comment = ''): void
    {
        DB::query(
            "UPDATE attendance_correction_requests
             SET status='rejected', owner_comment=?, updated_at=NOW()
             WHERE id=? AND store_id=?",
            [$comment, $id, $storeId]
        );
    }

    public static function pendingCount(int $storeId): int
    {
        $row = DB::fetchOne(
            "SELECT COUNT(*) AS cnt FROM attendance_correction_requests
             WHERE store_id=? AND status='pending'",
            [$storeId]
        );
        return (int)($row['cnt'] ?? 0);
    }
}
