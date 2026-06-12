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
        $sql = 'SELECT r.*, sm.name AS member_name,
                       al.original_clock_in_at  AS original_in,
                       al.original_clock_out_at AS original_out,
                       al.adjusted_clock_in_at,
                       al.adjusted_clock_out_at
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
                    al.original_clock_in_at  AS original_in,
                    al.original_clock_out_at AS original_out
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
            'SELECT * FROM attendance_correction_requests WHERE id = ? AND store_id = ?',
            [$id, $storeId]
        );
    }

    /**
     * 승인 처리
     * - adjusted 필드에만 반영 (원본 보존)
     * - AttendanceAdjustmentLog에 이력 기록
     */
    public static function approve(int $id, int $storeId, string $comment = ''): void
    {
        $req = self::find($id, $storeId);
        if (!$req) return;

        $approverId   = (int)($_SESSION['user_id'] ?? 0);
        $approverRole = Auth::isOwner() ? 'owner' : 'employee';

        // 잠금 검사를 상태 변경 전에 먼저 수행 (잠긴 기록은 요청 상태도 바꾸지 않는다).
        $log = null;
        if ($req['attendance_log_id']) {
            $log = AttendanceLog::find((int)$req['attendance_log_id'], $storeId);
            if ($log && AttendanceLog::isLocked($log)) {
                return;
            }
        }

        DB::transaction(function () use ($id, $storeId, $comment, $req, $log, $approverId, $approverRole) {
            DB::query(
                "UPDATE attendance_correction_requests
                 SET status = 'approved', owner_comment = ?, updated_at = NOW()
                 WHERE id = ? AND store_id = ?",
                [$comment, $id, $storeId]
            );

            if ($req['attendance_log_id']) {
                if ($log) {
                    // 수정 전 유효 시각 (adjusted 우선)
                    $beforeIn  = $log['adjusted_clock_in_at']  ?? $log['original_clock_in_at'];
                    $beforeOut = $log['adjusted_clock_out_at'] ?? $log['original_clock_out_at'];

                    // 수정 이력 기록 (adjusted 변경 전에 먼저)
                    AttendanceAdjustmentLog::create([
                        'attendance_log_id'   => $req['attendance_log_id'],
                        'store_id'            => $storeId,
                        'store_member_id'     => $req['store_member_id'],
                        'changed_by_user_id'  => $approverId,
                        'changed_by_role'     => $approverRole,
                        'before_clock_in_at'  => $beforeIn,
                        'before_clock_out_at' => $beforeOut,
                        'after_clock_in_at'   => $req['requested_clock_in_at']  ?? null,
                        'after_clock_out_at'  => $req['requested_clock_out_at'] ?? null,
                        'reason'              => $comment ?: $req['reason'],
                        'change_type'         => 'employee_request_approved',
                        'employee_visible'    => 1,
                    ]);

                    // adjusted 필드에만 반영 (원본 보존)
                    AttendanceLog::ownerAdjust(
                        (int)$req['attendance_log_id'],
                        $storeId,
                        $req['requested_clock_in_at'],
                        $req['requested_clock_out_at']
                    );

                    AttendanceSyncService::sync((int)$req['attendance_log_id']);
                }
            } else {
                // 누락 기록 신규 생성 (original 필드 사용)
                if ($req['requested_clock_in_at']) {
                    $newLogId = AttendanceLog::ownerCreate(
                        $storeId,
                        $req['store_member_id'],
                        $req['requested_clock_in_at'],
                        $req['requested_clock_out_at']
                    );
                    AttendanceSyncService::sync($newLogId);
                }
            }
        });
    }

    public static function reject(int $id, int $storeId, string $comment = ''): void
    {
        DB::query(
            "UPDATE attendance_correction_requests
             SET status = 'rejected', owner_comment = ?, updated_at = NOW()
             WHERE id = ? AND store_id = ?",
            [$comment, $id, $storeId]
        );
    }

    public static function objection(int $id, int $storeId, string $text): void
    {
        DB::query(
            "UPDATE attendance_correction_requests
             SET status = 'objected', employee_objection = ?, updated_at = NOW()
             WHERE id = ? AND store_id = ? AND status = 'rejected'",
            [$text, $id, $storeId]
        );
    }

    public static function pendingCount(int $storeId): int
    {
        $row = DB::fetchOne(
            "SELECT COUNT(*) AS cnt FROM attendance_correction_requests
             WHERE store_id = ? AND status IN ('pending','objected')",
            [$storeId]
        );
        return (int)($row['cnt'] ?? 0);
    }
}
